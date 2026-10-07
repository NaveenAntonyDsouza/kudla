<?php

/*
| On phones the settings sidebar is hidden (lg:block), so members couldn't
| reach Hide / Delete profile or Change Password — several contact messages
| asked how to delete. A chip menu shows the same sections below 1024px.
*/

it('offers every settings section on phones, including hide and delete', function () {
    $view = file_get_contents(resource_path('views/settings/index.blade.php'));

    expect($view)->toContain('class="settings-mobile-nav"')
        ->toContain('@media (min-width:1024px){.settings-mobile-nav{display:none}}');

    foreach (['search_visibility', 'manage_alerts', 'hide_profile', 'delete_profile', 'change_password'] as $section) {
        expect($view)->toMatch("/mobileSections = \[[^\]]*'{$section}'/");
    }
});
