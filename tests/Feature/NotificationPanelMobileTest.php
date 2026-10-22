<?php

/*
| Notification panel on phones (Oct 2026): the bell sits mid-header, so the
| panel pinned to it ran 124 px off the left edge of a 375 px screen. Below
| 640 px it is pinned to the screen under the header; messages show 2 lines.
*/

it('pins the notification panel to the screen on phones and shows two lines per message', function () {
    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));

    expect($layout)->toContain('class="notif-panel absolute right-0')
        ->toContain('.notif-panel { position: fixed !important; left: 12px !important; right: 12px !important;')
        ->toContain('@media (max-width: 639px)')
        ->toContain('<p class="notif-msg text-xs text-gray-600">{{ $notif->message }}</p>')
        ->toContain('-webkit-line-clamp: 2')
        ->not->toContain('<p class="text-xs text-gray-600 truncate">{{ $notif->message }}</p>');
});
