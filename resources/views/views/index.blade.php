<x-layouts.app title="Profile Views">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Profile Views</span>
        </p>

        {{-- Tab Navigation --}}
        <div class="flex border-b border-gray-200 mb-6">
            <a href="{{ route('views.index', ['tab' => 'viewed_by']) }}"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'viewed_by' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Profiles Viewed by Others
            </a>
            <a href="{{ route('views.index', ['tab' => 'i_viewed']) }}"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'i_viewed' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Profiles I Viewed
            </a>
        </div>

        @if($views->count() > 0)
            <div class="bg-white rounded-lg border border-gray-200 shadow-xs divide-y divide-gray-100">
                @foreach($views as $view)
                    @php
                        $otherProfile = $tab === 'viewed_by' ? $view->viewerProfile : $view->viewedProfile;
                    @endphp
                    @if($otherProfile)
                        <a href="{{ route('profile.view', $otherProfile) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition-colors">
                            <div class="w-12 h-12 rounded-full bg-gray-100 overflow-hidden shrink-0">
                                @if($otherProfile->primaryPhoto)
                                    <img src="{{ $otherProfile->primaryPhoto->full_url }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center"><svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg></div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-(--color-primary)">{{ $otherProfile->matri_id }}</p>
                                <p class="text-xs text-gray-500 truncate">
                                    {{ collect([$otherProfile->age ? $otherProfile->age . ' Yrs' : null, $otherProfile->religiousInfo?->religion, $otherProfile->educationDetail?->occupation, $otherProfile->locationInfo?->native_state])->filter()->implode(', ') }}
                                </p>
                            </div>
                            <p class="text-xs text-gray-400 shrink-0">{{ $view->viewed_at->format('d M Y, h:i A') }}</p>
                        </a>
                    @endif
                @endforeach
            </div>
            <div class="mt-6">{{ $views->withQueryString()->links() }}</div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                </svg>
                <p class="text-sm text-gray-500">{{ $tab === 'viewed_by' ? 'No one has viewed your profile yet.' : 'You haven\'t viewed any profiles yet.' }}</p>
                @if($tab === 'viewed_by')
                    <p class="text-xs text-gray-400 mt-1">Complete your profile and add photos to get more views.</p>
                @endif
            </div>
        @endif
    </div>
</x-layouts.app>
