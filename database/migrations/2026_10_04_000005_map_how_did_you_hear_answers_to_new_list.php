<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Moves "How did you hear about us?" answers given on the old Kerala-media
 * list onto the new channel-level options (config/reference_data.php), so
 * old and new answers can be counted together. Answers already on the new
 * list (Google Search, Instagram, Friends, Others…) are untouched.
 */
return new class extends Migration
{
    public const MAP = [
        'Auto / Bus Advertisement' => ['Auto', 'Bus', 'Boat', 'Metro'],
        'Poster / Banner' => ['Bill Board', 'Hoarding', 'Notice/Pamphlet'],
        'Event / Exhibition' => ['Carnival', 'Consumer Fair', 'Conventions'],
        'Parish Bulletin / Magazine' => ['College Magazines', 'Community Magazines', 'Other Magazines', 'Parish Bulletins'],
        'Newspaper' => ['Deepika', 'Deshabhimani', 'Janapraksham', 'Keralasabha'],
        'Our Office / Phone Call' => ['Tele-In', 'Telemarketing'],
        'Radio' => ['Aakashavani', 'Club FM', 'Local Radios', 'Radio Mango', 'Radio Mirchi', 'Red FM'],
        'TV' => ['24news', 'Asianet', 'Asianet Movies', 'Asianet News', 'Flowers'],
        'Church / Parish' => ['Church'],
        'Family' => ['Family Marriage'],
        'Another Website' => ['Bing', 'DuckDuckGo', 'Yahoo', 'Yandex', 'LinkedIn', 'Pinterest', 'Snapchat'],
    ];

    public function up(): void
    {
        foreach (self::MAP as $new => $old) {
            DB::table('profiles')->whereIn('how_did_you_hear_about_us', $old)
                ->update(['how_did_you_hear_about_us' => $new]);
        }
    }

    public function down(): void
    {
        // One-way: several old answers merge into one. Restore from the
        // pre-deploy export if ever needed.
    }
};
