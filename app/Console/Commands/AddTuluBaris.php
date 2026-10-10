<?php

namespace App\Console\Commands;

use App\Models\ReferenceDataOption;
use Illuminate\Console\Command;

/**
 * Adds the Tulu bari / bali names to the "Gotra / Bari" list (reference data
 * category `gothram`) in groups, after the draft list has been checked by
 * community members (review doc, Oct 2026). Brahmin gotras become their own
 * group (plus Naidhruva, Angirasa), then the five Vishwakarma gotras;
 * "Don't Know" / "Other" go last. Admins can edit any name later in
 * Reference Data, and the Unlisted page shows what members type that isn't
 * listed. Safe to run again: existing names are left alone.
 */
class AddTuluBaris extends Command
{
    protected $signature = 'reference:add-tulu-baris {--dry-run : show what would change, change nothing}';

    protected $description = 'Add the reviewed Tulu bari / bali names to the Gotra / Bari list';

    /** Group label => names, in the order members see them. */
    public const BARIS = [
        'Common Tulu baris' => ['Amin', 'Anchan', 'Banan', 'Bangera', 'Banjan', 'Chandan', 'Gujaran', 'Jathanna', 'Kanchan', 'Karkera', 'Kirodian', 'Kotian', 'Kukkian', 'Kunder', 'Mabian', 'Maindan', 'Mendon', 'Naik', 'Palan', 'Pangal', 'Puthran', 'Rao', 'Salian', 'Sanil', 'Sapaliga', 'Shriyan', 'Suvarna', 'Thingalaya', 'Tholar', 'Upiyan'],
        // + Karkatarannaya, Chalyannaya: in both Gemini's list and Thurston 1909 (Karkadabennai, Chaliannaya)
        'Bunt balis' => ['Aarya Bannaya', 'Ujjiti Bannaya', 'Uddarannaya', 'Upparannaya', 'Uli Bannaya', 'Karkatarannaya', 'Karmarannaya', 'Kanchannaya', 'Kundarannaya', 'Kuberannaya', 'Kellarannaya', 'Kochata Bannaya', 'Komati Bannaya', 'Gujjile Bannaya', 'Chalyannaya', 'Thingalannaya', 'Nala Bannaya', 'Pangalannaya', 'Puthurannaya', 'Pulyothannaya', 'Pergade Bannaya', 'Bangarannaya', 'Bhagyatavannaya', 'Barmetheri Bannaya', 'Bunnannaya', 'Salannaya', 'Hiri Bannaya', 'Siri Bannaya'],
        'Nadava balis' => ['Akkachchara Bali', 'Ajjara Bali', 'Kavachana Bali', 'Kanchana Bali', 'Kashya Bali', 'Garee Bali', 'Gangaara Bali', 'Chandi Bali', 'Haara Bali', 'Jaaji Bali', 'Chiththaan Bali', 'Jwaalara Bali', 'Tholaara Bali', 'Devara Bali', 'Naamada Bali', 'Pergade Bali', 'Baale Bali', 'Beegara Bali', 'Shrigaara Bali', 'Shetty Bali', 'Hattina Bali', 'Hangaara Bali', 'Haavina Bali', 'Siri Bali', 'Honnina Bali', 'Hole Bali'],
        'Gowda baris' => ['Bangara', 'Nandara', 'Malara', 'Salu', 'Hemmana', 'Kabru', 'Goli', 'Basruvogaru', 'Balasanna', 'Kabar', 'Gundana', 'Chalyera', 'Mulyera', 'Nayera', 'Karbannaya'],
    ];

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
        $rows = ReferenceDataOption::where('category', 'gothram')->get()->keyBy(fn ($r) => mb_strtolower($r->value));

        // Baris first (1000+), then the Brahmin gotras (5000+), then Don't Know / Other (9000+)
        $added = 0;
        $order = 1000;
        foreach (self::BARIS as $group => $names) {
            foreach ($names as $name) {
                $order += 10;
                if ($rows->has(mb_strtolower($name))) {
                    continue;
                }
                $added++;
                if (! $dry) {
                    ReferenceDataOption::create(['category' => 'gothram', 'group_label' => $group, 'value' => $name, 'sort_order' => $order, 'is_active' => true]);
                }
            }
        }

        foreach (self::GOTRAS as $group => [$order, $names]) {
            foreach ($names as $name) {
                $order += 10;
                if ($rows->has(mb_strtolower($name))) {
                    continue;
                }
                $added++;
                if (! $dry) {
                    ReferenceDataOption::create(['category' => 'gothram', 'group_label' => $group, 'value' => $name, 'sort_order' => $order, 'is_active' => true]);
                }
            }
        }

        $regrouped = 0;
        foreach ($rows as $key => $row) {
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

        $this->line(($dry ? '[dry run] ' : '')."bari names added: {$added}; existing options grouped: {$regrouped}");

        return self::SUCCESS;
    }
}
