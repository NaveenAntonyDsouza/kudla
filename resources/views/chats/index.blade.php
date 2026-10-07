{{-- Chats: accepted interests as conversations, newest first. A chat opens
     only after an interest is accepted; requests stay under Interests. --}}
<x-layouts.app title="Chats">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <p class="text-sm text-gray-500 mb-4">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Chats</span>
        </p>

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
            <h1 class="text-xl font-semibold text-gray-900">Chats</h1>
            <a href="{{ route('interests.inbox', ['tab' => 'received']) }}" style="font-size: 13px; color: var(--color-primary, #8B1D91);">Interests waiting for you &rarr;</a>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
            @if($chats->count() > 0)
                <div class="divide-y divide-gray-100">
                    @foreach($chats as $chat)
                        @php
                            $other = $chat->sender_profile_id === $profile->id ? $chat->receiverProfile : $chat->senderProfile;
                            $unread = (int) $chat->unread_count;
                            $at = $chat->last_activity_at ? \Illuminate\Support\Carbon::parse($chat->last_activity_at) : $chat->updated_at;
                        @endphp
                        @php
                            $rowContact = $other ? \App\Support\ContactAccess::for($profile, $other) : null;
                            $rowWhatsApp = ($rowContact['state'] ?? null) === \App\Support\ContactAccess::OPEN ? ($rowContact['contact']['whatsapp_link'] ?? null) : null;
                            $anyRowWhatsApp = ($anyRowWhatsApp ?? false) || (bool) $rowWhatsApp;
                        @endphp
                        <div style="display: flex; align-items: center;">
                        <a href="{{ route('interests.show', $chat) }}" class="chat-row" style="flex: 1; min-width: 0; display: flex; align-items: center; gap: 12px; padding: 14px 18px; text-decoration: none;">
                            <div style="width: 48px; height: 48px; border-radius: 9999px; overflow: hidden; flex-shrink: 0; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                @if($other && ($__photoUrl = \App\Support\PhotoVisibility::urlForCurrentViewer($other)))
                                    <img src="{{ $__photoUrl }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <svg width="24" height="24" fill="none" stroke="#d1d5db" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 8px;">
                                    <p style="margin: 0; font-size: 14px; font-weight: {{ $unread ? '700' : '600' }}; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $other?->full_name ?: 'Member' }} <span style="font-weight: 500; color: var(--color-primary, #8B1D91);">{{ $other?->matri_id }}</span>
                                    </p>
                                    <span style="font-size: 11px; color: {{ $unread ? 'var(--color-primary, #8B1D91)' : '#9ca3af' }}; flex-shrink: 0;">{{ $at?->displayTz()->diffForHumans(short: true) }}</span>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 2px;">
                                    <p style="margin: 0; font-size: 13px; color: {{ $unread ? '#111827' : '#6b7280' }}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ \App\Support\Chats::preview($chat, $profile) }}</p>
                                    @if($unread)
                                        <span style="min-width: 20px; height: 20px; padding: 0 6px; border-radius: 9999px; background: var(--color-primary, #8B1D91); color: #fff; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">{{ $unread }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                        @if($rowWhatsApp)
                            <a href="{{ $rowWhatsApp }}" target="_blank" rel="noopener" aria-label="Continue on WhatsApp" title="Continue on WhatsApp"
                               onclick="var p = { source: 'chats_list' }; if (window.gtag) { gtag('event', 'contact_whatsapp_click', p); } if (window.posthog) { posthog.capture('contact_whatsapp_click', p); }"
                               style="flex-shrink: 0; margin-right: 14px; width: 42px; height: 42px; border-radius: 9999px; background: #25D366; color: #fff; display: flex; align-items: center; justify-content: center;">
                                <x-icons.whatsapp :size="21" />
                            </a>
                        @endif
                        </div>
                    @endforeach
                </div>
                @if($chats->hasPages())
                    <div class="px-5 py-3 border-t border-gray-100">{{ $chats->links() }}</div>
                @endif
            @else
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                    <p class="text-sm text-gray-600 font-medium">No chats yet</p>
                    <p class="text-xs text-gray-400 mt-1">When someone accepts your interest — or you accept theirs — your conversation appears here.</p>
                    <a href="{{ route('matches.index') }}" class="inline-block mt-4 text-sm text-(--color-primary) hover:underline font-medium">See your matches</a>
                </div>
            @endif
        </div>
    </div>
    <style>
        .chat-row:hover { background: #f9fafb; }
        /* members' WhatsApp buttons are on this list: hide the site's help button so they aren't confused */
        @if($anyRowWhatsApp ?? false) .wa-fab { display: none !important; } @endif
    </style>
</x-layouts.app>
