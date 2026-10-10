<?php

namespace App\Console\Commands;

use App\Models\ReferenceDataOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Adds the Tulu bari / bali names to the "Gotra / Bari" list (reference data
 * category `gothram`) in groups, after the draft list has been checked by
 * community members (review doc, Oct 2026). Brahmin gotras become their own
 * group (plus Naidhruva, Angirasa), then the five Vishwakarma gotras;
 * "Don't Know" / "Other" go last. Admins can edit any name later in
 * Reference Data, and the Unlisted page shows what members type that isn't
 * listed.
 *
 * Every bari is checked against a source (10 Oct 2026, round 2 with
 * ChatGPT / Gemini, then the sources themselves): Billava baris
 * billava-marriage.blogspot.com + Ramanath Kotekar (2012) via
 * tulu-research.blogspot.com; Mogaveera en.wikipedia.org/wiki/Mogaveera;
 * Bunt + Nadava balis buntsdaily.com (2016) + Sacchidananda Hegde (2009) via
 * tulu-research; Gowda en.wikipedia.org/wiki/Tulu_Gowda (15 of 18);
 * Devadiga en.wikipedia.org/wiki/Devadiga (Singh 2006).
 *
 * Safe to run again: it puts every listed bari in its group and place, and
 * changes nothing on a second run.
 */
class AddTuluBaris extends Command
{
    protected $signature = 'reference:add-tulu-baris {--dry-run : show what would change, change nothing}';

    protected $description = 'Add the reviewed Tulu bari / bali names to the Gotra / Bari list';

    /**
     * Group label => names, in the order members see them. A name is stored
     * once (unique per list), so a name listed in two sources sits in the
     * first group only: Kundarannaya and Gujjarannaya (Devadiga too) are
     * under Bunt balis, and search finds them.
     */
    public const BARIS = [
        'Common Tulu baris' => ['Amin', 'Anchan', 'Banan', 'Bangera', 'Chandan', 'Gujaran', 'Jathanna', 'Kabera', 'Kanchan', 'Karkera', 'Kirodian', 'Kotian', 'Kukkian', 'Kunder', 'Mabian', 'Maindan', 'Mendon', 'Mulyeranna', 'Mundan', 'Naika', 'Nayak', 'Palan', 'Pangal', 'Pergade', 'Pulyatan', 'Puthran', 'Rao', 'Salian', 'Sanil', 'Sapaliga', 'Shirodian', 'Shriyan', 'Suvarna', 'Thingalaya', 'Tholar', 'Upiyan'],
        // Bunts Daily's 26 (+ Karkatarannaya, Chalyannaya: Thurston 1909), then Hegde's (2009) not already here
        'Bunt balis' => ['Aarya Bannaya', 'Ujjiti Bannaya', 'Uddarannaya', 'Upparannaya', 'Uli Bannaya', 'Karkatarannaya', 'Karmarannaya', 'Kanchannaya', 'Kundarannaya', 'Kuberannaya', 'Kellarannaya', 'Kochata Bannaya', 'Komati Bannaya', 'Gujjile Bannaya', 'Chalyannaya', 'Thingalannaya', 'Nala Bannaya', 'Pangalannaya', 'Puthurannaya', 'Pulyothannaya', 'Pergade Bannaya', 'Bangarannaya', 'Bhagyatavannaya', 'Barmetheri Bannaya', 'Bunnannaya', 'Salannaya', 'Hiri Bannaya', 'Siri Bannaya',
            'Ammannya', 'Anchannaya', 'Chavadithannaya', 'Gondarannya', 'Gujjarannaya', 'Kongannaya', 'Kotirannaya', 'Nandarannaya'],
        'Nadava balis' => ['Akkachchara Bali', 'Ajjara Bali', 'Kavachana Bali', 'Kanchana Bali', 'Kashya Bali', 'Garee Bali', 'Gangaara Bali', 'Chandi Bali', 'Haara Bali', 'Jaaji Bali', 'Chiththaan Bali', 'Jwaalara Bali', 'Tholaara Bali', 'Devara Bali', 'Naamada Bali', 'Pergade Bali', 'Baale Bali', 'Beegara Bali', 'Shrigaara Bali', 'Shetty Bali', 'Hattina Bali', 'Hangaara Bali', 'Haavina Bali', 'Siri Bali', 'Honnina Bali', 'Hole Bali'],
        'Gowda baris' => ['Bangara', 'Nandara', 'Malara', 'Salu', 'Hemmana', 'Kabru', 'Goli', 'Basruvogaru', 'Balasanna', 'Kabar', 'Gundana', 'Chalyera', 'Mulyera', 'Nayera', 'Karbannaya'],
        'Devadiga baris' => ['Saliyannaya', 'Bangerannaya', 'Kajjannaya', 'Kariannaya', 'Bhuthiannaya', 'Kochatabettannaya', 'Shaliyan', 'Serian', 'Addiyar', 'Gundranna', 'Uppayana'],
    ];

