<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class DummyContentSeeder extends Seeder
{
    public function run(): void
    {
        // ── FAQs ──────────────────────────────────────────────
        $faqs = [
            [
                'question' => 'How do I register on the platform?',
                'answer' => 'Registration is free and takes just 2 minutes. Click "Register Free" on the homepage, fill in your basic details like name, gender, date of birth, phone number, email, and create a password. After registration, you can complete your profile with additional details like education, family, and partner preferences.',
                'category' => 'Registration',
                'display_order' => 1,
            ],
            [
                'question' => 'Is my personal information safe and private?',
                'answer' => 'Absolutely! We take privacy very seriously. Your contact details (phone number, email, address) are never shown to other members unless you choose to share them. All profiles are manually verified by our team. You have full control over your photo privacy settings and can choose who sees your photos.',
                'category' => 'Privacy',
                'display_order' => 2,
            ],
            [
                'question' => 'What are the membership plans available?',
                'answer' => 'We offer Free, Silver, Gold, Diamond, and Diamond Plus plans. Free members can browse profiles and receive interests. Paid members get additional benefits like sending more interests, viewing contact details, priority profile highlighting in search results, and access to advanced matching features. Visit our Membership page for detailed pricing.',
                'category' => 'Membership',
                'display_order' => 3,
            ],
            [
                'question' => 'How does the matchmaking algorithm work?',
                'answer' => 'Our smart matching system evaluates 12 criteria including age, religion, community, education, location, and partner preferences to calculate a compatibility score. The more complete your profile and partner preferences, the better your matches will be. You can view your top matches on the My Matches page.',
                'category' => 'Matching',
                'display_order' => 4,
            ],
            [
                'question' => 'How do I send an interest to a profile?',
                'answer' => 'When you find a profile you like, click the "Send Interest" button on their profile card or profile page. The other person will receive a notification and can accept or decline your interest. Once accepted, you can view each other\'s contact details (for paid members) and communicate further.',
                'category' => 'Communication',
                'display_order' => 5,
            ],
            [
                'question' => 'Can I search for profiles by community or caste?',
                'answer' => 'Yes! We support community-based search across multiple religions. You can search by denomination (for Christians), caste (for Hindus and Jains), sect (for Muslims), and many more filters including education, occupation, location, and lifestyle preferences.',
                'category' => 'Search',
                'display_order' => 6,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['is_visible' => true])
            );
        }

        $this->command->info('✓ 6 FAQs seeded.');

        // ── Success Stories / Testimonials ─────────────────────
        $stories = [
            [
                'couple_names' => 'Rahul & Priya',
                'story' => 'We found each other through this platform and instantly connected over our shared values and love for family. After months of getting to know each other, we tied the knot in a beautiful ceremony. Thank you for helping us find our perfect match!',
                'location' => 'Mangalore, Karnataka',
                'display_order' => 1,
            ],
            [
                'couple_names' => 'Anil & Sneha',
                'story' => 'I was skeptical about online matrimony at first, but this platform changed my mind. The profile verification gave us confidence, and the matching algorithm connected us with the right person. We are happily married now and grateful for this wonderful service.',
                'location' => 'Udupi, Karnataka',
                'display_order' => 2,
            ],
            [
                'couple_names' => 'Joseph & Maria',
                'story' => 'Our families registered us on this platform, and within weeks we found each other. The community-focused search made it easy to find someone who shares our faith and traditions. We celebrated our wedding last year and could not be happier!',
                'location' => 'Kasaragod, Kerala',
                'display_order' => 3,
            ],
            [
                'couple_names' => 'Suresh & Kavitha',
                'story' => 'After searching for a long time, we finally found our perfect match here. The detailed profiles and partner preference matching helped us connect with the right person. We are now blessed with a beautiful family and recommend this platform to everyone.',
                'location' => 'Bangalore, Karnataka',
                'display_order' => 4,
            ],
        ];

        foreach ($stories as $story) {
            Testimonial::updateOrCreate(
                ['couple_names' => $story['couple_names']],
                array_merge($story, ['is_visible' => true])
            );
        }

        $this->command->info('✓ 4 Success Stories seeded.');

        // ── App Store URLs (dummy) ────────────────────────────
        SiteSetting::setValue('app_play_store_url', 'https://play.google.com/store/apps');
        SiteSetting::setValue('app_apple_store_url', 'https://apps.apple.com');

        $this->command->info('✓ App Store URLs seeded.');

        // ── Update Stats (if still 0) ─────────────────────────
        if ((int) SiteSetting::getValue('years_of_service', '0') === 0) {
            SiteSetting::setValue('years_of_service', '1');
        }

        $this->command->info('✓ Stats updated.');
        $this->command->info('');
        $this->command->info('All dummy content seeded successfully!');
        $this->command->info('Note: For hero image, upload via Admin > Settings > Homepage Content.');
    }
}
