<x-layouts.app title="Dashboard">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">

            {{-- Left Sidebar: Profile Summary --}}
            <div class="lg:w-72 shrink-0">
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
                        {{-- Verification Badges (only show if verification is enabled OR already verified) --}}
                        @php
                            $phoneVerificationEnabled = \App\Models\SiteSetting::getValue('phone_verification_enabled', '0') === '1';
                            $emailVerificationEnabled = \App\Models\SiteSetting::getValue('email_verification_enabled', '0') === '1';
                        @endphp
                        @if($phoneVerificationEnabled || $emailVerificationEnabled || $user->phone_verified_at || $user->email_verified_at)
                        <div class="space-y-2">
                            @if($user->phone_verified_at)
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                    <span class="text-green-700">Mobile Verified</span>
                                </div>
                            @elseif($phoneVerificationEnabled)
                                <div class="flex items-center gap-2 text-sm">
                                    <a href="{{ route('register.verify') }}" class="flex items-center gap-2 text-amber-600 hover:underline">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/></svg>
                                        Mobile Not Verified
                                    </a>
                                </div>
                            @endif
                            @if($user->email_verified_at)
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                    <span class="text-green-700">Email Verified</span>
                                </div>
                            @elseif($emailVerificationEnabled)
                                <div class="flex items-center gap-2 text-sm">
                                    <a href="{{ route('register.verifyemail') }}" class="flex items-center gap-2 text-amber-600 hover:underline">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/></svg>
                                        Email Not Verified
                                    </a>
                                </div>
                            @endif
                        </div>
                        @endif

                        {{-- Profile Completion --}}
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
                            <a href="{{ route('profile.show') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                View & Edit Profile
                            </a>
                            <a href="{{ route('photos.manage') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25a2.25 2.25 0 00-2.25-2.25H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                                Manage Photos
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="flex-1 min-w-0 space-y-6">

                {{-- Pending Approval Banner --}}
                @if(!$profile->is_approved)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-5">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Your profile is pending approval</p>
                                <p class="text-xs text-amber-600 mt-0.5">Our team is reviewing your profile. It will be visible to other members after approval. This usually takes 24-48 hours.</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ═══ INCOMPLETE PROFILE: CTA + Sections first ═══ --}}
                @if($completionPct < 80)
                    {{-- Profile Completion CTA --}}
                    <div class="bg-gradient-to-r from-(--color-primary) to-(--color-primary)/80 rounded-lg p-6 text-white">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-lg mb-1">{{ $completionPct }}% — Complete Your Profile</h3>
                                <p class="text-white/80 text-sm">Profiles with more details get 3x more responses.</p>
                            </div>
                            <a href="{{ route('onboarding.step1') }}"
                                class="shrink-0 bg-white text-(--color-primary) hover:bg-gray-100 rounded-lg px-6 py-2.5 font-semibold text-sm transition-colors">
                                Complete Now
                            </a>
                        </div>
                    </div>

                    {{-- Profile Sections Checklist --}}
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Sections</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($sections as $section)
                                <a href="{{ route($section['route']) }}"
                                    class="flex items-center justify-between p-3 rounded-lg border {{ $section['done'] ? 'border-green-200 bg-green-50' : 'border-gray-200 hover:border-gray-300' }} transition-colors">
                                    <div class="flex items-center gap-3">
                                        @if($section['done'])
                                            <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                        @else
                                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 shrink-0"></div>
                                        @endif
                                        <span class="text-sm font-medium {{ $section['done'] ? 'text-green-700' : 'text-gray-700' }}">{{ $section['label'] }}</span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Recommended Matches --}}
                @if($recommendedMatches->count() > 0)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Recommended Matches</h2>
                            <a href="{{ route('matches.index') }}" class="text-sm font-medium text-(--color-primary) hover:underline">See All</a>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            @foreach($recommendedMatches as $p)
                                <x-profile-card :profile="$p" :matchScore="$p->match_score ?? null" :matchBadge="$p->match_badge ?? null" />
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Stats Bar --}}
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach([
                        ['label' => 'Interest Sent', 'count' => $interestStats['sent'], 'route' => route('interests.inbox', ['tab' => 'sent']), 'color' => 'text-(--color-primary)'],
                        ['label' => 'Interest Accepted', 'count' => $interestStats['accepted'], 'route' => route('interests.inbox', ['tab' => 'all', 'filter' => 'accepted_me']), 'color' => 'text-green-600'],
                        ['label' => 'Profile Views', 'count' => $interestStats['views'], 'route' => route('views.index'), 'color' => 'text-blue-600'],
                        ['label' => 'Pending Received', 'count' => $interestStats['received'], 'route' => route('interests.inbox', ['tab' => 'received']), 'color' => 'text-amber-600'],
                        ['label' => 'Shortlisted', 'count' => $interestStats['shortlisted'], 'route' => route('shortlist.index'), 'color' => 'text-pink-600'],
                    ] as $stat)
                        <a href="{{ $stat['route'] }}" class="bg-white rounded-lg border border-gray-200 shadow-xs p-4 text-center hover:border-(--color-primary)/30 hover:shadow-md transition-all">
                            <p class="text-2xl font-bold {{ $stat['color'] }}">{{ $stat['count'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</p>
                        </a>
                    @endforeach
                </div>

                {{-- Mutual Matches --}}
                @if($mutualMatches->count() > 0)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Mutual Matches</h2>
                            <a href="{{ route('matches.mutual') }}" class="text-sm font-medium text-(--color-primary) hover:underline">See All</a>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($mutualMatches as $p)
                                <x-profile-card :profile="$p" :matchScore="$p->match_score ?? null" :matchBadge="$p->match_badge ?? null" />
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Recent Profile Views (who viewed me) --}}
                @if(!$isPremium && $viewCount > 0)
                    {{-- Free user with views: show count + upgrade CTA --}}
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Who Viewed Your Profile</h2>
                        </div>
                        <div class="flex items-center gap-4 p-5 bg-(--color-primary-light) rounded-lg">
                            <div class="w-14 h-14 rounded-full bg-(--color-primary)/10 flex items-center justify-center shrink-0">
                                <svg class="w-7 h-7 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xl font-bold text-(--color-primary)">{{ $viewCount }} {{ $viewCount === 1 ? 'person' : 'people' }}</p>
                                <p class="text-sm text-gray-600">viewed your profile. Upgrade to see who they are.</p>
                            </div>
                            <a href="{{ route('membership.index') }}" class="shrink-0 px-5 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                                Upgrade
                            </a>
                        </div>
                    </div>
                @elseif($recentViews->count() > 0)
                    {{-- Premium user: show profile cards --}}
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Recent Profile Views</h2>
                            <a href="{{ route('views.index') }}" class="text-sm font-medium text-(--color-primary) hover:underline">See All</a>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            @foreach($recentViews as $p)
                                <x-profile-card :profile="$p" />
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 6. Newly Joined Profiles --}}
                @if($newlyJoined->count() > 0)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Newly Joined Profiles</h2>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            @foreach($newlyJoined as $p)
                                <x-profile-card :profile="$p" />
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 7. Discover Profiles --}}
                <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Discover Profiles</h2>
                        <a href="{{ route('discover.hub') }}" class="text-sm font-medium text-(--color-primary) hover:underline">See All</a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($discoverCategories as $cat)
                            <a href="{{ route('discover.category', $cat['slug']) }}"
                                class="flex items-center justify-center px-4 py-3 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:border-(--color-primary) hover:text-(--color-primary) hover:bg-(--color-primary-light) transition-colors text-center">
                                {{ $cat['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
