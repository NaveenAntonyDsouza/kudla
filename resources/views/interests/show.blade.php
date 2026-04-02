@php
    $templateTexts = [
        'msg_suitable' => 'We find your profile suitable and would like to take this further. If you feel the same, kindly accept or share your thoughts.',
        'msg_parents_like' => 'My family and I like your profile and wish to hear from you. We look forward to your response, even if you feel we may not be the right match.',
        'msg_compatible' => 'Our profiles appear compatible. Please respond with your opinion so we may proceed accordingly.',
        'msg_children_align' => "Our children's profiles seem to align well; kindly let us know your interest to take the discussion forward.",
        'reply_accept_1' => 'Thank you for your interest. We are also interested in your profile and would like to proceed.',
        'reply_accept_2' => 'I am happy to accept your interest. Please feel free to contact me.',
        'decline_standard' => 'Thank you for the interest, but I feel we may not be the right match. Wishing you the best in your search.',
    ];
@endphp
<x-layouts.app title="Interest Message">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('interests.inbox') }}" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Back
            </a>
            <div class="flex items-center gap-2">
                {{-- Star --}}
                <form method="POST" action="{{ route('interests.star', $interest) }}">
                    @csrf
                    @php $isStarred = $isSender ? $interest->is_starred_by_sender : $interest->is_starred_by_receiver; @endphp
                    <button type="submit" class="p-2 rounded-lg hover:bg-gray-100 {{ $isStarred ? 'text-amber-400' : 'text-gray-400' }}" title="{{ $isStarred ? 'Unstar' : 'Star' }}">
                        <svg class="w-5 h-5" fill="{{ $isStarred ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                        </svg>
                    </button>
                </form>
                {{-- Trash --}}
                <form method="POST" action="{{ route('interests.trash', $interest) }}">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-red-500" title="Move to trash">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    </button>
                </form>
            </div>
        </div>

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

        {{-- ── Profile Card ── --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-5 mb-6">
            <div class="flex items-start gap-4">
                <a href="{{ route('profile.view', $otherProfile) }}" class="shrink-0">
                    <div class="w-16 h-16 rounded-full bg-gray-100 overflow-hidden">
                        @if($otherProfile->primaryPhoto)
                            <img src="{{ $otherProfile->primaryPhoto->full_url }}" alt="" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center"><svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg></div>
                        @endif
                    </div>
                </a>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('profile.view', $otherProfile) }}" class="text-base font-semibold text-(--color-primary) hover:underline">{{ $otherProfile->matri_id }}</a>
                    @php
                        $desc = collect([
                            $otherProfile->age ? $otherProfile->age . 'Yrs' : null,
                            $otherProfile->height, $otherProfile->complexion, $otherProfile->marital_status,
                            $otherProfile->religiousInfo?->religion, $otherProfile->religiousInfo?->denomination,
                            $otherProfile->educationDetail?->highest_education,
                            $otherProfile->educationDetail?->occupation,
                            $otherProfile->locationInfo?->native_state,
                        ])->filter()->implode(', ');
                    @endphp
                    <p class="text-xs text-gray-600 mt-1">{{ $desc }}</p>
                    <p class="mt-2">
                        @php
                            $statusBadge = match($interest->status) {
                                'pending' => ['Pending', 'text-amber-700 bg-amber-100'],
                                'accepted' => ['Accepted', 'text-green-700 bg-green-100'],
                                'declined' => ['Declined', 'text-red-700 bg-red-100'],
                                'cancelled' => ['Cancelled', 'text-gray-600 bg-gray-100'],
                                'expired' => ['Expired', 'text-gray-500 bg-gray-100'],
                                default => [$interest->status, 'text-gray-600 bg-gray-100'],
                            };
                        @endphp
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $statusBadge[1] }}">Status: {{ $statusBadge[0] }}</span>
                    </p>
                </div>

                {{-- Cancel button (for sender, if pending) --}}
                @if($isSender && $interest->status === 'pending')
                    <form method="POST" action="{{ route('interests.cancel', $interest) }}" onsubmit="return confirm('Cancel this interest?')">
                        @csrf
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Cancel Interest</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- ── Conversation Thread ── --}}
        <div class="space-y-4 mb-6">
            {{-- Initial interest message --}}
            <div class="flex gap-3 {{ $isSender ? 'justify-end' : '' }}">
                <div class="max-w-md {{ $isSender ? 'order-1 bg-(--color-primary-light) border-(--color-primary)/20' : 'bg-gray-50 border-gray-200' }} rounded-lg border p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-4 h-4 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75"/></svg>
                        <span class="text-xs font-semibold text-(--color-primary)">Interest message sent</span>
                        <span class="text-xs text-gray-400 ml-auto">{{ $interest->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <p class="text-sm text-gray-700">{{ $interest->custom_message ?? ($templateTexts[$interest->template_id] ?? $interest->template_id ?? '') }}</p>
                </div>
            </div>

            {{-- Replies --}}
            @foreach($interest->replies->sortBy('created_at') as $reply)
                @php
                    $isMyReply = $reply->replier_profile_id === $profile->id;
                    $replyLabel = match($reply->reply_type) {
                        'accept' => 'Accepted',
                        'decline' => $reply->is_silent_decline ? 'Declined (silent)' : 'Declined',
                        'message' => 'Message',
                        default => $reply->reply_type,
                    };
                    $replyColor = match($reply->reply_type) {
                        'accept' => 'text-green-600',
                        'decline' => 'text-red-500',
                        default => 'text-(--color-primary)',
                    };
                @endphp

                @if(!$reply->is_silent_decline || $reply->replier_profile_id === $profile->id)
                    <div class="flex gap-3 {{ $isMyReply ? 'justify-end' : '' }}">
                        <div class="max-w-md {{ $isMyReply ? 'bg-(--color-primary-light) border-(--color-primary)/20' : 'bg-gray-50 border-gray-200' }} rounded-lg border p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-semibold {{ $replyColor }}">{{ $replyLabel }}</span>
                                <span class="text-xs text-gray-400 ml-auto">{{ $reply->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            @if($reply->custom_message)
                                <p class="text-sm text-gray-700">{{ $reply->custom_message }}</p>
                            @elseif($reply->template_id)
                                <p class="text-sm text-gray-700">{{ ($templateTexts[$reply->template_id] ?? $reply->template_id ?? '') }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- ── Action Area ── --}}
        @if($interest->status === 'pending' && !$isSender)
            {{-- Receiver can Accept or Decline --}}
            <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-5" x-data="{
                action: '',
                selectedTemplate: '',
                customMessage: '',
                submitting: false,
                declineOpen: false
            }">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Reply to this interest</h3>

                {{-- Accept section --}}
                <div class="mb-4">
                    <p class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-2">Accept Interest</p>
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-green-50 transition-colors"
                            :class="action === 'accept' && selectedTemplate === 'reply_accept_1' ? 'border-green-400 bg-green-50' : 'border-gray-200'"
                            @click="action = 'accept'; selectedTemplate = 'reply_accept_1'">
                            <input type="radio" name="_action" value="accept_1" class="mt-0.5 text-green-600 focus:ring-green-500"
                                :checked="action === 'accept' && selectedTemplate === 'reply_accept_1'">
                            <span class="text-sm text-gray-700">Thank you for your interest. We are also interested in your profile and would like to proceed.</span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-green-50 transition-colors"
                            :class="action === 'accept' && selectedTemplate === 'reply_accept_2' ? 'border-green-400 bg-green-50' : 'border-gray-200'"
                            @click="action = 'accept'; selectedTemplate = 'reply_accept_2'">
                            <input type="radio" name="_action" value="accept_2" class="mt-0.5 text-green-600 focus:ring-green-500"
                                :checked="action === 'accept' && selectedTemplate === 'reply_accept_2'">
                            <span class="text-sm text-gray-700">I am happy to accept your interest. Please feel free to contact me.</span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-green-50 transition-colors"
                            :class="action === 'accept' && selectedTemplate === 'reply_accept_custom' ? 'border-green-400 bg-green-50' : 'border-gray-200'"
                            @click="action = 'accept'; selectedTemplate = 'reply_accept_custom'">
                            <input type="radio" name="_action" value="accept_custom" class="mt-0.5 text-green-600 focus:ring-green-500"
                                :checked="action === 'accept' && selectedTemplate === 'reply_accept_custom'">
                            <span class="text-sm text-gray-700 font-medium">Accept and send a personal reply</span>
                        </label>
                        <div x-show="action === 'accept' && selectedTemplate === 'reply_accept_custom'" class="pl-6">
                            <textarea x-model="customMessage" rows="3" maxlength="500" placeholder="Write your reply..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Decline section (collapsible) --}}
                <div>
                    <button type="button" @click="declineOpen = !declineOpen" class="flex items-center gap-2 text-xs font-semibold text-red-500 uppercase tracking-wider mb-2">
                        <svg class="w-3 h-3 transition-transform" :class="declineOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        Decline Interest
                    </button>
                    <div x-show="declineOpen" x-cloak class="space-y-2">
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-red-50 transition-colors"
                            :class="action === 'decline' && selectedTemplate === 'decline_standard' ? 'border-red-400 bg-red-50' : 'border-gray-200'"
                            @click="action = 'decline'; selectedTemplate = 'decline_standard'">
                            <input type="radio" name="_action" value="decline_std" class="mt-0.5 text-red-600 focus:ring-red-500"
                                :checked="action === 'decline' && selectedTemplate === 'decline_standard'">
                            <span class="text-sm text-gray-700">Thank you for the interest, but I feel we may not be the right match. Wishing you the best in your search.</span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-red-50 transition-colors"
                            :class="action === 'decline' && selectedTemplate === 'decline_silent' ? 'border-red-400 bg-red-50' : 'border-gray-200'"
                            @click="action = 'decline'; selectedTemplate = 'decline_silent'">
                            <input type="radio" name="_action" value="decline_silent" class="mt-0.5 text-red-600 focus:ring-red-500"
                                :checked="action === 'decline' && selectedTemplate === 'decline_silent'">
                            <span class="text-sm text-gray-700">Decline without sending a message (Silent)</span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-red-50 transition-colors"
                            :class="action === 'decline' && selectedTemplate === 'decline_custom' ? 'border-red-400 bg-red-50' : 'border-gray-200'"
                            @click="action = 'decline'; selectedTemplate = 'decline_custom'">
                            <input type="radio" name="_action" value="decline_custom" class="mt-0.5 text-red-600 focus:ring-red-500"
                                :checked="action === 'decline' && selectedTemplate === 'decline_custom'">
                            <span class="text-sm text-gray-700 font-medium">Decline and send a personal note</span>
                        </label>
                        <div x-show="action === 'decline' && selectedTemplate === 'decline_custom'" class="pl-6">
                            <textarea x-model="customMessage" rows="3" maxlength="250" placeholder="Write your decline message..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Submit Buttons (always visible, form changes based on action) --}}
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 mb-3" x-show="action === ''">Select an option above to continue</p>

                    <form x-ref="acceptForm" method="POST" action="{{ route('interests.accept', $interest) }}">@csrf
                        <input type="hidden" name="template_id" :value="selectedTemplate">
                        <input type="hidden" name="custom_message" :value="selectedTemplate.includes('custom') ? customMessage : ''">
                    </form>
                    <form x-ref="declineForm" method="POST" action="{{ route('interests.decline', $interest) }}">@csrf
                        <input type="hidden" name="template_id" :value="selectedTemplate">
                        <input type="hidden" name="custom_message" :value="selectedTemplate.includes('custom') ? customMessage : ''">
                        <input type="hidden" name="silent" :value="selectedTemplate === 'decline_silent' ? '1' : '0'">
                    </form>

                    <div class="flex justify-end gap-3">
                        <button type="button"
                            x-show="action === 'accept'"
                            @click="$refs.acceptForm.submit()"
                            style="display:none"
                            class="px-6 py-2.5 text-sm font-semibold text-white rounded-lg bg-green-500 hover:bg-green-500/90 cursor-pointer">
                            Accept & Send Reply
                        </button>
                        <button type="button"
                            x-show="action === 'decline'"
                            @click="$refs.declineForm.submit()"
                            style="display:none"
                            class="px-6 py-2.5 text-sm font-semibold text-white rounded-lg bg-red-500 hover:bg-red-500/90 cursor-pointer">
                            Decline
                        </button>
                    </div>
                </div>
            </div>
        @elseif($interest->status === 'accepted')
            {{-- Chat input for accepted interests --}}
            <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-5">
                <form method="POST" action="{{ route('interests.message', $interest) }}" class="flex gap-3" x-data="{ msg: '', submitting: false }" @submit="submitting = true">
                    @csrf
                    <input type="text" name="message" x-model="msg" required maxlength="500" placeholder="Type a message..."
                        class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
                    <button type="submit" :disabled="submitting || msg.length === 0"
                        :class="(submitting || msg.length === 0) && 'opacity-50 cursor-not-allowed'"
                        class="px-6 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors shrink-0">
                        <span x-show="!submitting">Send</span>
                        <span x-show="submitting" x-cloak>Sending...</span>
                    </button>
                </form>
            </div>
        @elseif($interest->status === 'declined')
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 text-center">
                <p class="text-sm text-gray-500">This interest has been declined. The conversation is closed.</p>
            </div>
        @endif
    </div>
</x-layouts.app>

