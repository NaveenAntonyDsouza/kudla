<x-layouts.app title="View & Edit My Profile">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">View & Edit My Profile</span>
        </p>

        @if (session('success'))
            <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">

            {{-- ══ LEFT SIDEBAR ══ --}}
            <div class="lg:w-72 shrink-0">
                <div class="sticky top-24 space-y-4">
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
                        {{-- Photo --}}
                        <div class="bg-gradient-to-br from-(--color-primary) to-(--color-primary)/70 px-6 py-8 text-center">
                            @if($profile->primaryPhoto)
                                <div class="w-24 h-24 mx-auto rounded-full overflow-hidden border-2 border-white/30 mb-3">
                                    <img src="{{ $profile->primaryPhoto->full_url }}" alt="{{ $profile->full_name }}" class="w-full h-full object-cover">
                                </div>
                            @else
                                <div class="w-24 h-24 mx-auto rounded-full bg-white/20 flex items-center justify-center mb-3">
                                    <svg class="w-12 h-12 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                    </svg>
                                </div>
                            @endif
                            <h2 class="text-white font-semibold text-lg">{{ $profile->full_name }}</h2>
                            <p class="text-white/70 text-sm">{{ $profile->matri_id }}</p>
                        </div>

                        <div class="p-5 space-y-4">
                            {{-- Verification --}}
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    @if($user->phone_verified_at)
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                        <span class="text-green-700">Mobile Verified</span>
                                    @else
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/></svg>
                                        <span class="text-gray-500">Mobile Not Verified</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    @if($user->email_verified_at)
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                        <span class="text-green-700">Email Verified</span>
                                    @else
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/></svg>
                                        <span class="text-gray-500">Email Not Verified</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Completion --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-sm font-medium text-gray-700">Profile Completion</span>
                                    <span class="text-sm font-bold text-(--color-primary)">{{ $completionPct }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full transition-all duration-500 {{ $completionPct >= 80 ? 'bg-green-500' : ($completionPct >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                                        style="width: {{ $completionPct }}%"></div>
                                </div>
                            </div>

                            {{-- Quick Links --}}
                            <div class="pt-3 border-t border-gray-100 space-y-1">
                                <a href="{{ route('photos.manage') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25a2.25 2.25 0 00-2.25-2.25H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                                    Manage Photos
                                </a>
                                <a href="{{ route('profile.preview') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Profile Preview
                                </a>
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                                    Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ RIGHT: ACCORDION SECTIONS ══ --}}
            <div class="flex-1 min-w-0 space-y-4">
                @php
                    $sections = [
                        ['key' => 'primary', 'title' => 'Primary Information', 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0'],
                        ['key' => 'religious', 'title' => 'Religious Information', 'icon' => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21'],
                        ['key' => 'education', 'title' => 'Education & Profession', 'icon' => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342'],
                        ['key' => 'family', 'title' => 'Family Information', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197'],
                        ['key' => 'location', 'title' => 'Location Information', 'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
                        ['key' => 'contact', 'title' => 'Contact Information', 'icon' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z'],
                        ['key' => 'hobbies', 'title' => 'Hobbies & Interests', 'icon' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
                        ['key' => 'social', 'title' => 'Social Media Information', 'icon' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418'],
                        ['key' => 'partner', 'title' => 'Partner Preferences', 'icon' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
                    ];
                @endphp

                @foreach($sections as $i => $sec)
                    <div x-data="{ open: {{ $openSection === $sec['key'] ? 'true' : 'false' }}, editing: false }"
                        class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
                        {{-- Section Header --}}
                        <button @click="if (!editing) open = !open"
                            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $sec['icon'] }}"/>
                                </svg>
                                <h2 class="text-sm font-semibold text-gray-900">{{ $sec['title'] }}</h2>
                            </div>
                            <div class="flex items-center gap-3">
                                <span x-show="!editing" @click.stop="editing = true; open = true"
                                    class="text-xs font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) px-4 py-1.5 rounded cursor-pointer transition-colors">
                                    EDIT
                                </span>
                                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>

                        {{-- Section Content --}}
                        <div x-show="open" x-cloak class="px-5 pb-5 border-t border-gray-100 pt-4">
                            <div x-show="!editing">
                                @include('profile.sections.' . $sec['key'] . '-view')
                            </div>
                            <div x-show="editing">
                                @include('profile.sections.' . $sec['key'] . '-edit')
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
