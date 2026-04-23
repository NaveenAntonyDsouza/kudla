<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\ReferenceDataService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class HoroscopeConfig extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Horoscope Matching';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Horoscope Compatibility Configuration';
    protected string $view = 'filament.pages.horoscope-config';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_site_settings');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_site_settings');
    }

    /**
     * Default nakshatra compatibility chart.
     * Based on simplified traditional Vedic astrology pairings.
     * Format: nakshatra => [compatible nakshatras]
     * Any pair NOT listed defaults to "neutral" (partial match).
     */
    public static function defaultCompatibility(): array
    {
        return [
            'Ashwini (Aswathy)' => ['Bharani', 'Rohini', 'Pushya (Pooyam)', 'Ashlesha (Ayilyam)', 'Magha (Makam)', 'Shravana (Thiruvonam)', 'Revati'],
            'Bharani' => ['Ashwini (Aswathy)', 'Rohini', 'Pushya (Pooyam)', 'Magha (Makam)', 'Purva Phalguni (Pooram)', 'Shravana (Thiruvonam)'],
            'Krittika (Karthika)' => ['Rohini', 'Mrigashira (Makayiram)', 'Pushya (Pooyam)', 'Uttara Phalguni (Uthram)', 'Hasta (Atham)'],
            'Rohini' => ['Ashwini (Aswathy)', 'Bharani', 'Krittika (Karthika)', 'Mrigashira (Makayiram)', 'Uttara Phalguni (Uthram)', 'Hasta (Atham)', 'Shravana (Thiruvonam)'],
            'Mrigashira (Makayiram)' => ['Krittika (Karthika)', 'Rohini', 'Ardra (Thiruvathira)', 'Hasta (Atham)', 'Chitra (Chithira)', 'Dhanishta (Avittam)'],
            'Ardra (Thiruvathira)' => ['Mrigashira (Makayiram)', 'Punarvasu (Punartham)', 'Pushya (Pooyam)', 'Swati (Chothi)', 'Shatabhisha (Chathayam)'],
            'Punarvasu (Punartham)' => ['Ardra (Thiruvathira)', 'Pushya (Pooyam)', 'Ashlesha (Ayilyam)', 'Hasta (Atham)', 'Vishakha (Vishakam)', 'Revati'],
            'Pushya (Pooyam)' => ['Ashwini (Aswathy)', 'Bharani', 'Krittika (Karthika)', 'Ardra (Thiruvathira)', 'Punarvasu (Punartham)', 'Shravana (Thiruvonam)', 'Revati'],
            'Ashlesha (Ayilyam)' => ['Ashwini (Aswathy)', 'Punarvasu (Punartham)', 'Magha (Makam)', 'Jyeshtha (Thrikketta)', 'Mula (Moolam)'],
            'Magha (Makam)' => ['Ashwini (Aswathy)', 'Bharani', 'Ashlesha (Ayilyam)', 'Purva Phalguni (Pooram)', 'Uttara Phalguni (Uthram)'],
            'Purva Phalguni (Pooram)' => ['Bharani', 'Magha (Makam)', 'Uttara Phalguni (Uthram)', 'Hasta (Atham)', 'Chitra (Chithira)'],
            'Uttara Phalguni (Uthram)' => ['Krittika (Karthika)', 'Rohini', 'Magha (Makam)', 'Purva Phalguni (Pooram)', 'Hasta (Atham)', 'Swati (Chothi)'],
            'Hasta (Atham)' => ['Krittika (Karthika)', 'Rohini', 'Mrigashira (Makayiram)', 'Punarvasu (Punartham)', 'Purva Phalguni (Pooram)', 'Uttara Phalguni (Uthram)', 'Chitra (Chithira)'],
            'Chitra (Chithira)' => ['Mrigashira (Makayiram)', 'Purva Phalguni (Pooram)', 'Hasta (Atham)', 'Swati (Chothi)', 'Vishakha (Vishakam)'],
            'Swati (Chothi)' => ['Ardra (Thiruvathira)', 'Uttara Phalguni (Uthram)', 'Chitra (Chithira)', 'Vishakha (Vishakam)', 'Anuradha (Anizham)', 'Shatabhisha (Chathayam)'],
            'Vishakha (Vishakam)' => ['Punarvasu (Punartham)', 'Chitra (Chithira)', 'Swati (Chothi)', 'Anuradha (Anizham)', 'Purva Ashadha (Pooradam)'],
            'Anuradha (Anizham)' => ['Swati (Chothi)', 'Vishakha (Vishakam)', 'Jyeshtha (Thrikketta)', 'Mula (Moolam)', 'Uttara Ashadha (Uthradam)'],
            'Jyeshtha (Thrikketta)' => ['Ashlesha (Ayilyam)', 'Anuradha (Anizham)', 'Mula (Moolam)', 'Purva Ashadha (Pooradam)'],
            'Mula (Moolam)' => ['Ashlesha (Ayilyam)', 'Anuradha (Anizham)', 'Jyeshtha (Thrikketta)', 'Purva Ashadha (Pooradam)', 'Uttara Ashadha (Uthradam)'],
            'Purva Ashadha (Pooradam)' => ['Vishakha (Vishakam)', 'Jyeshtha (Thrikketta)', 'Mula (Moolam)', 'Uttara Ashadha (Uthradam)', 'Shravana (Thiruvonam)'],
            'Uttara Ashadha (Uthradam)' => ['Anuradha (Anizham)', 'Mula (Moolam)', 'Purva Ashadha (Pooradam)', 'Shravana (Thiruvonam)', 'Dhanishta (Avittam)'],
            'Shravana (Thiruvonam)' => ['Ashwini (Aswathy)', 'Bharani', 'Rohini', 'Pushya (Pooyam)', 'Purva Ashadha (Pooradam)', 'Uttara Ashadha (Uthradam)', 'Dhanishta (Avittam)', 'Revati'],
            'Dhanishta (Avittam)' => ['Mrigashira (Makayiram)', 'Uttara Ashadha (Uthradam)', 'Shravana (Thiruvonam)', 'Shatabhisha (Chathayam)', 'Purva Bhadrapada (Pooruruttathi)'],
            'Shatabhisha (Chathayam)' => ['Ardra (Thiruvathira)', 'Swati (Chothi)', 'Dhanishta (Avittam)', 'Purva Bhadrapada (Pooruruttathi)', 'Uttara Bhadrapada (Uthruttathi)'],
            'Purva Bhadrapada (Pooruruttathi)' => ['Dhanishta (Avittam)', 'Shatabhisha (Chathayam)', 'Uttara Bhadrapada (Uthruttathi)', 'Revati'],
            'Uttara Bhadrapada (Uthruttathi)' => ['Shatabhisha (Chathayam)', 'Purva Bhadrapada (Pooruruttathi)', 'Revati'],
            'Revati' => ['Ashwini (Aswathy)', 'Punarvasu (Punartham)', 'Pushya (Pooyam)', 'Shravana (Thiruvonam)', 'Purva Bhadrapada (Pooruruttathi)', 'Uttara Bhadrapada (Uthruttathi)'],
        ];
    }

    public function mount(): void
    {
        $saved = json_decode(SiteSetting::getValue('horoscope_compatibility', '{}'), true);
        $nakshatras = config('reference_data.nakshatra_list', []);

        $matrix = [];
        $savedMatrix = $saved['nakshatra_matrix'] ?? self::defaultCompatibility();

        foreach ($nakshatras as $nak) {
            $matrix[] = [
                'nakshatra' => $nak,
                'compatible' => $savedMatrix[$nak] ?? [],
            ];
        }

        $this->form->fill([
            'horoscope_enabled' => $saved['horoscope_enabled'] ?? '1',
            'nakshatra_matrix' => $matrix,
        ]);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        $nakshatraOptions = collect(config('reference_data.nakshatra_list', []))
            ->mapWithKeys(fn ($n) => [$n => $n])
            ->toArray();

        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Horoscope Matching')
                    ->description('Configure Nakshatra (birth star) compatibility for Hindu users. When enabled, match scores include horoscope compatibility. Set the weight in Match Weights page.')
                    ->schema([
                        Forms\Components\Toggle::make('horoscope_enabled')
                            ->label('Enable Horoscope Matching')
                            ->helperText('When enabled and horoscope weight is > 0 in Match Weights, compatibility is factored into match scores.'),
                    ]),

                \Filament\Schemas\Components\Section::make('Nakshatra Compatibility Chart')
                    ->description('For each Nakshatra, select which other Nakshatras are compatible. Any pair not listed is treated as neutral (partial match).')
                    ->schema([
                        Forms\Components\Repeater::make('nakshatra_matrix')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('nakshatra')
                                    ->label('Nakshatra')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Select::make('compatible')
                                    ->label('Compatible With')
                                    ->multiple()
                                    ->options($nakshatraOptions)
                                    ->searchable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['nakshatra'] ?? null),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Build the matrix as nakshatra => [compatible list]
        $matrix = [];
        foreach ($data['nakshatra_matrix'] ?? [] as $entry) {
            $matrix[$entry['nakshatra']] = $entry['compatible'] ?? [];
        }

        $config = [
            'horoscope_enabled' => $data['horoscope_enabled'] ? '1' : '0',
            'nakshatra_matrix' => $matrix,
        ];

        SiteSetting::setValue('horoscope_compatibility', json_encode($config, JSON_UNESCAPED_UNICODE));

        Notification::make()
            ->title('Horoscope compatibility saved successfully')
            ->success()
            ->send();
    }

    public function resetToDefaults(): void
    {
        $nakshatras = config('reference_data.nakshatra_list', []);
        $defaults = self::defaultCompatibility();

        $matrix = [];
        foreach ($nakshatras as $nak) {
            $matrix[] = [
                'nakshatra' => $nak,
                'compatible' => $defaults[$nak] ?? [],
            ];
        }

        $this->form->fill([
            'horoscope_enabled' => '1',
            'nakshatra_matrix' => $matrix,
        ]);

        Notification::make()
            ->title('Reset to default compatibility chart. Click "Save" to apply.')
            ->info()
            ->send();
    }
}
