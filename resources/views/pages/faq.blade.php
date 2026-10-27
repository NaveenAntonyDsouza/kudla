<x-layouts.app title="Help & FAQ">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-2">Help & Frequently Asked Questions</h1>
        <p class="text-gray-500 mb-10">Find answers to common questions about {{ config('app.name') }}.</p>

        @php
            // Kept true to how the site works today (Oct 2026 review): plans,
            // contacts and photo choices follow this site's own settings.
            $siteName = \App\Models\SiteSetting::getValue('site_name', config('app.name'));
            $free = \App\Models\User::freeMembershipEnabled();
            $comingSoon = ! $free && \App\Support\PaidPlans::comingSoon();
            $freeLimit = \App\Services\InterestService::FREE_DAILY_LIMIT;
            $photoChoices = $free
                ? 'everyone, only members whose interest you accept, or no one'
                : 'everyone, only members whose interest you accept, only premium members, or no one';

            $faqs = [
                'Getting Started' => [
                    ["How do I register?", "Tap 'Register Free' and fill in four short steps: your basic details; religion and community; education and job; and location, contact and who is creating the profile. Then add a photo (you can also do it later) and confirm your email address with the code we send you. Registration is free."],
                    ["Can parents create a profile for their son or daughter?", "Yes. Parents, brothers and sisters can create and manage a profile for a son, daughter or sibling. Just choose who is creating the profile during registration."],
                    ["How do I complete my profile?", "Go to My Profile > View & Edit Profile and fill in all 9 sections. Families decide whom to contact from these details, and profiles with a photo are viewed far more often."],
                    ["How do I upload photos?", "Go to Manage Photos. You can add one profile photo, up to 9 album photos and up to 3 family photos, and choose who can see them: {$photoChoices}."],
                    ["How do I verify my profile?", "Go to Submit ID Proof and upload a government ID such as Aadhaar, passport, voter ID or driving licence. Our team reviews it."],
                ],
                'Searching & Matching' => [
                    ["How do I search for profiles?", "Use the Search menu: Quick Search (age, height and community), Advanced Search (every filter), Keyword Search (any word, such as a job, a town or a community) and Search by ID. You can also browse profiles by community, mother tongue, place or profession."],
                    ["Can I save a search?", "Yes. On a search results page, tap Save and give the search a name. You will find it again under Search > Saved Searches."],
                    ["How does matching work?", "My Matches compares your partner preferences with each member's profile: religion and community, age, mother tongue, education, job, height, where they live and work, marital status, food habits and family status. Each match gets a score out of 100, so the closest matches come first."],
                    ["What are Mutual Matches?", "Mutual Matches are members whose profile fits your preferences and whose preferences your profile fits too. They are usually the best people to contact first."],
                ],
                'Interest & Communication' => [
                    ["How do I send an interest?", "Open a profile and tap 'Send Interest'. Free members can send up to {$freeLimit} interests a day" . ($free || $comingSoon ? '.' : '; paid plans allow more.')],
                    ["What happens after I send an interest?", "The member gets a notification and an email, and can accept or decline, even straight from the email. You are told when they accept. Interests that get no reply close by themselves after a while, so you can move on."],
                    ["When can we chat or call each other?", $free
                        ? "Once one of you accepts the other's interest, you can chat on the site and see each other's phone number and WhatsApp. Until then, nobody sees your contact details."
                        : "Once one of you accepts the other's interest, you can chat on the site. Members on a paid plan can also see each other's phone number and WhatsApp. Until an interest is accepted, nobody sees your contact details."],
                    ["Can I withdraw an interest I sent?", "Yes. Go to Interests, find it among your sent interests, and cancel it while it is still waiting for a reply."],
                ],
                'Privacy & Safety' => [
                    ["Who can see my profile?", "Visitors who are not logged in see only a short summary card and must log in to view a profile. Members can view your profile, but your phone number and WhatsApp only after you both accept an interest. In Settings > Who Can See My Profile you can limit this further, and in Manage Photos you choose who sees your photos."],
                    ["Can I hide my profile for a while?", "Yes. Go to Settings > Hide Profile. Your profile stays out of all search results until you show it again."],
                    ["Can I block or report someone?", "Yes. On any profile you can block the member, so you no longer see each other and neither of you can send an interest, and you can report anything that worries you to our team."],
                    ["How do I stay safe?", "{$siteName} will never ask for your password or OTP. Never send money to someone you have not met in person with your family, talk on video before you get attached, and meet for the first time in a public place with a family member."],
                    ["How do I delete my account?", "Go to Settings > Delete Account. Your profile is hidden from everyone straight away and you are signed out. Your personal details are permanently deleted after 90 days; to come back before then, just contact us."],
                ],
                'Membership & Payment' => $free ? [
                    ["Is it free?", "Yes. Right now every feature on {$siteName} is free for all members: registration, search, interests, chat, and seeing each other's phone number and WhatsApp once you both accept an interest."],
                ] : ($comingSoon ? [
                    ["Is registration free?", "Yes. Registration, creating your profile, searching and sending interests are free."],
                    ["Are there paid plans?", "Paid plans are coming soon. The Membership Plans page will show them, with their prices, as soon as they are available."],
                ] : [
                    ["Is registration free?", "Yes. Registration, creating your profile, searching and sending interests are free."],
                    ["What do paid plans offer?", "Paid plans add things like seeing members' phone numbers and WhatsApp after an interest is accepted, more interests a day and a highlighted profile. The Membership Plans page shows each plan, what it includes and its price."],
                    ["How can I pay?", "You can pay online with UPI, debit or credit cards, net banking or wallets, through a secure payment gateway. A plan starts as soon as the payment is confirmed and does not renew automatically."],
                    ["Can I get a refund?", "We refund duplicate payments, payments where your plan did not start, and wrong amounts charged by mistake. An active membership is not refundable. Email us within 30 days of the payment; our Refund Policy has the details."],
                ]),
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
