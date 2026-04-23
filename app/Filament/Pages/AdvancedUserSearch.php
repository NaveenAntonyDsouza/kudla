<?php

namespace App\Filament\Pages;

use App\Models\MembershipPlan;
use App\Models\Profile;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdvancedUserSearch extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Advanced Search';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.advanced-user-search';

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('advanced_search');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('advanced_search');
    }

    // Form state
    public ?string $keyword = null;
    public ?string $gender = null;
    public ?string $age_from = null;
    public ?string $age_to = null;
    public ?string $height_from = null;
    public ?string $height_to = null;
    public ?string $marital_status = null;
    public ?string $religion = null;
    public ?string $denomination = null;
    public ?string $caste = null;
    public ?string $mother_tongue = null;
    public ?string $native_country = null;
    public ?string $native_state = null;
    public ?string $native_district = null;
    public ?string $highest_education = null;
    public ?string $occupation = null;
    public ?string $annual_income = null;
    public ?string $employment_category = null;
    public ?string $diet = null;
    public ?string $smoking = null;
    public ?string $drinking = null;
    public ?string $physical_status = null;
    public ?string $family_status = null;
    public ?string $created_by = null;
    public ?string $membership_plan = null;
    public ?string $plan_status = null;
    public ?string $registered_from = null;
    public ?string $registered_to = null;
    public ?string $completion_range = null;
    public ?string $has_photo = null;
    public ?string $is_approved = null;
    public ?string $id_verified = null;
    public ?string $blood_group = null;

    public bool $searched = false;
    public Collection $results;

    public function mount(): void
    {
        $this->results = collect();
    }

    /**
     * Flatten a reference_data list that may be nested (grouped) into a flat key=>value array.
     */
    private static function flatOptions(string $configKey): array
    {
        $list = config('reference_data.' . $configKey, []);
        $flat = [];
        foreach ($list as $groupOrItem => $items) {
            if (is_array($items)) {
                foreach ($items as $item) $flat[$item] = $item;
            } else {
                $flat[$items] = $items;
            }
        }
        return $flat;
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Search Filters')
                ->columns(4)
                ->schema([
                    // Basic
                    Forms\Components\TextInput::make('keyword')
                        ->label('Keyword (Name, Matri ID, Email, Phone)')
                        ->placeholder('Search...')
                        ->columnSpan(2),

                    Forms\Components\Select::make('gender')
                        ->options(['male' => 'Male', 'female' => 'Female'])
                        ->placeholder('All'),

                    Forms\Components\Select::make('marital_status')
                        ->label('Marital Status')
                        ->options(['Unmarried' => 'Unmarried', 'Divorced' => 'Divorced', 'Widow/Widower' => 'Widow/Widower', 'Awaiting Divorce' => 'Awaiting Divorce', 'Annulled' => 'Annulled'])
                        ->placeholder('All'),

                    // Age & Height
                    Forms\Components\Select::make('age_from')->label('Age From')->options(array_combine(range(18, 70), range(18, 70)))->placeholder('-'),
                    Forms\Components\Select::make('age_to')->label('Age To')->options(array_combine(range(18, 70), range(18, 70)))->placeholder('-'),
                    Forms\Components\Select::make('height_from')->label('Height From')->options(fn () => self::flatOptions('height_list'))->placeholder('-'),
                    Forms\Components\Select::make('height_to')->label('Height To')->options(fn () => self::flatOptions('height_list'))->placeholder('-'),

                    // Religion
                    Forms\Components\Select::make('religion')
                        ->options(['Christian' => 'Christian', 'Hindu' => 'Hindu', 'Muslim' => 'Muslim', 'Jain' => 'Jain', 'No Religion' => 'No Religion', 'Other' => 'Other'])
                        ->placeholder('All'),
                    Forms\Components\Select::make('denomination')
                        ->options(fn () => self::flatOptions('denomination_list'))
                        ->searchable()
                        ->placeholder('All'),
                    Forms\Components\Select::make('caste')
                        ->options(fn () => array_combine(
                            \App\Models\Community::getCasteList(),
                            \App\Models\Community::getCasteList()
                        ))
                        ->searchable()
                        ->placeholder('All'),
                    Forms\Components\Select::make('mother_tongue')
                        ->label('Mother Tongue')
                        ->options(fn () => self::flatOptions('language_list'))
                        ->searchable()
                        ->placeholder('All'),

                    // Location
                    Forms\Components\Select::make('native_country')
                        ->label('Country')
                        ->options(fn () => self::flatOptions('country_list'))
                        ->searchable()
                        ->placeholder('All'),
                    Forms\Components\TextInput::make('native_state')->label('State')->placeholder('Any'),
                    Forms\Components\TextInput::make('native_district')->label('District')->placeholder('Any'),

                    // Education & Career
                    Forms\Components\Select::make('highest_education')
                        ->label('Education')
                        ->options(fn () => self::flatOptions('educational_qualifications_list'))
                        ->searchable()
                        ->placeholder('All'),
                    Forms\Components\Select::make('occupation')
                        ->options(fn () => self::flatOptions('occupation_category_list'))
                        ->searchable()
                        ->placeholder('All'),
                    Forms\Components\Select::make('annual_income')
                        ->label('Annual Income')
                        ->options(fn () => self::flatOptions('annual_income_list'))
                        ->placeholder('All'),
                    Forms\Components\TextInput::make('employment_category')
                        ->label('Employment Category')
                        ->placeholder('Any'),

                    // Lifestyle
                    Forms\Components\Select::make('diet')
                        ->options(fn () => self::flatOptions('eating_habits'))
                        ->placeholder('All'),
                    Forms\Components\Select::make('smoking')
                        ->options(fn () => self::flatOptions('smoking_habits'))
                        ->placeholder('All'),
                    Forms\Components\Select::make('drinking')
                        ->options(fn () => self::flatOptions('drinking_habits'))
                        ->placeholder('All'),
                    Forms\Components\Select::make('physical_status')
                        ->label('Physical Status')
                        ->options(['Normal' => 'Normal', 'Differently Abled' => 'Differently Abled'])
                        ->placeholder('All'),

                    // Family
                    Forms\Components\TextInput::make('family_status')->label('Family Status')->placeholder('Any'),
                    Forms\Components\Select::make('blood_group')
                        ->label('Blood Group')
                        ->options(['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'O+' => 'O+', 'O-' => 'O-', 'AB+' => 'AB+', 'AB-' => 'AB-'])
                        ->placeholder('All'),
                    Forms\Components\Select::make('created_by')
                        ->label('Profile By')
                        ->options(['self' => 'Self', 'parent' => 'Parent', 'sibling' => 'Sibling', 'relative' => 'Relative', 'friend' => 'Friend'])
                        ->placeholder('All'),

                    // Membership & Status
                    Forms\Components\Select::make('membership_plan')
                        ->label('Membership Plan')
                        ->options(fn () => ['free' => 'Free'] + MembershipPlan::where('is_active', true)->orderBy('sort_order')->pluck('plan_name', 'id')->toArray())
                        ->placeholder('All'),
                    Forms\Components\Select::make('is_approved')
                        ->label('Approved')
                        ->options(['1' => 'Yes', '0' => 'No'])
                        ->placeholder('All'),
                    Forms\Components\Select::make('id_verified')
                        ->label('ID Verified')
                        ->options(['1' => 'Yes', '0' => 'No'])
                        ->placeholder('All'),
                    Forms\Components\Select::make('has_photo')
                        ->label('Has Photo')
                        ->options(['1' => 'Yes', '0' => 'No'])
                        ->placeholder('All'),

                    Forms\Components\Select::make('completion_range')
                        ->label('Profile Completion')
                        ->options(['0-25' => '0-25%', '25-50' => '25-50%', '50-75' => '50-75%', '75-100' => '75-100%'])
                        ->placeholder('All'),

                    // Date range
                    Forms\Components\DatePicker::make('registered_from')->label('Registered From'),
                    Forms\Components\DatePicker::make('registered_to')->label('Registered To'),
                ]),
        ]);
    }

    public function search(): void
    {
        $query = Profile::query()
            ->whereNotNull('full_name')
            ->with(['user', 'religiousInfo', 'educationDetail', 'locationInfo', 'familyDetail', 'primaryPhoto', 'lifestyleInfo']);

        // Keyword
        if ($this->keyword) {
            $kw = $this->keyword;
            $query->where(function ($q) use ($kw) {
                $q->where('full_name', 'like', "%{$kw}%")
                    ->orWhere('matri_id', 'like', "%{$kw}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$kw}%")->orWhere('phone', 'like', "%{$kw}%"));
            });
        }

        // Basic filters
        if ($this->gender) $query->where('gender', $this->gender);
        if ($this->marital_status) $query->where('marital_status', $this->marital_status);
        if ($this->physical_status) $query->where('physical_status', $this->physical_status);
        if ($this->created_by) $query->where('created_by', $this->created_by);
        if ($this->mother_tongue) $query->where('mother_tongue', $this->mother_tongue);
        if ($this->blood_group) $query->where('blood_group', $this->blood_group);

        // Age range
        if ($this->age_from) $query->whereDate('date_of_birth', '<=', now()->subYears((int) $this->age_from));
        if ($this->age_to) $query->whereDate('date_of_birth', '>=', now()->subYears((int) $this->age_to + 1));

        // Height
        if ($this->height_from) $query->where('height', '>=', $this->height_from);
        if ($this->height_to) $query->where('height', '<=', $this->height_to);

        // Religion
        if ($this->religion) $query->whereHas('religiousInfo', fn ($q) => $q->where('religion', $this->religion));
        if ($this->denomination) $query->whereHas('religiousInfo', fn ($q) => $q->where('denomination', $this->denomination));
        if ($this->caste) $query->whereHas('religiousInfo', fn ($q) => $q->where('caste', $this->caste));

        // Location
        if ($this->native_country) $query->whereHas('locationInfo', fn ($q) => $q->where('native_country', $this->native_country));
        if ($this->native_state) $query->whereHas('locationInfo', fn ($q) => $q->where('native_state', 'like', "%{$this->native_state}%"));
        if ($this->native_district) $query->whereHas('locationInfo', fn ($q) => $q->where('native_district', 'like', "%{$this->native_district}%"));

        // Education
        if ($this->highest_education) $query->whereHas('educationDetail', fn ($q) => $q->where('highest_education', $this->highest_education));
        if ($this->occupation) $query->whereHas('educationDetail', fn ($q) => $q->where('occupation', $this->occupation));
        if ($this->annual_income) $query->whereHas('educationDetail', fn ($q) => $q->where('annual_income', $this->annual_income));
        if ($this->employment_category) $query->whereHas('educationDetail', fn ($q) => $q->where('employment_category', 'like', "%{$this->employment_category}%"));

        // Lifestyle
        if ($this->diet) $query->whereHas('lifestyleInfo', fn ($q) => $q->where('diet', $this->diet));
        if ($this->smoking) $query->whereHas('lifestyleInfo', fn ($q) => $q->where('smoking', $this->smoking));
        if ($this->drinking) $query->whereHas('lifestyleInfo', fn ($q) => $q->where('drinking', $this->drinking));

        // Family
        if ($this->family_status) $query->whereHas('familyDetail', fn ($q) => $q->where('family_status', 'like', "%{$this->family_status}%"));

        // Status
        if ($this->is_approved !== null && $this->is_approved !== '') $query->where('is_approved', (bool) $this->is_approved);
        if ($this->id_verified !== null && $this->id_verified !== '') $query->where('id_proof_verified', (bool) $this->id_verified);
        if ($this->has_photo === '1') $query->whereHas('primaryPhoto');
        if ($this->has_photo === '0') $query->whereDoesntHave('primaryPhoto');

        // Completion
        if ($this->completion_range) {
            [$min, $max] = explode('-', $this->completion_range);
            $query->whereBetween('profile_completion_pct', [(int) $min, (int) $max]);
        }

        // Membership
        if ($this->membership_plan) {
            if ($this->membership_plan === 'free') {
                $query->whereDoesntHave('user.userMemberships', fn ($q) => $q->where('is_active', true)->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now())));
            } else {
                $query->whereHas('user.userMemberships', fn ($q) => $q->where('plan_id', $this->membership_plan)->where('is_active', true)->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now())));
            }
        }

        // Date range
        if ($this->registered_from) $query->whereDate('created_at', '>=', $this->registered_from);
        if ($this->registered_to) $query->whereDate('created_at', '<=', $this->registered_to);

        $this->results = $query->orderBy('created_at', 'desc')->limit(100)->get();
        $this->searched = true;
    }

    public function resetFilters(): void
    {
        $this->reset([
            'keyword', 'gender', 'age_from', 'age_to', 'height_from', 'height_to',
            'marital_status', 'religion', 'denomination', 'caste', 'mother_tongue',
            'native_country', 'native_state', 'native_district', 'highest_education',
            'occupation', 'annual_income', 'employment_category', 'diet', 'smoking',
            'drinking', 'physical_status', 'family_status', 'created_by', 'membership_plan',
            'plan_status', 'registered_from', 'registered_to', 'completion_range',
            'has_photo', 'is_approved', 'id_verified', 'blood_group', 'searched',
        ]);
        $this->results = collect();
    }
}
