@php
    $theme = \App\Models\ThemeSetting::getTheme();
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Perfect Match');
    $isLoggedIn = auth()->check();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? '' }} | {{ $siteName }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <!-- White-label theme colors injected from database -->
    <style>
        :root {
            --brand-primary: {{ $theme->primary_color ?? '#8B1D91' }};
            --brand-primary-hover: {{ $theme->primary_hover ?? '#6B1571' }};
            --brand-primary-light: {{ $theme->primary_light ?? '#F3E8F7' }};
            --brand-secondary: {{ $theme->secondary_color ?? '#00BCD4' }};
            --brand-secondary-hover: {{ $theme->secondary_hover ?? '#00ACC1' }};
            --brand-secondary-light: {{ $theme->secondary_light ?? '#E0F7FA' }};
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#FEFCFB] text-[#1C1917] font-sans antialiased">

    @if($isLoggedIn)
        {{-- ══ LOGGED-IN HEADER ══ --}}
        <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <a href="/dashboard" class="flex items-center gap-2">
                        @if($theme->logo_url ?? false)
                            <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" class="h-10">
                        @else
                            <span class="text-xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</span>
                        @endif
                    </a>

                    <!-- Desktop Nav with Dropdowns -->
                    <nav class="hidden md:flex items-center gap-1">
                        {{-- My Home --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50">
                                My Home
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Home</a>
                                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View & Edit Profile</a>
                                <a href="{{ route('photos.manage') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Manage Photos</a>
                                <a href="{{ route('idproof.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Submit ID Proof</a>
                                <a href="{{ route('membership.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Membership Plans</a>
                                <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Settings</a>
                            </div>
                        </div>

                        {{-- Search --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50">
                                Search
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('search.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Partner Search</a>
                                <a href="{{ route('search.index', ['tab' => 'keyword']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Keyword Search</a>
                                <a href="{{ route('search.index', ['tab' => 'byid']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Search by ID</a>
                            </div>
                        </div>

                        {{-- Matches --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50">
                                Matches
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('matches.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Matches</a>
                                <a href="{{ route('matches.mutual') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Mutual Matches</a>
                            </div>
                        </div>

                        {{-- Messages --}}
                        <a href="{{ route('interests.inbox') }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50">Messages</a>

                        {{-- Activity --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button @click="open = !open" class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-50">
                                Activity
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('views.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Views</a>
                                <a href="{{ route('shortlist.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Shortlisted Profiles</a>
                                <a href="{{ route('blocked.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Blocked Profiles</a>
                            </div>
                        </div>
                    </nav>

                    <!-- Right side: Notification bell + Avatar dropdown -->
                    <div class="flex items-center gap-4">
                        <!-- Notification bell -->
                        <div x-data="{ notifOpen: false }" @click.outside="notifOpen = false" class="relative">
                            <button @click="notifOpen = !notifOpen" class="relative p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                @if(($unreadNotificationCount ?? 0) > 0)
                                    <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white rounded-full bg-red-500">{{ $unreadNotificationCount }}</span>
                                @endif
                            </button>

                            {{-- Notification dropdown --}}
                            <div x-show="notifOpen" x-cloak class="absolute right-0 mt-2 w-[calc(100vw-2rem)] sm:w-80 md:w-96 max-w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50 overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Notifications ({{ $unreadNotificationCount ?? 0 }})</h3>
                                    <div class="flex items-center gap-2">
                                        @if(($unreadNotificationCount ?? 0) > 0)
                                            <form method="POST" action="{{ route('notifications.readAll') }}">
                                                @csrf
                                                <button type="submit" class="text-xs text-(--color-primary) hover:underline font-medium">Mark all read</button>
                                            </form>
                                        @endif
                                        <button @click="notifOpen = false" class="p-1 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="max-h-96 overflow-y-auto">
                                    @php $notifs = $recentNotifications ?? ['today' => collect(), 'yesterday' => collect(), 'previous' => collect()]; @endphp
                                    @php $hasAny = $notifs['today']->count() + $notifs['yesterday']->count() + $notifs['previous']->count(); @endphp

                                    @if($hasAny > 0)
                                        @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'previous' => 'Previous'] as $key => $label)
                                            @if($notifs[$key]->count() > 0)
                                                <p class="px-4 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $label }}</p>
                                                @foreach($notifs[$key] as $notif)
                                                    <form method="POST" action="{{ route('notifications.read', $notif) }}">
                                                        @csrf
                                                        <button type="submit" class="w-full flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors text-left {{ !$notif->is_read ? 'bg-(--color-primary-light)/30' : '' }}">
                                                            <div class="w-8 h-8 rounded-full shrink-0 mt-0.5 flex items-center justify-center {{ match($notif->type) { 'interest_received' => 'bg-(--color-primary-light) text-(--color-primary)', 'interest_accepted' => 'bg-green-100 text-green-600', 'interest_declined' => 'bg-red-100 text-red-500', default => 'bg-gray-100 text-gray-400' } }}">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75"/></svg>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-sm font-medium text-gray-900 {{ !$notif->is_read ? 'font-semibold' : '' }}">{{ $notif->title }}</p>
                                                                <p class="text-xs text-gray-600 truncate">{{ $notif->message }}</p>
                                                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $notif->created_at->format('d/m/Y h:i A') }}</p>
                                                            </div>
                                                            @if(!$notif->is_read)
                                                                <div class="w-2 h-2 rounded-full bg-(--color-primary) shrink-0 mt-2"></div>
                                                            @endif
                                                        </button>
                                                    </form>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @else
                                        <div class="p-6 text-center">
                                            <p class="text-sm text-gray-400">No notifications yet</p>
                                        </div>
                                    @endif
                                </div>

                                <a href="{{ route('notifications.index') }}" class="block text-center text-xs text-(--color-primary) font-medium py-2.5 border-t border-gray-100 hover:bg-gray-50">
                                    View All Notifications
                                </a>
                            </div>
                        </div>

                        <!-- Avatar dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button x-on:click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100">
                                <div class="w-8 h-8 rounded-full bg-(--color-primary-light) flex items-center justify-center">
                                    <svg class="w-5 h-5 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" x-on:click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-sm border border-gray-200 py-1 z-50">
                                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                                <a href="{{ route('photos.manage') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Manage Photos</a>
                                <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <a href="{{ route('shortlist.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Shortlist</a>
                                <a href="{{ route('views.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Views</a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <form method="POST" action="/logout">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</button>
                                </form>
                            </div>
                        </div>

                        <!-- Mobile hamburger -->
                        <button x-data x-on:click="$dispatch('toggle-mobile-menu')" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu (logged-in) -->
            <div x-data="{ open: false }" x-on:toggle-mobile-menu.window="open = !open" x-show="open" x-cloak class="md:hidden border-t border-gray-200 bg-white">
                <div class="px-4 py-3 space-y-1">
                    {{-- My Home --}}
                    <p class="px-3 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">My Home</p>
                    <a href="/dashboard" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Dashboard</a>
                    <a href="{{ route('profile.show') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">View & Edit Profile</a>
                    <a href="{{ route('photos.manage') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Manage Photos</a>
                    <a href="{{ route('idproof.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Submit ID Proof</a>
                    <a href="{{ route('membership.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Membership Plans</a>
                    <a href="{{ route('settings.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Profile Settings</a>

                    {{-- Search --}}
                    <div class="border-t border-gray-100 my-1"></div>
                    <p class="px-3 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Search</p>
                    <a href="{{ route('search.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Partner Search</a>
                    <a href="{{ route('search.index', ['tab' => 'keyword']) }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Keyword Search</a>
                    <a href="{{ route('search.index', ['tab' => 'byid']) }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Search by ID</a>

                    {{-- Matches --}}
                    <div class="border-t border-gray-100 my-1"></div>
                    <p class="px-3 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Matches</p>
                    <a href="{{ route('matches.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">My Matches</a>
                    <a href="{{ route('matches.mutual') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Mutual Matches</a>

                    {{-- Messages --}}
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="{{ route('interests.inbox') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Messages</a>

                    {{-- Activity --}}
                    <div class="border-t border-gray-100 my-1"></div>
                    <p class="px-3 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Activity</p>
                    <a href="{{ route('views.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Profile Views</a>
                    <a href="{{ route('shortlist.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Shortlisted Profiles</a>
                    <a href="{{ route('blocked.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Blocked Profiles</a>

                    {{-- Logout --}}
                    <div class="border-t border-gray-100 my-1"></div>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50">Logout</button>
                    </form>
                </div>
            </div>
        </header>
    @else
        {{-- ══ NON-LOGGED-IN HEADER ══ --}}
        <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <a href="/" class="flex items-center gap-2">
                        @if($theme->logo_url ?? false)
                            <img src="{{ $theme->logo_url }}" alt="{{ $siteName }}" class="h-10">
                        @else
                            <span class="text-xl font-serif font-bold text-(--color-primary)">{{ $siteName }}</span>
                        @endif
                    </a>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center gap-6">
                        <a href="/" class="text-sm font-medium text-gray-700 hover:text-gray-900">Home</a>
                        <a href="/register" class="text-sm font-medium text-(--color-primary) hover:text-(--color-primary-hover)">Register Free</a>
                        <a href="/login" class="px-5 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">Login</a>
                    </nav>

                    <!-- Mobile hamburger -->
                    <button x-data x-on:click="$dispatch('toggle-guest-menu')" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu (non-logged-in) -->
            <div x-data="{ open: false }" x-on:toggle-guest-menu.window="open = !open" x-show="open" x-cloak class="md:hidden border-t border-gray-200 bg-white">
                <div class="px-4 py-3 space-y-2">
                    <a href="/" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Home</a>
                    <a href="/register" class="block px-3 py-2 rounded-lg text-sm font-medium text-(--color-primary) hover:bg-gray-100">Register Free</a>
                    <a href="/login" class="block px-3 py-2 rounded-lg text-sm font-medium text-white bg-(--color-primary) text-center rounded-lg">Login</a>
                </div>
            </div>
        </header>
    @endif

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    @if($isLoggedIn)
        {{-- ══ LOGGED-IN FOOTER ══ --}}
        <footer class="bg-gray-900 text-gray-400 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                    <div class="flex items-center gap-4 text-sm">
                        <a href="/privacy-policy" class="hover:text-white transition-colors">Privacy</a>
                        <a href="/terms-of-service" class="hover:text-white transition-colors">Terms</a>
                        <a href="/help" class="hover:text-white transition-colors">Help</a>
                    </div>
                </div>
            </div>
        </footer>
    @else
        {{-- ══ NON-LOGGED-IN FOOTER ══ --}}
        <footer class="bg-gray-900 text-gray-400 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                    <div>
                        <h3 class="text-white font-semibold text-sm mb-3">{{ $siteName }}</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">{{ $siteTagline }}. A trusted matrimony platform for families seeking meaningful connections.</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-3">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/" class="hover:text-white transition-colors">Home</a></li>
                            <li><a href="/register" class="hover:text-white transition-colors">Register Free</a></li>
                            <li><a href="/login" class="hover:text-white transition-colors">Login</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-3">Legal</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/privacy-policy" class="hover:text-white transition-colors">Privacy Policy</a></li>
                            <li><a href="/terms-of-service" class="hover:text-white transition-colors">Terms of Service</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-3">Contact</h4>
                        <ul class="space-y-2 text-sm">
                            <li>info@anugrahamatrimony.com</li>
                            <li><a href="/help" class="hover:text-white transition-colors">Help & Support</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 pt-6 text-center">
                    <p class="text-xs">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                </div>
            </div>
        </footer>
    @endif

    @livewireScripts
</body>
</html>
