<x-layouts.app title="Help & FAQ">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-2">Help & Frequently Asked Questions</h1>
        <p class="text-gray-500 mb-10">Find answers to common questions about {{ config('app.name') }}.</p>

        @php
            $siteName = config('app.name');
            $faqs = [
                'Getting Started' => [
                    ["How do I register?", "Click 'Register Free' on the homepage. You'll complete a 5-step registration process including personal details, religious information, and partner preferences. Registration is free."],
                    ["How do I complete my profile?", "After registration, go to Dashboard > View & Edit Profile. Complete all 9 sections for best results. Profiles with 80%+ completion get 3x more responses."],
                    ["How do I upload photos?", "Go to Dashboard > Manage Photos. You can upload a profile photo, up to 9 album photos, and up to 3 family photos."],
                    ["How do I verify my profile?", "Go to Dashboard > Submit ID Proof. Upload a government-issued ID (Aadhaar, Passport, Voter ID, or Driving License). Our team will verify within 24-48 hours."],
                ],
                'Searching & Matching' => [
                    ["How does the matching system work?", "Our matching engine compares your partner preferences with other profiles across 12 criteria including religion, age, education, occupation, and location. Each match gets a compatibility score (0-100%)."],
                    ["What are Mutual Matches?", "Mutual Matches are profiles where both you and the other person match each other's partner preferences. These are your highest-quality matches."],
                    ["How do I search for profiles?", "Go to Search > Partner Search to filter by detailed criteria. You can also use Keyword Search or Search by ID. The Discover section lets you browse by category (religion, location, etc.)."],
                    ["Can I save my search filters?", "Currently, you can load your saved partner preferences into the search form using the 'Load Partner Preferences' button."],
                ],
                'Interest & Communication' => [
                    ["How do I send an interest?", "Visit a profile and click 'Send Interest'. Choose a template message or write a custom one. You can send up to 5 interests per day (free plan)."],
                    ["What happens after I send an interest?", "The other person receives a notification. They can Accept, Decline, or ignore your interest. If accepted, you can start chatting."],
                    ["Can I chat with someone?", "Yes, but only after your interest is accepted by the other person. This ensures both parties are mutually interested before starting a conversation."],
                    ["Can I withdraw a sent interest?", "Yes, you can cancel a pending interest from your Sent Interests page."],
                ],
                'Privacy & Security' => [
                    ["Who can see my profile?", "By default, all registered members of the opposite gender can see your profile. You can restrict visibility in Settings > Profile Settings."],
                    ["Can I hide my profile temporarily?", "Yes, go to Settings > Hide Profile. Your profile will be hidden from all search results until you unhide it."],
                    ["Can I block someone?", "Yes, click 'Block Profile' on any profile. You will no longer see each other in search results, and neither can send interests."],
                    ["How do I delete my account?", "Go to Settings > Delete Profile. Select a reason and confirm. Your profile will be deactivated. Contact support if you need permanent deletion."],
                ],
                'Membership & Payment' => [
                    ["Is registration free?", "Yes, registration and basic profile creation are completely free."],
                    ["What do paid plans offer?", "Premium plans offer higher daily interest limits, the ability to view contact details, personalized messages, featured profile placement, and priority support."],
                    ["What payment methods are accepted?", "We accept payments through Razorpay (credit/debit cards, UPI, net banking, wallets)."],
                    ["Can I get a refund?", "Please contact our support team for refund requests. Refunds are processed on a case-by-case basis."],
                ],
            ];
        @endphp

        <div class="space-y-8" x-data="{ openFaq: null }">
            @foreach($faqs as $category => $questions)
                <div>
                    <h2 class="text-lg font-semibold text-(--color-primary) mb-4">{{ $category }}</h2>
                    <div class="space-y-2">
                        @foreach($questions as $index => $qa)
                            @php $faqId = Str::slug($category) . '-' . $index; @endphp
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <button @click="openFaq = openFaq === '{{ $faqId }}' ? null : '{{ $faqId }}'"
                                    class="w-full flex items-center justify-between px-5 py-3.5 text-left text-sm font-medium text-gray-900 hover:bg-gray-50 transition-colors">
                                    {{ $qa[0] }}
                                    <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="openFaq === '{{ $faqId }}' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="openFaq === '{{ $faqId }}'" x-cloak class="px-5 pb-4 text-sm text-gray-600 border-t border-gray-100">
                                    <p class="pt-3">{{ $qa[1] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Still have questions --}}
        <div class="mt-12 bg-(--color-primary-light) rounded-lg p-8 text-center">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Still have questions?</h3>
            <p class="text-sm text-gray-600 mb-4">We're here to help. Reach out to us anytime.</p>
            <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                Contact Us
            </a>
        </div>
    </div>
</x-layouts.app>
