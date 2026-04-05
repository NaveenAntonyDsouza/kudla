<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\EducationDetail;
use App\Models\LocationInfo;
use App\Models\ReligiousInfo;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('toggleActive')
                ->label(fn() => $this->record->is_active ? 'Deactivate User' : 'Activate User')
                ->color(fn() => $this->record->is_active ? 'danger' : 'success')
                ->icon(fn() => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    $this->refreshFormData(['is_active']);
                }),
        ];
    }

    /**
     * Load related data into form fields.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $profile = $this->record;

        // Religious info
        $rel = $profile->religiousInfo;
        $data['rel_religion'] = $rel?->religion;
        $data['rel_denomination'] = $rel?->denomination;
        $data['rel_caste'] = $rel?->caste;

        // Education
        $edu = $profile->educationDetail;
        $data['edu_highest_education'] = $edu?->highest_education;
        $data['edu_occupation'] = $edu?->occupation;
        $data['edu_employer_name'] = $edu?->employer_name;
        $data['edu_annual_income'] = $edu?->annual_income;
        $data['edu_working_country'] = $edu?->working_country;

        // Location
        $loc = $profile->locationInfo;
        $data['loc_residing_country'] = $loc?->residing_country;
        $data['loc_native_country'] = $loc?->native_country;
        $data['loc_native_state'] = $loc?->native_state;
        $data['loc_native_district'] = $loc?->native_district;

        // User contact
        $user = $profile->user;
        $data['user_email'] = $user?->email;
        $data['user_phone'] = $user?->phone;

        return $data;
    }

    /**
     * Save related data from form fields.
     */
    protected function afterSave(): void
    {
        $data = $this->form->getState();
        $profile = $this->record;

        // Update religious info
        ReligiousInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'religion' => $data['rel_religion'] ?? null,
                'denomination' => $data['rel_denomination'] ?? null,
                'caste' => $data['rel_caste'] ?? null,
            ]
        );

        // Update education
        EducationDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'highest_education' => $data['edu_highest_education'] ?? null,
                'occupation' => $data['edu_occupation'] ?? null,
                'employer_name' => $data['edu_employer_name'] ?? null,
                'annual_income' => $data['edu_annual_income'] ?? null,
                'working_country' => $data['edu_working_country'] ?? null,
            ]
        );

        // Update location
        LocationInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'residing_country' => $data['loc_residing_country'] ?? null,
                'native_country' => $data['loc_native_country'] ?? null,
                'native_state' => $data['loc_native_state'] ?? null,
                'native_district' => $data['loc_native_district'] ?? null,
            ]
        );

        // Update user email/phone
        $profile->user->update([
            'email' => $data['user_email'] ?? $profile->user->email,
            'phone' => $data['user_phone'] ?? $profile->user->phone,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
