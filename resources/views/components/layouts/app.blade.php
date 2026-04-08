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
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="{{ route('discover.hub') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Discover Profiles</a>
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
                    <a href="{{ route('discover.hub') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Discover Profiles</a>

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
                        <a href="/membership-plans" class="text-sm font-medium text-gray-700 hover:text-gray-900">Membership</a>
                        <a href="/about-us" class="text-sm font-medium text-gray-700 hover:text-gray-900">About Us</a>
                        <a href="/contact" class="text-sm font-medium text-gray-700 hover:text-gray-900">Contact</a>
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
                    <a href="/membership-plans" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Membership</a>
                    <a href="/about-us" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">About Us</a>
                    <a href="/contact" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Contact</a>
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
                        <a href="/terms-condition" class="hover:text-white transition-colors">Terms</a>
                        <a href="/faq" class="hover:text-white transition-colors">Help</a>
                        @php
                            $socialFb = \App\Models\SiteSetting::getValue('social_facebook');
                            $socialIg = \App\Models\SiteSetting::getValue('social_instagram');
                            $socialYt = \App\Models\SiteSetting::getValue('social_youtube');
                            $socialTw = \App\Models\SiteSetting::getValue('social_twitter');
                        @endphp
                        @if($socialFb || $socialIg || $socialYt || $socialTw)
                            <span class="text-gray-700">|</span>
                            @if($socialFb)<a href="{{ $socialFb }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>@endif
                            @if($socialIg)<a href="{{ $socialIg }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>@endif
                            @if($socialYt)<a href="{{ $socialYt }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>@endif
                            @if($socialTw)<a href="{{ $socialTw }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>@endif
                        @endif
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
                            <li><a href="/about-us" class="hover:text-white transition-colors">About Us</a></li>
                            <li><a href="/membership-plans" class="hover:text-white transition-colors">Membership Plans</a></li>
                            <li><a href="/demograph" class="hover:text-white transition-colors">Demographics</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-3">Information</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/privacy-policy" class="hover:text-white transition-colors">Privacy Policy</a></li>
                            <li><a href="/terms-condition" class="hover:text-white transition-colors">Terms & Conditions</a></li>
                            <li><a href="/refund-policy" class="hover:text-white transition-colors">Refund Policy</a></li>
                            <li><a href="/child-safety" class="hover:text-white transition-colors">Child Safety</a></li>
                            <li><a href="/report-misuse" class="hover:text-white transition-colors">Report Misuse</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-3">Reach Us</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/contact" class="hover:text-white transition-colors">Contact Us</a></li>
                            <li><a href="/faq" class="hover:text-white transition-colors">Help & FAQ</a></li>
                            <li><a href="/blog" class="hover:text-white transition-colors">Blog</a></li>
                            <li><a href="/event" class="hover:text-white transition-colors">Events</a></li>
                            <li><a href="/add-with-us" class="hover:text-white transition-colors">Advertise With Us</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                    @php
                        $sFb = \App\Models\SiteSetting::getValue('social_facebook');
                        $sIg = \App\Models\SiteSetting::getValue('social_instagram');
                        $sYt = \App\Models\SiteSetting::getValue('social_youtube');
                        $sTw = \App\Models\SiteSetting::getValue('social_twitter');
                    @endphp
                    @if($sFb || $sIg || $sYt || $sTw)
                        <div class="flex items-center gap-4">
                            @if($sFb)<a href="{{ $sFb }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>@endif
                            @if($sIg)<a href="{{ $sIg }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>@endif
                            @if($sYt)<a href="{{ $sYt }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>@endif
                            @if($sTw)<a href="{{ $sTw }}" target="_blank" class="hover:text-white transition-colors"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>@endif
                        </div>
                    @endif
                </div>
            </div>
        </footer>
    @endif

    @livewireScripts
</body>
</html>
