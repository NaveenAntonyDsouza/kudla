{{--
    Phone bottom menu (Oct 2026): Home · Matches · Interests · Chats · Profile.
    94% of signed-in members use phones, and Interests / Chats were two taps
    away behind ☰. Shown below 768px only (the desktop top menu takes over
    from md). Badges: interests waiting for a reply, chats with something new
    (red, like the notification bell). Hidden while typing so it never sits
    over a form field, and on profile pages that have their own action bar.
    Inline styles: the live CSS build is not rebuilt on deploy.
--}}
@php
    $mnMe = auth()->user()?->profile;
    $mnPending = $mnMe ? \App\Support\Chats::pendingInterestsCount($mnMe) : 0;
    $mnUnread = $mnMe ? \App\Support\Chats::unreadCount($mnMe) : 0;
    $mnItems = [
        ['label' => 'Home', 'route' => 'dashboard', 'active' => ['dashboard'], 'badge' => 0,
         'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
        ['label' => 'Matches', 'route' => 'matches.index', 'active' => ['matches.*', 'search.*', 'discover.*'], 'badge' => 0,
         'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ['label' => 'Interests', 'route' => 'interests.inbox', 'params' => $mnPending ? ['tab' => 'received'] : [], 'active' => ['interests.*'], 'badge' => $mnPending,
         'icon' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
        ['label' => 'Chats', 'route' => 'chats.index', 'active' => ['chats.*'], 'badge' => $mnUnread,
         'icon' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z'],
        ['label' => 'Profile', 'route' => 'profile.show', 'active' => ['profile.show', 'profile.preview', 'photos.*', 'settings.*', 'membership.*'], 'badge' => 0,
         'icon' => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
@endphp

@if($mnMe)
    <nav class="mnav" aria-label="Main menu">
        @foreach($mnItems as $item)
            @php $on = request()->routeIs(...$item['active']); @endphp
            <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="mnav-item {{ $on ? 'mnav-on' : '' }}" @if($on) aria-current="page" @endif>
                <span class="mnav-icon">
                    <svg width="23" height="23" fill="{{ $on && $item['label'] === 'Interests' ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $on ? '2' : '1.6' }}" d="{{ $item['icon'] }}"/></svg>
                    @if($item['badge'])
                        <span class="mnav-badge" aria-label="{{ $item['badge'] }} new">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                    @endif
                </span>
                <span class="mnav-label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
    <div class="mnav-spacer" aria-hidden="true"></div>

    <style>
        .mnav, .mnav-spacer { display: none; }
        @media (max-width: 767px) {
            .mnav { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 44; background: #fff; border-top: 1px solid #e5e7eb; box-shadow: 0 -2px 10px rgba(0,0,0,.06); padding-bottom: env(safe-area-inset-bottom, 0px); }
            .mnav-spacer { display: block; height: calc(62px + env(safe-area-inset-bottom, 0px)); }
            .mnav-item { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; height: 60px; color: #6b7280; text-decoration: none; -webkit-tap-highlight-color: transparent; }
            .mnav-item:active { background: #f9fafb; }
            .mnav-on { color: var(--color-primary, #8B1D91); }
            .mnav-icon { position: relative; display: flex; }
            .mnav-label { font-size: 11px; font-weight: 500; line-height: 1.2; }
            .mnav-on .mnav-label { font-weight: 700; }
            .mnav-badge { position: absolute; top: -5px; left: 14px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9999px; background: #ef4444; color: #fff; font-size: 10.5px; font-weight: 700; line-height: 18px; text-align: center; box-shadow: 0 0 0 2px #fff; }
            /* the floating WhatsApp help button sits above the menu */
            .wa-fab { bottom: calc(76px + env(safe-area-inset-bottom, 0px)) !important; }
            /* typing: the on-screen keyboard pushes fixed bars up over the field */
            .mnav-typing .mnav { display: none; }
            .mnav-typing .mnav-spacer { display: none; }
        }
        @media print { .mnav, .mnav-spacer { display: none !important; } }
    </style>
    <script>
        (function () {
            var typing = function (el) { return el && (el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || (el.tagName === 'INPUT' && !/^(checkbox|radio|button|submit|reset|file|range|color|hidden)$/i.test(el.type)) || el.isContentEditable); };
            document.addEventListener('focusin', function (e) { if (typing(e.target)) document.documentElement.classList.add('mnav-typing'); });
            document.addEventListener('focusout', function () { setTimeout(function () { if (!typing(document.activeElement)) document.documentElement.classList.remove('mnav-typing'); }, 50); });
        })();
    </script>
@endif
