<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ReferenceDataEditor extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Reference Data';
    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Reference Data Editor';
    protected string $view = 'filament.pages.reference-data-editor';

    public string $selectedCategory = 'educational_qualifications_list';
    public string $editorContent = '';
    public bool $isGrouped = false;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_content');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_content');
    }

    /**
     * All editable categories with labels and whether they're grouped.
     */
    public static function categories(): array
    {
        return [
            'educational_qualifications_list' => ['label' => 'Education Qualifications', 'grouped' => true],
            'occupation_category_list' => ['label' => 'Occupation Categories', 'grouped' => true],
            'language_list' => ['label' => 'Mother Tongues / Languages', 'grouped' => false],
            'annual_income_list' => ['label' => 'Annual Income Ranges', 'grouped' => false],
            'eating_habits' => ['label' => 'Eating Habits (Diet)', 'grouped' => false],
            'drinking_habits' => ['label' => 'Drinking Habits', 'grouped' => false],
            'smoking_habits' => ['label' => 'Smoking Habits', 'grouped' => false],
            'cultural_background_list' => ['label' => 'Cultural Background', 'grouped' => false],
            'hobbies_list' => ['label' => 'Hobbies & Interests', 'grouped' => false],
            'music_list' => ['label' => 'Music Preferences', 'grouped' => false],
            'books_list' => ['label' => 'Books Preferences', 'grouped' => false],
            'movies_list' => ['label' => 'Movies Preferences', 'grouped' => false],
            'sports_list' => ['label' => 'Sports & Fitness', 'grouped' => false],
            'cuisine_list' => ['label' => 'Cuisine Preferences', 'grouped' => false],
            'how_did_you_hear_list' => ['label' => 'How Did You Hear Options', 'grouped' => false],
            'height_list' => ['label' => 'Height Options', 'grouped' => false],
            'weight_list' => ['label' => 'Weight Options', 'grouped' => false],
            'country_list' => ['label' => 'Countries', 'grouped' => true],
            'denomination_list' => ['label' => 'Denominations', 'grouped' => true],
            'diocese_list' => ['label' => 'Dioceses', 'grouped' => true],
            'rasi_list' => ['label' => 'Rasi (Zodiac)', 'grouped' => false],
            'nakshatra_list' => ['label' => 'Nakshatra (Stars)', 'grouped' => false],
            'gothram_list' => ['label' => 'Gothram / Gothra', 'grouped' => false],
            'muslim_sect_list' => ['label' => 'Muslim Sects', 'grouped' => false],
            'jain_sect_list' => ['label' => 'Jain Sects', 'grouped' => false],
            'jamath_list' => ['label' => 'Muslim Jamath / Community', 'grouped' => false],
        ];
    }

    public function mount(): void
    {
        $this->loadCategory();
    }

    public function updatedSelectedCategory(): void
    {
        $this->loadCategory();
    }

    protected function loadCategory(): void
    {
        $categories = self::categories();
        $cat = $categories[$this->selectedCategory] ?? null;

        if (!$cat) {
            return;
        }

        $this->isGrouped = $cat['grouped'];

        // Check DB override first, then fallback to config
        $dbKey = 'ref_data_' . $this->selectedCategory;
        $dbValue = SiteSetting::getValue($dbKey);

        if ($dbValue) {
            $data = json_decode($dbValue, true);
        } else {
            $data = config('reference_data.' . $this->selectedCategory, []);
        }

        $this->editorContent = $this->dataToText($data, $this->isGrouped);
    }

    public function save(): void
    {
        $categories = self::categories();
        $cat = $categories[$this->selectedCategory] ?? null;

        if (!$cat) {
            return;
        }

        $parsed = $this->textToData($this->editorContent, $cat['grouped']);

        if (empty($parsed)) {
            Notification::make()
                ->title('Data is empty. Please add at least one item.')
                ->danger()
                ->send();
            return;
        }

        $dbKey = 'ref_data_' . $this->selectedCategory;
        SiteSetting::setValue($dbKey, json_encode($parsed, JSON_UNESCAPED_UNICODE));

        // Clear the reference data cache
        \Illuminate\Support\Facades\Cache::forget("site_setting.{$dbKey}");

        Notification::make()
            ->title($cat['label'] . ' saved successfully (' . $this->countItems($parsed) . ' items)')
            ->success()
            ->send();
    }

    public function resetToDefault(): void
    {
        $dbKey = 'ref_data_' . $this->selectedCategory;

        // Delete the DB override
        \App\Models\SiteSetting::where('key', $dbKey)->delete();
        \Illuminate\Support\Facades\Cache::forget("site_setting.{$dbKey}");
        \Illuminate\Support\Facades\Cache::forget('site_settings.all');

        // Reload from config
        $this->loadCategory();

        Notification::make()
            ->title('Reset to default values from config file')
            ->success()
            ->send();
    }

    /**
     * Convert array data to editable text format.
     */
    protected function dataToText(array $data, bool $grouped): string
    {
        if (!$grouped) {
            return implode("\n", $data);
        }

        // Grouped: "# Group Name" followed by items
        $lines = [];
        foreach ($data as $group => $items) {
            if (is_array($items)) {
                $lines[] = '# ' . $group;
                foreach ($items as $item) {
                    $lines[] = $item;
                }
                $lines[] = ''; // blank line between groups
            } else {
                // Flat item mixed in
                $lines[] = $items;
            }
        }

        return rtrim(implode("\n", $lines));
    }

    /**
     * Parse text editor content back to array.
     */
    protected function textToData(string $text, bool $grouped): array
    {
        $lines = array_filter(
            array_map('trim', explode("\n", $text)),
            fn ($line) => $line !== ''
        );

        if (!$grouped) {
            return array_values($lines);
        }

        // Parse grouped format
        $result = [];
        $currentGroup = null;

        foreach ($lines as $line) {
            if (str_starts_with($line, '# ')) {
                $currentGroup = trim(substr($line, 2));
                if (!isset($result[$currentGroup])) {
                    $result[$currentGroup] = [];
                }
            } elseif ($currentGroup !== null) {
                $result[$currentGroup][] = $line;
            }
        }

        return $result;
    }

    protected function countItems(array $data): int
    {
        $count = 0;
        foreach ($data as $item) {
            if (is_array($item)) {
                $count += count($item);
            } else {
                $count++;
            }
        }
        return $count;
    }
}