    /** Spellings corrected from the sources (old => new); members who chose the old one move too. */
    public const RENAMED = ['Naik' => 'Nayak'];

    /** In no source: switched off (a member who chose one keeps it). */
    public const RETIRED = ['Banjan'];

    public const GOTRA_GROUP = 'Brahmin gotras';

    public const UNSURE_GROUP = 'Not sure / not listed';

    /** Gotras to add after the existing Brahmin ones (group => [sort start, names]). */
    public const GOTRAS = [
        self::GOTRA_GROUP => [5500, ['Naidhruva', 'Angirasa']],
        'Vishwakarma gotras' => [6000, ['Sanaga', 'Sanatana', 'Abhuvanasa', 'Pratnasa', 'Suparna']],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $load = fn () => ReferenceDataOption::where('category', 'gothram')->get()->keyBy(fn ($r) => mb_strtolower($r->value));
        $rows = $load();

        // Corrections first, so the renamed rows take their new place below
        $renamed = 0;
        $membersMoved = 0;
        foreach (self::RENAMED as $old => $new) {
            $row = $rows->get(mb_strtolower($old));
            if (! $row || $rows->has(mb_strtolower($new))) {
                continue;
            }
            $renamed++;
            $membersMoved += DB::table('religious_info')->where('gotra', $old)->count();
            if (! $dry) {
                $row->update(['value' => $new]);
                DB::table('religious_info')->where('gotra', $old)->update(['gotra' => $new]);
            }
        }
        $retired = 0;
        foreach (self::RETIRED as $name) {
            $row = $rows->get(mb_strtolower($name));
            if ($row && $row->is_active) {
                $retired++;
                if (! $dry) {
                    $row->update(['is_active' => false]);
                }
            }
        }
        if (! $dry) {
            $rows = $load();
        }

        // Baris (1000+): every listed one in its group and place
        $added = 0;
        $placed = 0;
        $order = 1000;
        $seen = [];
        foreach (self::BARIS as $group => $names) {
            foreach ($names as $name) {
                $order += 10;
                $key = mb_strtolower($name);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $row = $rows->get($key);
                if (! $row) {
                    $added++;
                    if (! $dry) {
                        ReferenceDataOption::create(['category' => 'gothram', 'group_label' => $group, 'value' => $name, 'sort_order' => $order, 'is_active' => true]);
                    }

                    continue;
                }
                if ($row->group_label !== $group || (int) $row->sort_order !== $order) {
                    $placed++;
                    if (! $dry) {
                        $row->update(['group_label' => $group, 'sort_order' => $order]);
                    }
                }
            }
        }

        // Gotras added after the Brahmin ones (5500+) and the Vishwakarma group (6000+)
        foreach (self::GOTRAS as $group => [$gotraOrder, $names]) {
            foreach ($names as $name) {
                $gotraOrder += 10;
                if ($rows->has(mb_strtolower($name))) {
                    continue;
                }
                $added++;
                if (! $dry) {
                    ReferenceDataOption::create(['category' => 'gothram', 'group_label' => $group, 'value' => $name, 'sort_order' => $gotraOrder, 'is_active' => true]);
                }
            }
        }

        // Everything else: the old gotras (5000+), Don't Know / Other last (9000+)
        $regrouped = 0;
        foreach ($rows as $key => $row) {
            if (isset($seen[$key]) || in_array($row->value, self::RETIRED, true)) {
                continue;
            }
            $unsure = in_array($key, ["don't know", 'other'], true);
            $group = $unsure ? self::UNSURE_GROUP : ($row->group_label ?: self::GOTRA_GROUP);
            // already grouped → keep its place (so a second run changes nothing)
            $sort = $row->group_label ? (int) $row->sort_order : ($unsure ? 9000 : 5000) + (int) $row->sort_order;
            if ($row->group_label === $group && (int) $row->sort_order === $sort) {
                continue;
            }
            $regrouped++;
            if (! $dry) {
                $row->update(['group_label' => $group, 'sort_order' => $sort]);
            }
        }

        $this->line(($dry ? '[dry run] ' : '')."names added: {$added}; baris put in place: {$placed}; other options grouped: {$regrouped}; renamed: {$renamed} (members moved: {$membersMoved}); switched off: {$retired}");

        return self::SUCCESS;
    }
}
