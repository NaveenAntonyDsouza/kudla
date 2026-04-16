<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MatchWeightConfig extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Match Weights';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Match Weight Configuration';
    protected string $view = 'filament.pages.match-weight-config';

    public ?array $data = [];

    private const DEFAULTS = [
        'religion' => 15,
        'age' => 15,
        'denomination' => 10,
        'mother_tongue' => 10,
        'education' => 10,
        'occupation' => 10,
        'height' => 8,
        'native_location' => 8,
        'working_location' => 5,
        'marital_status' => 5,
        'diet' => 2,
        'family_status' => 2,
    ];

    private const LABELS = [
        'religion' => 'Religion',
        'age' => 'Age Range',
        'denomination' => 'Denomination / Caste',
        'mother_tongue' => 'Mother Tongue',
        'education' => 'Education',
        'occupation' => 'Occupation',
        'height' => 'Height Range',
        'native_location' => 'Native Location',
        'working_location' => 'Working Location',
        'marital_status' => 'Marital Status',
        'diet' => 'Diet / Eating Habit',
        'family_status' => 'Family Status',
    ];

    public function mount(): void
    {
        $saved = json_decode(SiteSetting::getValue('match_weights', '{}'), true) ?: [];
        $weights = array_merge(self::DEFAULTS, $saved);

        $this->form->fill($weights);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        $fields = [];
        foreach (self::LABELS as $key => $label) {
            $fields[] = Forms\Components\TextInput::make($key)
                ->label($label)
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->default(self::DEFAULTS[$key]);
        }

        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Match Algorithm Weights')
                    ->description('Adjust how much each criteria contributes to the match score. Values should add up to 100.')
                    ->schema($fields)
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Calculate total
        $total = array_sum(array_map('intval', $data));

        if ($total !== 100) {
            Notification::make()
                ->title("Weights must add up to 100%. Current total: {$total}%")
                ->danger()
                ->send();

            return;
        }

        SiteSetting::setValue('match_weights', json_encode($data));

        Notification::make()
            ->title('Match weights saved successfully')
            ->success()
            ->send();
    }

    public function resetToDefaults(): void
    {
        $this->form->fill(self::DEFAULTS);

        Notification::make()
            ->title('Weights reset to defaults. Click "Save" to apply.')
            ->info()
            ->send();
    }
}
