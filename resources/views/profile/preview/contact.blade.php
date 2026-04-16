@php
    $c = $profile->contactInfo;
    $s = $profile->socialMediaLink;
    $nm = fn($v) => $v ?: 'Not Mentioned';
    $isOwn = $isOwn ?? false;
@endphp

@php
    $canViewContact = false;
    $isPremium = false;
    $interestAccepted = false;
    if (!$isOwn && auth()->check() && auth()->user()->profile) {
        $myProfileId = auth()->user()->profile->id;
        $isPremium = auth()->user()->isPremium();
        $interestAccepted = \App\Models\Interest::where('status', 'accepted')
            ->where(fn($q) => $q
                ->where(fn($q2) => $q2->where('sender_profile_id', $myProfileId)->where('receiver_profile_id', $profile->id))
                ->orWhere(fn($q2) => $q2->where('sender_profile_id', $profile->id)->where('receiver_profile_id', $myProfileId))
            )->exists();
        // Both conditions required: must be premium AND interest accepted
        $canViewContact = $isPremium && $interestAccepted;
    }
@endphp

@if(!$isOwn && !$canViewContact)
    {{-- Contact details locked --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Contact Details</h3>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-amber-800">Contact details are locked</p>
                    @if(!$isPremium && !$interestAccepted)
                        <p class="text-xs text-amber-600 mt-1">Upgrade to a paid plan and send an interest to view contact details.</p>
                    @elseif($isPremium && !$interestAccepted)
                        <p class="text-xs text-amber-600 mt-1">Send an interest and wait for acceptance to view contact details.</p>
                    @elseif(!$isPremium && $interestAccepted)
                        <p class="text-xs text-amber-600 mt-1">Upgrade to a paid plan to view contact details.</p>
                    @endif
                    @if(!$isPremium)
                        <a href="{{ route('membership.index') }}" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-(--color-primary) hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            View Membership Plans
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Social Media (public) --}}
    @if($s && ($s->facebook_url || $s->instagram_url || $s->linkedin_url))
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Social Media Information</h3>
        <div class="space-y-3">
            @foreach([
                ['label' => 'Facebook', 'url' => $s->facebook_url],
                ['label' => 'Instagram', 'url' => $s->instagram_url],
                ['label' => 'LinkedIn', 'url' => $s->linkedin_url],
            ] as $social)
                @if($social['url'])
                    <div class="flex items-center gap-3 py-2">
                        <span class="text-sm font-medium text-gray-600">{{ $social['label'] }}:</span>
                        <a href="{{ $social['url'] }}" target="_blank" class="text-sm text-(--color-primary) hover:underline truncate">{{ $social['url'] }}</a>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif
@elseif(!$isOwn && $canViewContact)
    {{-- Premium member or interest accepted — show contact details --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Contact Details</h3>
        @if($c)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach([
                    'Contact Person' => $c->contact_person,
                    'Relationship' => $c->contact_relationship,
                    'Phone' => $c->primary_phone,
                    'WhatsApp' => $c->whatsapp_number,
                    'Email' => $c->email,
                ] as $label => $value)
                    @if($value)
                        <div>
                            <p class="text-xs text-gray-500">{{ $label }}</p>
                            <p class="text-sm font-medium text-gray-900">{{ $value }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">Contact details not provided by this user.</p>
        @endif
    </div>

    {{-- Social Media --}}
    @if($s && ($s->facebook_url || $s->instagram_url || $s->linkedin_url))
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Social Media</h3>
        <div class="space-y-3">
            @foreach([
                ['label' => 'Facebook', 'url' => $s->facebook_url],
                ['label' => 'Instagram', 'url' => $s->instagram_url],
                ['label' => 'LinkedIn', 'url' => $s->linkedin_url],
            ] as $social)
                @if($social['url'])
                    <div class="flex items-center gap-3 py-2">
                        <span class="text-sm font-medium text-gray-600">{{ $social['label'] }}:</span>
                        <a href="{{ $social['url'] }}" target="_blank" class="text-sm text-(--color-primary) hover:underline truncate">{{ $social['url'] }}</a>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif
@else
    {{-- Own profile — show all contact details --}}

    {{-- Primary Contact Details --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Primary Contact Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
            <div><p class="text-xs text-gray-500">Mobile Number</p><p class="text-sm font-semibold text-gray-900">+91 {{ $user->phone ?? 'Not Mentioned' }}</p></div>
            <div><p class="text-xs text-gray-500">Custodian Name</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->contact_person) }}</p></div>
            <div><p class="text-xs text-gray-500">Custodian Relation</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->contact_relationship) }}</p></div>
            <div><p class="text-xs text-gray-500">Preferred Time To Reach You</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->preferred_call_time) }}</p></div>
        </div>
    </div>

    {{-- Other Contact Details --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Other Contact Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
            <div><p class="text-xs text-gray-500">Residential Landline No.</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->residential_phone_number) }}</p></div>
            <div><p class="text-xs text-gray-500">WhatsApp Number</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->whatsapp_number) }}</p></div>
            <div><p class="text-xs text-gray-500">Secondary Mobile No.</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->secondary_phone) }}</p></div>
            <div><p class="text-xs text-gray-500">Alternate Email</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->alternate_email) }}</p></div>
        </div>
    </div>

    {{-- Candidate's Address --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Candidate's Address</h3>
        <div class="space-y-4">
            <div><p class="text-xs text-gray-500">Communication Address</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->communication_address) }}{{ $c?->pincode ? ', ' . $c->pincode : '' }}</p></div>
            @if($c?->present_address)
                <div><p class="text-xs text-gray-500">Present Address</p><p class="text-sm font-semibold text-gray-900">{{ $c->present_address }}{{ $c->present_pin_zip_code ? ', ' . $c->present_pin_zip_code : '' }}</p></div>
            @endif
            @if($c?->permanent_address)
                <div><p class="text-xs text-gray-500">Permanent Address</p><p class="text-sm font-semibold text-gray-900">{{ $c->permanent_address }}{{ $c->permanent_pin_zip_code ? ', ' . $c->permanent_pin_zip_code : '' }}</p></div>
            @endif
        </div>
    </div>

    {{-- Reference Person --}}
    @if($c?->reference_name)
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Reference Person's Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
            <div><p class="text-xs text-gray-500">Name</p><p class="text-sm font-semibold text-gray-900">{{ $c->reference_name }}</p></div>
            <div><p class="text-xs text-gray-500">Relationship with Candidate</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c->reference_relationship) }}</p></div>
            <div><p class="text-xs text-gray-500">Mobile No.</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c->reference_mobile) }}</p></div>
        </div>
    </div>
    @endif

    {{-- Social Media --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Social Media Information</h3>
        <div class="space-y-3">
            @foreach([
                ['label' => 'Facebook', 'url' => $s?->facebook_url],
                ['label' => 'Instagram', 'url' => $s?->instagram_url],
                ['label' => 'LinkedIn', 'url' => $s?->linkedin_url],
                ['label' => 'YouTube', 'url' => $s?->youtube_url],
                ['label' => 'Website', 'url' => $s?->website_url],
            ] as $social)
                <div class="flex items-center gap-3 py-2 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <span class="text-sm font-medium text-gray-600 w-20 shrink-0">{{ $social['label'] }}</span>
                    <p class="text-sm font-medium text-gray-900 truncate">
                        @if($social['url'])
                            <a href="{{ $social['url'] }}" target="_blank" class="text-(--color-primary) hover:underline">{{ $social['url'] }}</a>
                        @else
                            Not Mentioned
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
    </div>
@endif
