<?php

// Crasta, Nov 2026: console "New Profile" saved 47 members switched off because an untouched
// Filament Toggle sends false. The Status switches must start on / at the auto-approve setting.
it('starts the New Profile Active and Approved switches on', function () {
    $src = file_get_contents(__DIR__.'/../../app/Filament/Resources/UserResource.php');
    expect($src)->toContain("Toggle::make('is_active')->label('Active')->default(true)")
        ->and($src)->toContain("Toggle::make('is_approved')->label('Approved')")
        ->and($src)->toContain("auto_approve_profiles");

    $page = file_get_contents(__DIR__.'/../../app/Filament/Resources/UserResource/Pages/CreateUser.php');
    expect($page)->toContain("auto_approve_profiles")->and($page)->toContain("'created_by_staff_id' => auth()->id()");
});
