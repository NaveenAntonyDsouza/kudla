<x-layouts.app title="Interests">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Interests</span>
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

        {{-- Phones: the tab sidebar below is desktop-only (lg:block), so
             Received / Sent / Starred / Trash get a chip row here --}}
        <style>.inbox-mobile-tabs{display:flex}@media (min-width:1024px){.inbox-mobile-tabs{display:none}}</style>
        <nav class="inbox-mobile-tabs" aria-label="Interest tabs" style="flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
            @foreach(['all' => 'All', 'received' => 'Received', 'sent' => 'Sent', 'starred' => 'Starred', 'trash' => 'Trash'] as $key => $label)
                <a href="{{ route('interests.inbox', ['tab' => $key]) }}"
                   style="padding: 7px 13px; border-radius: 9999px; font-size: 13px; font-weight: 500; text-decoration: none; border: 1px solid {{ $tab === $key ? 'var(--color-primary, #8B1D91)' : '#d1d5db' }}; background: {{ $tab === $key ? 'var(--color-primary, #8B1D91)' : '#fff' }}; color: {{ $tab === $key ? '#fff' : '#374151' }};"
                   @if($tab === $key) aria-current="page" @endif>{{ $label }} <span style="opacity: .7;">{{ $counts[$key] ?? 0 }}</span></a>
            @endforeach
            <a href="{{ route('chats.index') }}" style="padding: 7px 13px; border-radius: 9999px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid var(--color-primary, #8B1D91); color: var(--color-primary, #8B1D91); background: #fff;">Chats &rarr;</a>
        </nav>

        <div class="flex flex-col lg:flex-row gap-8">

            {{-- ══ LEFT SIDEBAR ══ --}}
            <div class="hidden lg:block lg:w-56 shrink-0">
                <div class="sticky top-24 space-y-1">
                    <h2 class="text-base font-semibold text-gray-900 mb-1">Interests</h2>
                    <a href="{{ route('chats.index') }}" class="block text-xs text-(--color-primary) hover:underline mb-3">Your conversations are under Chats &rarr;</a>

                    {{-- Tabs --}}
                    @foreach([
                        ['key' => 'all', 'label' => 'All', 'count' => $counts['all'], 'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'],
                        ['key' => 'received', 'label' => 'Received', 'count' => $counts['received'], 'icon' => 'M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859'],
                        ['key' => 'sent', 'label' => 'Sent', 'count' => $counts['sent'], 'icon' => 'M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5'],
                        ['key' => 'starred', 'label' => 'Starred', 'count' => $counts['starred'], 'icon' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
                        ['key' => 'trash', 'label' => 'Trash', 'count' => $counts['trash'], 'icon' => 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'],
                    ] as $t)
                        <a href="{{ route('interests.inbox', ['tab' => $t['key']]) }}"
                            class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $tab === $t['key'] ? 'bg-(--color-primary) text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $t['icon'] }}"/></svg>
                                {{ $t['label'] }}
                            </span>
                            <span class="text-xs {{ $tab === $t['key'] ? 'text-white/70' : 'text-gray-400' }}">{{ $t['count'] }}</span>
                        </a>
                    @endforeach

                    {{-- Filters --}}
                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 px-3">Filter By</p>
                        @foreach([
                            ['key' => 'interest_received', 'label' => 'Interest Received', 'count' => $counts['interest_received']],
                            ['key' => 'interest_sent', 'label' => 'Interest Sent', 'count' => $counts['interest_sent']],
                            ['key' => 'i_accepted', 'label' => 'I Accepted', 'count' => $counts['i_accepted']],
                            ['key' => 'accepted_me', 'label' => 'Accepted Me', 'count' => $counts['accepted_me']],
                            ['key' => 'i_declined', 'label' => 'I Declined', 'count' => $counts['i_declined']],
                            ['key' => 'declined_me', 'label' => 'Declined Me', 'count' => $counts['declined_me']],
                            ['key' => 'expired', 'label' => 'Expired', 'count' => $counts['expired']],
                        ] as $f)
                            <a href="{{ route('interests.inbox', ['tab' => $tab, 'filter' => $f['key']]) }}"
                                class="flex items-center justify-between px-3 py-1.5 rounded-lg text-sm transition-colors {{ $filter === $f['key'] ? 'text-(--color-primary) font-semibold bg-(--color-primary-light)' : 'text-gray-600 hover:bg-gray-50' }}">
                                {{ $f['label'] }}
                                <span class="text-xs {{ $filter === $f['key'] ? 'text-(--color-primary)' : 'text-gray-400' }}">{{ str_pad($f['count'], 2, '0', STR_PAD_LEFT) }}</span>
                            </a>
                        @endforeach

                        @if($filter)
                            <a href="{{ route('interests.inbox', ['tab' => $tab]) }}" class="block text-xs text-(--color-primary) hover:underline mt-2 px-3">Clear Filter</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ══ RIGHT: INTEREST LIST ══ --}}
            <div class="flex-1 min-w-0">
                <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
                    @if($interests->count() > 0)
                        <div class="divide-y divide-gray-100">
                            @foreach($interests as $interest)
                                @php
                                    $isSender = $interest->sender_profile_id === $profile->id;
                                    $other = $isSender ? $interest->receiverProfile : $interest->senderProfile;
                                    $isStarred = $isSender ? $interest->is_starred_by_sender : $interest->is_starred_by_receiver;

                                    $statusLabel = match($interest->status) {
                                        'pending' => $isSender ? 'Interest Sent' : 'Interest Received',
                                        'accepted' => $isSender ? 'Accepted Me' : 'I Accepted',
                                        'declined' => $isSender ? 'Declined Me' : 'I Declined',
                                        'cancelled' => 'Cancelled',
                                        'expired' => 'Expired',
                                        default => $interest->status,
                                    };

                                    $statusColor = match($interest->status) {
                                        'pending' => 'text-amber-600 bg-amber-50',
                                        'accepted' => 'text-green-600 bg-green-50',
                                        'declined' => 'text-red-600 bg-red-50',
                                        'cancelled' => 'text-gray-500 bg-gray-50',
                                        'expired' => 'text-gray-400 bg-gray-50',
                                        default => 'text-gray-500 bg-gray-50',
                                    };
                                @endphp

                                @once
                                    <style>
                                        .ib-status-phone { display: inline-block; }
                                        .ib-status-wide { display: none; }
                                        @media (min-width: 640px) { .ib-status-phone { display: none; } .ib-status-wide { display: inline-block; } }
                                    </style>
                                @endonce
                                <div>
                                <a href="{{ route('interests.show', $interest) }}"
                                    class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition-colors">
                                    {{-- Star --}}
                                    <form method="POST" action="{{ route('interests.star', $interest) }}" @click.stop>
                                        @csrf
                                        <button type="submit" class="p-1 {{ $isStarred ? 'text-amber-400' : 'text-gray-300 hover:text-amber-400' }}">
                                            <svg class="w-4 h-4" fill="{{ $isStarred ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                                            </svg>
                                        </button>
                                    </form>

                                    {{-- Photo --}}
                                    <div class="w-10 h-10 rounded-full bg-gray-100 overflow-hidden shrink-0">
                                        @if($__photoUrl = \App\Support\PhotoVisibility::urlForCurrentViewer($other))
                                            <img src="{{ $__photoUrl }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Name & ID: wrap, never cut off — on a phone the status
                                         goes underneath (Oct 2026: "AM100…", "Smoke Ta…") --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900" style="overflow-wrap: anywhere;">{{ $other?->matri_id ?? 'Unknown' }}</p>
                                        <p class="text-xs text-gray-500" style="overflow-wrap: anywhere;">{{ $other?->full_name }}{{ $other?->age ? ' · '.$other->age.' yrs' : '' }}</p>
                                        <span class="ib-status-phone text-xs font-medium px-2.5 py-1 rounded-full {{ $statusColor }}" style="margin-top: 4px;">{{ $statusLabel }}</span>
                                    </div>

                                    {{-- Status --}}
                                    <span class="ib-status-wide text-xs font-medium px-2.5 py-1 rounded-full {{ $statusColor }} shrink-0">
                                        {{ $statusLabel }}
                                    </span>

                                    {{-- Date --}}
                                    <p class="text-xs text-gray-400 shrink-0 hidden sm:block">{{ $interest->updated_at->displayTz()->format('d M, h:i A') }}</p>
                                </a>

                                {{-- Answer from the list: pending interests sat unanswered
                                     until they expired (464 on kudla, Oct 2026). --}}
                                @if(! $isSender && $interest->status === 'pending')
                                    <div class="interest-quick-reply" style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 0 20px 14px 76px;">
                                        <form method="POST" action="{{ route('interests.accept', $interest) }}">
                                            @csrf
                                            <input type="hidden" name="template_id" value="reply_accept_1">
                                            <button type="submit" style="padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #fff; background: #16a34a; border: 0; cursor: pointer;">Accept</button>
                                        </form>
                                        <form method="POST" action="{{ route('interests.decline', $interest) }}"
                                              onsubmit="return confirm('Decline this interest? They will get a polite reply saying you may not be the right match.');">
                                            @csrf
                                            <input type="hidden" name="template_id" value="decline_standard">
                                            <button type="submit" style="padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; color: #374151; background: #fff; border: 1px solid #d1d5db; cursor: pointer;">Decline</button>
                                        </form>
                                        <a href="{{ route('interests.show', $interest) }}" style="font-size: 13px; color: var(--color-primary, #8B1D91);">Reply with a message</a>
                                        <p style="flex-basis: 100%; margin: 2px 0 0; font-size: 12px; color: #6b7280;">Accepting shares your phone and WhatsApp with each other.</p>
                                    </div>
                                @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="px-5 py-3 border-t border-gray-100">
                            {{ $interests->links() }}
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                            </svg>
                            <p class="text-sm text-gray-500">No interest messages yet.</p>
                            <p class="text-xs text-gray-400 mt-1">When you send or receive interests, they'll appear here.</p>
                            <a href="{{ route('search.index') }}" class="inline-block mt-4 text-sm text-(--color-primary) hover:underline font-medium">Search Profiles</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
