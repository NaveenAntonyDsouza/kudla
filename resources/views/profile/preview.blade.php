<x-layouts.app title="Profile Preview">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('profile.show') }}" class="hover:text-(--color-primary)">View & Edit</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Profile Preview</span>
        </p>

        @if(session('success'))
            <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg">
                @foreach($errors->all() as $error)
                    <p class="text-sm text-red-600 font-medium">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8" x-data="{ activeTab: '{{ $activeTab }}' }">

            {{-- ══ LEFT SIDEBAR ══ --}}
            <div class="shrink-0" style="width: 100%; max-width: 256px;">
                <div class="sticky top-24">
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
                        {{-- Photo --}}
                        <div class="relative" style="max-width: 256px; overflow: hidden;">
                            @if($profile->primaryPhoto)
                                <img src="{{ $profile->primaryPhoto->full_url }}" alt="{{ $profile->full_name }}"
                                    style="width: 100%; height: auto; max-width: 256px; aspect-ratio: 3/4; object-fit: cover;">
                            @else
                                <div class="aspect-[3/4] bg-gray-100 flex items-center justify-center" style="width: 100%; max-width: 256px;">
                                    <svg class="w-20 h-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/>
                                    </svg>
                                </div>
                            @endif
                            @php $photoCount = $profile->profilePhotos->where('is_visible', true)->count(); @endphp
                            @if($photoCount > 1)
                                <div class="absolute top-3 right-3 bg-black/60 text-white text-xs px-2 py-1 rounded-full flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                                    {{ $photoCount }}
                                </div>
                            @endif
                        </div>

                        <div class="p-5 text-center">
                            <h2 class="text-lg font-semibold text-gray-900">{{ $profile->full_name }}</h2>
                            <p class="text-sm text-(--color-primary) font-medium">{{ $profile->matri_id }}</p>

                            <div class="mt-4 space-y-2 text-left">
                                <div class="flex items-center gap-2 text-sm">
                                    @if($user->phone_verified_at)
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                        <span class="text-green-700">Mobile Number Verified</span>
                                    @else
                                        <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                                        <span class="text-red-500">ID Proof Not Verified</span>
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
                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                    Last Login : {{ $user->updated_at?->format('d M Y') }}
                                </div>
                            </div>

                            @if(!($isOwn ?? false))
                                {{-- Action buttons for other users' profiles --}}
                                <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
                                    @php
                                        $interestStatus = app(\App\Services\InterestService::class)->getStatus(auth()->user()->profile, $profile);
                                    @endphp

                                    @if(!$interestStatus)
                                        <x-send-interest-modal :profile="$profile" />
                                    @elseif($interestStatus['direction'] === 'sent' && $interestStatus['status'] === 'pending')
                                        <div class="text-center py-2">
                                            <span class="inline-flex items-center gap-1 text-sm text-amber-600 font-medium">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Interest Sent — Awaiting Response
                                            </span>
                                        </div>
                                    @elseif($interestStatus['direction'] === 'received' && $interestStatus['status'] === 'pending')
                                        <a href="{{ route('interests.show', $interestStatus['interest']) }}"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-green-500 rounded-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75"/></svg>
                                            Respond to Interest
                                        </a>
                                    @elseif($interestStatus['status'] === 'accepted')
                                        <a href="{{ route('interests.show', $interestStatus['interest']) }}"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-(--color-primary) rounded-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                                            Continue Chat
                                        </a>
                                    @elseif($interestStatus['status'] === 'declined')
                                        <div class="text-center py-2">
                                            <span class="text-sm text-gray-500">Interest was declined</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ RIGHT: TABBED CONTENT ══ --}}
            <div class="flex-1 min-w-0">
                {{-- Tab Navigation --}}
                <div class="flex border-b border-gray-200 mb-6 overflow-x-auto">
                    @foreach([
                        ['key' => 'personal', 'label' => 'Personal Details', 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0'],
                        ['key' => 'partner', 'label' => 'Partner Preferences', 'icon' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
                        ['key' => 'family', 'label' => 'Family Details', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197'],
                        ['key' => 'contact', 'label' => 'Contact Details', 'icon' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z'],
                    ] as $tab)
                        <button @click="activeTab = '{{ $tab['key'] }}'"
                            :class="activeTab === '{{ $tab['key'] }}' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $tab['icon'] }}"/>
                            </svg>
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>

                {{-- Tab Content --}}
                <div x-show="activeTab === 'personal'" x-cloak>
                    @include('profile.preview.personal')
                </div>
                <div x-show="activeTab === 'partner'" x-cloak>
                    @include('profile.preview.partner')
                </div>
                <div x-show="activeTab === 'family'" x-cloak>
                    @include('profile.preview.family')
                </div>
                <div x-show="activeTab === 'contact'" x-cloak>
                    @include('profile.preview.contact')
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
