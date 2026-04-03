<x-layouts.app title="Profile Settings">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Profile Settings</span>
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

        <div class="flex flex-col lg:flex-row gap-8">
            {{-- Left Sidebar --}}
            <div class="hidden lg:block lg:w-56 shrink-0">
                <div class="sticky top-24">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-3">Profile Management</p>
                    @foreach([
                        ['key' => 'profile_filters', 'label' => 'Profile Filters', 'icon' => 'M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z'],
                        ['key' => 'manage_alerts', 'label' => 'Manage Alerts', 'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'],
                        ['key' => 'search_visibility', 'label' => 'Search Visibility', 'icon' => 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z'],
                        ['key' => 'hide_profile', 'label' => 'Hide Profile', 'icon' => 'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88'],
                        ['key' => 'delete_profile', 'label' => 'Delete Profile', 'icon' => 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'],
                    ] as $item)
                        <a href="{{ route('settings.index', ['section' => $item['key']]) }}"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $section === $item['key'] ? 'bg-(--color-primary) text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/></svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-6 mb-3 px-3">Account Management</p>
                    <a href="{{ route('settings.index', ['section' => 'change_password']) }}"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $section === 'change_password' ? 'bg-(--color-primary) text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        Change Password
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-1">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors text-left">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>

            {{-- Right Content --}}
            <div class="flex-1 min-w-0">
                <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">

                    @if($section === 'profile_filters')
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Profile Filters</h2>
                        <p class="text-sm text-gray-500 mb-6">Show my profile to :</p>
                        <form method="POST" action="{{ route('settings.filters') }}">
                            @csrf
                            <div class="space-y-3">
                                @foreach(['all' => 'To all members (Recommended)', 'premium' => 'To all premium members', 'matches' => 'Only to those whom I have included in my matches list'] as $val => $label)
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" name="show_profile_to" value="{{ $val }}"
                                            {{ ($profile->show_profile_to ?? 'all') === $val ? 'checked' : '' }}
                                            class="text-(--color-primary) focus:ring-(--color-primary)">
                                        <span class="text-sm text-gray-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button type="submit" class="mt-6 px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">Submit</button>
                        </form>

                    @elseif($section === 'manage_alerts')
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Manage Alerts</h2>
                        <p class="text-sm text-gray-500 mb-6">Manage the notifications you receive via email.</p>
                        <form method="POST" action="{{ route('settings.alerts') }}">
                            @csrf
                            <div class="space-y-4">
                                @foreach([
                                    'email_interest' => 'New interest received',
                                    'email_accepted' => 'Interest accepted',
                                    'email_declined' => 'Interest declined',
                                    'email_views' => 'Profile contact views',
                                    'email_promotions' => 'Promotions & discounts',
                                ] as $key => $label)
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" name="{{ $key }}" value="1"
                                            {{ ($prefs[$key] ?? false) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                                        <span class="text-sm text-gray-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button type="submit" class="mt-6 px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">Save</button>
                        </form>

                    @elseif($section === 'search_visibility')
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Profile Visibility</h2>
                        <p class="text-sm text-gray-500 mb-6">Control who can see your profile in search results. When enabled, only matching users will find you.</p>
                        <form method="POST" action="{{ route('settings.visibility') }}">
                            @csrf
                            <div class="space-y-4">
                                <label class="flex items-center justify-between cursor-pointer p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Same Religion Only</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Only show my profile to users of my religion{{ $profile->religiousInfo?->religion ? ' (' . $profile->religiousInfo->religion . ')' : '' }}</p>
                                    </div>
                                    <input type="checkbox" name="only_same_religion" value="1"
                                        {{ ($profile->only_same_religion ?? false) ? 'checked' : '' }}
                                        class="w-5 h-5 rounded text-(--color-primary) focus:ring-(--color-primary) border-gray-300">
                                </label>
                                <label class="flex items-center justify-between cursor-pointer p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                    @php
                                        $religion = $profile->religiousInfo?->religion;
                                        if (in_array($religion, ['Christian'])) {
                                            $subLabel = 'Same Denomination Only';
                                            $subDesc = 'Only show my profile to users of my denomination';
                                            $subValue = $profile->religiousInfo?->denomination;
                                        } elseif (in_array($religion, ['Hindu', 'Jain'])) {
                                            $subLabel = 'Same Caste Only';
                                            $subDesc = 'Only show my profile to users of my caste';
                                            $subValue = $profile->religiousInfo?->caste;
                                        } elseif ($religion === 'Muslim') {
                                            $subLabel = 'Same Sect Only';
                                            $subDesc = 'Only show my profile to users of my sect';
                                            $subValue = $profile->religiousInfo?->muslim_sect;
                                        } else {
                                            $subLabel = 'Same Caste / Denomination Only';
                                            $subDesc = 'Only show my profile to users of my caste or denomination';
                                            $subValue = $profile->religiousInfo?->denomination ?? $profile->religiousInfo?->caste;
                                        }
                                    @endphp
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $subLabel }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $subDesc }}{{ $subValue ? ' (' . $subValue . ')' : '' }}</p>
                                    </div>
                                    <input type="checkbox" name="only_same_denomination" value="1"
                                        {{ ($profile->only_same_denomination ?? false) ? 'checked' : '' }}
                                        class="w-5 h-5 rounded text-(--color-primary) focus:ring-(--color-primary) border-gray-300">
                                </label>
                                <label class="flex items-center justify-between cursor-pointer p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Same Mother Tongue Only</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Only show my profile to users who speak my language{{ $profile->mother_tongue ? ' (' . $profile->mother_tongue . ')' : '' }}</p>
                                    </div>
                                    <input type="checkbox" name="only_same_mother_tongue" value="1"
                                        {{ ($profile->only_same_mother_tongue ?? false) ? 'checked' : '' }}
                                        class="w-5 h-5 rounded text-(--color-primary) focus:ring-(--color-primary) border-gray-300">
                                </label>
                            </div>
                            <p class="text-xs text-gray-400 mt-4">Note: Enabling these options may reduce your profile visibility. We recommend keeping them off for maximum matches.</p>
                            <button type="submit" class="mt-4 px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">Save</button>
                        </form>

                    @elseif($section === 'hide_profile')
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Hide Profile</h2>
                        <p class="text-sm text-gray-500 mb-6">Temporarily hide your profile from search results. You can unhide anytime.</p>
                        <div class="p-4 rounded-lg {{ $profile->is_hidden ? 'bg-amber-50 border border-amber-200' : 'bg-green-50 border border-green-200' }}">
                            <p class="text-sm {{ $profile->is_hidden ? 'text-amber-700' : 'text-green-700' }} font-medium">
                                Your profile is currently: <strong>{{ $profile->is_hidden ? 'Hidden' : 'Visible' }}</strong>
                            </p>
                        </div>
                        <form method="POST" action="{{ route('settings.hide') }}" class="mt-4">
                            @csrf
                            <button type="submit" class="px-8 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors {{ $profile->is_hidden ? 'bg-green-500 hover:bg-green-500/90' : 'bg-amber-500 hover:bg-amber-500/90' }}">
                                {{ $profile->is_hidden ? 'Show My Profile' : 'Hide My Profile' }}
                            </button>
                        </form>

                    @elseif($section === 'delete_profile')
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Delete Profile</h2>
                        <p class="text-sm text-gray-500 mb-6">Choose the reason for deleting your profile.</p>
                        <form method="POST" action="{{ route('settings.delete') }}" onsubmit="return confirm('Are you sure? This will deactivate your account.')" x-data="{ selectedReason: '' }">
                            @csrf
                            <div class="space-y-3">
                                @foreach(['I am Married', 'My Marriage is Fixed', 'Other Reasons'] as $reason)
                                    <label class="flex items-center justify-between p-4 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors"
                                        :class="selectedReason === '{{ $reason }}' && 'border-(--color-primary) bg-(--color-primary-light)'">
                                        <span class="text-sm text-gray-700">{{ $reason }}</span>
                                        <input type="radio" name="reason" value="{{ $reason }}" required x-model="selectedReason"
                                            class="text-(--color-primary) focus:ring-(--color-primary)">
                                    </label>
                                @endforeach
                            </div>
                            <div x-show="selectedReason === 'Other Reasons'" x-cloak class="mt-4">
                                <textarea name="other_reason" rows="3" maxlength="500" placeholder="Please tell us why you are leaving..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)"></textarea>
                            </div>
                            <button type="submit" class="mt-6 px-8 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-500/90 rounded-lg transition-colors">Delete Profile</button>
                        </form>

                    @elseif($section === 'change_password')
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Change Password</h2>
                        <form method="POST" action="{{ route('settings.password') }}" class="max-w-md">
                            @csrf
                            <div class="space-y-5">
                                <div class="float-field">
                                    <input type="password" name="current_password" required placeholder=" ">
                                    <label>Enter current password</label>
                                    @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="float-field">
                                    <input type="password" name="new_password" required minlength="6" maxlength="14" placeholder=" ">
                                    <label>Create new password</label>
                                </div>
                                <div class="float-field">
                                    <input type="password" name="new_password_confirmation" required placeholder=" ">
                                    <label>Confirm new password</label>
                                </div>
                                <p class="text-xs text-red-500">Use 6 - 14 characters with a mix of letters, numbers & symbols</p>
                            </div>
                            <button type="submit" class="mt-6 px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">Submit</button>
                        </form>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
