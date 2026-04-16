<x-filament-panels::page>
    {{-- Expiring Soon --}}
    <h3 class="text-lg font-semibold text-orange-600 mb-3">Expiring Within 7 Days</h3>
    @php $expiring = $this->getExpiring(); @endphp

    @if($expiring->isEmpty())
        <p class="text-sm text-gray-500 mb-6">No memberships expiring in the next 7 days.</p>
    @else
        <div class="space-y-2 mb-6">
            @foreach($expiring as $membership)
                @php $profile = $membership->user?->profile; @endphp
                @if($profile)
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-700 p-4 flex items-center gap-4">
                        <div class="shrink-0">
                            @if($profile->primaryPhoto?->photo_url)
                                <img src="{{ asset('storage/' . $profile->primaryPhoto->photo_url) }}" class="w-10 h-10 rounded-full object-cover">
                            @else
                                <img src="{{ url('/images/default-avatar.svg') }}" class="w-10 h-10 rounded-full">
                            @endif
                        </div>
                        <div class="flex-1">
                            <a href="{{ route('filament.admin.resources.users.view', $profile->id) }}" class="font-bold text-primary-600 hover:underline">{{ $profile->full_name }} ({{ $profile->matri_id }})</a>
                            <div class="text-sm text-gray-600">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">{{ $membership->plan?->plan_name }}</span>
                                <span class="ml-2">Expires: {{ $membership->ends_at?->format('d M Y') }} ({{ $membership->ends_at?->diffForHumans() }})</span>
                                <span class="ml-2">{{ $profile->user?->phone }}</span>
                            </div>
                        </div>
                        @if($profile->user?->phone)
                            @php $phone = preg_replace('/[^0-9]/', '', $profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                            <a href="https://wa.me/{{ $phone }}?text={{ urlencode('Hi ' . $profile->full_name . ', your membership plan is expiring soon. Renew now to continue enjoying premium features!') }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-green-100 text-green-700 hover:bg-green-200">WhatsApp</a>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Already Expired --}}
    <h3 class="text-lg font-semibold text-red-600 mb-3">Expired Memberships</h3>
    @php $expired = $this->getExpired(); @endphp

    @if($expired->isEmpty())
        <p class="text-sm text-gray-500">No expired memberships.</p>
    @else
        <div class="space-y-2">
            @foreach($expired as $membership)
                @php $profile = $membership->user?->profile; @endphp
                @if($profile)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4">
                        <div class="shrink-0">
                            @if($profile->primaryPhoto?->photo_url)
                                <img src="{{ asset('storage/' . $profile->primaryPhoto->photo_url) }}" class="w-10 h-10 rounded-full object-cover">
                            @else
                                <img src="{{ url('/images/default-avatar.svg') }}" class="w-10 h-10 rounded-full">
                            @endif
                        </div>
                        <div class="flex-1">
                            <a href="{{ route('filament.admin.resources.users.view', $profile->id) }}" class="font-bold text-primary-600 hover:underline">{{ $profile->full_name }} ({{ $profile->matri_id }})</a>
                            <div class="text-sm text-gray-600">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ $membership->plan?->plan_name }}</span>
                                <span class="ml-2 text-red-600">Expired: {{ $membership->ends_at?->format('d M Y') }} ({{ $membership->ends_at?->diffForHumans() }})</span>
                                <span class="ml-2">{{ $profile->user?->phone }}</span>
                            </div>
                        </div>
                        @if($profile->user?->phone)
                            @php $phone = preg_replace('/[^0-9]/', '', $profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                            <a href="https://wa.me/{{ $phone }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-green-100 text-green-700 hover:bg-green-200">WhatsApp</a>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
