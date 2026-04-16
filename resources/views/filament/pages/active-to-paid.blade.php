<x-filament-panels::page>
    <p class="text-sm text-gray-500 mb-4">Free users sorted by recent activity — most likely to convert to paid plans. Contact them via WhatsApp to promote membership.</p>

    @php $profiles = $this->getProfiles(); @endphp

    <div class="space-y-3">
        @forelse($profiles as $profile)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex gap-4 items-center">
                    <div class="shrink-0">
                        @if($profile->primaryPhoto?->photo_url)
                            <img src="{{ asset('storage/' . $profile->primaryPhoto->photo_url) }}" alt="" class="w-12 h-12 rounded-full object-cover">
                        @else
                            <img src="{{ url('/images/default-avatar.svg') }}" alt="" class="w-12 h-12 rounded-full">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('filament.admin.resources.users.view', $profile->id) }}" class="font-bold text-primary-600 hover:underline">
                            {{ $profile->full_name }} ({{ $profile->matri_id }})
                        </a>
                        <div class="text-sm text-gray-600 flex flex-wrap gap-x-4">
                            <span>{{ $profile->gender === 'male' ? 'Male' : 'Female' }}, {{ $profile->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age . 'y' : '-' }}</span>
                            <span>{{ $profile->user?->phone ?? '-' }}</span>
                            <span>{{ $profile->religiousInfo?->religion ?? '-' }}</span>
                            <span>{{ $profile->locationInfo?->native_state ?? '-' }}</span>
                            <span>Last Login: {{ $profile->user?->last_login_at ? \Carbon\Carbon::parse($profile->user->last_login_at)->diffForHumans() : 'Never' }}</span>
                            <span>Profile: {{ $profile->profile_completion_pct ?? 0 }}%</span>
                        </div>
                    </div>
                    <div class="shrink-0 flex gap-2">
                        @if($profile->user?->phone)
                            @php
                                $phone = preg_replace('/[^0-9]/', '', $profile->user->phone);
                                if (strlen($phone) === 10) $phone = '91' . $phone;
                            @endphp
                            <a href="https://wa.me/{{ $phone }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-green-100 text-green-700 hover:bg-green-200">WhatsApp</a>
                        @endif
                        <a href="{{ route('filament.admin.resources.users.view', $profile->id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-primary-50 text-primary-700 hover:bg-primary-100">View</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border">
                <p class="text-gray-500">No free active users found. All active users have paid plans!</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
