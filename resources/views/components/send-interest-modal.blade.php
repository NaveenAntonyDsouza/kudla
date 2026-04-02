@props(['profile'])

<div x-data="{ showModal: false, selectedTemplate: 'msg_suitable', customMessage: '', submitting: false }">
    {{-- Trigger Button --}}
    <button @click="showModal = true" type="button"
        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
        </svg>
        Send Interest
    </button>

    {{-- Modal --}}
    <div x-show="showModal" x-cloak @keydown.escape.window="showModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.outside="showModal = false">
        <div @click.stop class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Send Interest to {{ $profile->matri_id }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Express interest by selecting a message</p>
                </div>
                <button @click="showModal = false" class="p-1 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <form method="POST" action="{{ route('interests.send', $profile) }}" @submit="submitting = true">
                @csrf
                <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                    <div class="space-y-3">
                        @foreach([
                            ['id' => 'msg_suitable', 'text' => 'We find your profile suitable and would like to take this further. If you feel the same, kindly accept or share your thoughts.'],
                            ['id' => 'msg_parents_like', 'text' => 'My family and I like your profile and wish to hear from you. We look forward to your response, even if you feel we may not be the right match.'],
                            ['id' => 'msg_compatible', 'text' => 'Our profiles appear compatible. Please respond with your opinion so we may proceed accordingly.'],
                            ['id' => 'msg_children_align', 'text' => "Our children's profiles seem to align well; kindly let us know your interest to take the discussion forward."],
                        ] as $template)
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-gray-50 transition-colors"
                                :class="selectedTemplate === '{{ $template['id'] }}' ? 'border-(--color-primary) bg-(--color-primary-light)' : 'border-gray-200'">
                                <input type="radio" name="template_id" value="{{ $template['id'] }}" x-model="selectedTemplate"
                                    class="mt-0.5 text-(--color-primary) focus:ring-(--color-primary)">
                                <span class="text-sm text-gray-700">{{ $template['text'] }}</span>
                            </label>
                        @endforeach

                        {{-- Custom message option --}}
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-gray-50 transition-colors"
                            :class="selectedTemplate === 'msg_custom' ? 'border-(--color-primary) bg-(--color-primary-light)' : 'border-gray-200'">
                            <input type="radio" name="template_id" value="msg_custom" x-model="selectedTemplate"
                                class="mt-0.5 text-(--color-primary) focus:ring-(--color-primary)">
                            <span class="text-sm text-gray-700 font-medium">Write a personal message</span>
                        </label>

                        <div x-show="selectedTemplate === 'msg_custom'" x-cloak class="pl-6">
                            <textarea name="custom_message" x-model="customMessage" rows="4" maxlength="500"
                                placeholder="Write your personal message here..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)"></textarea>
                            <p class="text-xs text-gray-400 mt-1"><span x-text="customMessage.length">0</span>/500 characters</p>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" @click="showModal = false"
                        class="px-5 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="submitting || (selectedTemplate === 'msg_custom' && customMessage.length < 10)"
                        :class="(submitting || (selectedTemplate === 'msg_custom' && customMessage.length < 10)) && 'opacity-50 cursor-not-allowed'"
                        class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                        <span x-show="!submitting">Send</span>
                        <span x-show="submitting" x-cloak>Sending...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
