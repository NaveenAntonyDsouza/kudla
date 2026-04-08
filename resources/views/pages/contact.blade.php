<x-layouts.app title="Contact Us">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-2">Contact Us</h1>
        <p class="text-gray-500 mb-10">We'd love to hear from you. Reach out to us for any queries or support.</p>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Contact Form --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Send us a Message</h2>

                    <form method="POST" action="{{ route('contact.submit') }}">
                        @csrf
                        <div class="space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="float-field">
                                    <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required
                                        class="w-full rounded-lg border-gray-300 focus:ring-(--color-primary) focus:border-(--color-primary) text-sm">
                                    <label>Your Name *</label>
                                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="float-field">
                                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required
                                        class="w-full rounded-lg border-gray-300 focus:ring-(--color-primary) focus:border-(--color-primary) text-sm">
                                    <label>Your Email *</label>
                                    @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="float-field">
                                <input type="text" name="phone" value="{{ old('phone', auth()->user()?->phone) }}"
                                    class="w-full rounded-lg border-gray-300 focus:ring-(--color-primary) focus:border-(--color-primary) text-sm">
                                <label>Phone Number</label>
                            </div>

                            <div class="float-field">
                                <select name="subject" required
                                    class="w-full rounded-lg border-gray-300 focus:ring-(--color-primary) focus:border-(--color-primary) text-sm">
                                    <option value="">Select Subject</option>
                                    <option value="General Inquiry" {{ old('subject') === 'General Inquiry' ? 'selected' : '' }}>General Inquiry</option>
                                    <option value="Account Issue" {{ old('subject') === 'Account Issue' ? 'selected' : '' }}>Account Issue</option>
                                    <option value="Payment Issue" {{ old('subject') === 'Payment Issue' ? 'selected' : '' }}>Payment / Membership Issue</option>
                                    <option value="Profile Issue" {{ old('subject') === 'Profile Issue' ? 'selected' : '' }}>Profile Related Issue</option>
                                    <option value="Report Misuse" {{ old('subject') === 'Report Misuse' ? 'selected' : '' }}>Report Misuse / Fake Profile</option>
                                    <option value="Feedback" {{ old('subject') === 'Feedback' ? 'selected' : '' }}>Feedback / Suggestion</option>
                                    <option value="Other" {{ old('subject') === 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <label>Subject *</label>
                                @error('subject') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="float-field">
                                <textarea name="message" rows="5" required
                                    class="w-full rounded-lg border-gray-300 focus:ring-(--color-primary) focus:border-(--color-primary) text-sm">{{ old('message') }}</textarea>
                                <label>Your Message *</label>
                                @error('message') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit"
                                class="w-full sm:w-auto px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Contact Info Sidebar --}}
            <div class="space-y-6">
                @php
                    $contactEmail = \App\Models\SiteSetting::getValue('email', 'info@kudlamatrimony.com');
                    $contactPhone = \App\Models\SiteSetting::getValue('phone', '');
                    $whatsapp = \App\Models\SiteSetting::getValue('whatsapp', '');
                    $address = \App\Models\SiteSetting::getValue('address', '');
                @endphp

                <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Get in Touch</h3>
                    <div class="space-y-4">
                        @if($contactEmail)
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-(--color-primary) mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Email</p>
                                <a href="mailto:{{ $contactEmail }}" class="text-sm text-(--color-primary) hover:underline">{{ $contactEmail }}</a>
                            </div>
                        </div>
                        @endif

                        @if($contactPhone)
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-(--color-primary) mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Phone</p>
                                <a href="tel:{{ $contactPhone }}" class="text-sm text-(--color-primary) hover:underline">{{ $contactPhone }}</a>
                            </div>
                        </div>
                        @endif

                        @if($whatsapp)
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-gray-700">WhatsApp</p>
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" class="text-sm text-green-600 hover:underline">{{ $whatsapp }}</a>
                            </div>
                        </div>
                        @endif

                        @if($address)
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-(--color-primary) mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Address</p>
                                <p class="text-sm text-gray-600">{{ $address }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="bg-(--color-primary-light) rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Need Quick Help?</h3>
                    <p class="text-sm text-gray-600 mb-3">Check our FAQ section for instant answers to common questions.</p>
                    <a href="{{ route('faq') }}" class="text-sm font-medium text-(--color-primary) hover:underline">View FAQ &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
