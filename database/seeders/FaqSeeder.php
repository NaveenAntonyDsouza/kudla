<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            // Registration (4)
            [
                'question' => 'How do I register?',
                'answer' => 'Click \'Register Free\' on the homepage. Fill in your basic details across 5 simple steps, verify your phone number via OTP, and your profile is ready. You\'ll receive a unique profile ID upon completion.',
                'category' => 'Registration',
                'is_visible' => true,
                'display_order' => 1,
            ],
            [
                'question' => 'Is registration free?',
                'answer' => 'Yes, registration is completely free. You can create your profile, upload photos, and search for matches without any charge. Premium features like viewing contact details require a paid membership.',
                'category' => 'Registration',
                'is_visible' => true,
                'display_order' => 2,
            ],
            [
                'question' => 'Can someone else register on my behalf?',
                'answer' => 'Yes! A parent, sibling, or friend can create a profile on your behalf. Simply select the appropriate option in \'Profile Created By\' during registration.',
                'category' => 'Registration',
                'is_visible' => true,
                'display_order' => 3,
            ],
            [
                'question' => 'What is my Profile ID?',
                'answer' => 'Your Profile ID (e.g., AM100001) is your unique identifier. You can share this ID with potential matches or use it to search for specific profiles.',
                'category' => 'Registration',
                'is_visible' => true,
                'display_order' => 4,
            ],

            // Profile (4)
            [
                'question' => 'How do I edit my profile?',
                'answer' => 'After logging in, go to \'My Profile\' and click \'Edit\' on any section you want to update. Changes are saved immediately.',
                'category' => 'Profile',
                'is_visible' => true,
                'display_order' => 5,
            ],
            [
                'question' => 'How is profile completion calculated?',
                'answer' => 'Your profile completion is based on filling key sections: basic info (15%), religious info (10%), education (15%), family (10%), location (10%), lifestyle (5%), contact (10%), partner preferences (10%), profile photo (10%), and about me (5%).',
                'category' => 'Profile',
                'is_visible' => true,
                'display_order' => 6,
            ],
            [
                'question' => 'Can I delete my profile?',
                'answer' => 'Yes. Go to Profile Settings and select \'Delete Account\'. You\'ll be asked to confirm and provide a reason. Your profile will be soft-deleted and can be recovered within 30 days by contacting support.',
                'category' => 'Profile',
                'is_visible' => true,
                'display_order' => 7,
            ],
            [
                'question' => 'How do I upload photos?',
                'answer' => 'Go to \'Manage Photos\' in your profile. You can upload 1 profile photo, up to 9 album photos, and up to 3 family photos. Supported formats: JPG, PNG, WebP (max 30MB each).',
                'category' => 'Profile',
                'is_visible' => true,
                'display_order' => 8,
            ],

            // Search (3)
            [
                'question' => 'How do I search for matches?',
                'answer' => 'Use the \'Search\' page to filter profiles by age, religion, community, education, location, and more. You can also search by Profile ID or keywords.',
                'category' => 'Search',
                'is_visible' => true,
                'display_order' => 9,
            ],
            [
                'question' => 'Can I save my search criteria?',
                'answer' => 'Yes! Click \'Save this Search\' on the search results page, give it a name, and access it later from \'Saved Searches\'.',
                'category' => 'Search',
                'is_visible' => true,
                'display_order' => 10,
            ],
            [
                'question' => 'Why can\'t I see some profiles?',
                'answer' => 'Profiles may be hidden if: the user has hidden their profile from search, you have blocked or ignored them, or they have blocked you.',
                'category' => 'Search',
                'is_visible' => true,
                'display_order' => 11,
            ],

            // Privacy (3)
            [
                'question' => 'Who can see my photos?',
                'answer' => 'You control this in \'Photo Privacy Settings\'. Options: Visible to All, Visible to Interest Accepted (only mutual interests), or Hidden. You can control profile, album, and family photos separately.',
                'category' => 'Privacy',
                'is_visible' => true,
                'display_order' => 12,
            ],
            [
                'question' => 'Can I hide my profile from search?',
                'answer' => 'Yes. Go to Profile Settings and toggle \'Hide from Search Results\'. Your profile won\'t appear in searches but direct links (Profile ID) still work.',
                'category' => 'Privacy',
                'is_visible' => true,
                'display_order' => 13,
            ],
            [
                'question' => 'Is my contact information visible to everyone?',
                'answer' => 'No. Contact details (phone, email, address) are only visible to premium members who have a mutual interest (accepted) with you. Free members see a locked/blurred view.',
                'category' => 'Privacy',
                'is_visible' => true,
                'display_order' => 14,
            ],

            // Payment (4)
            [
                'question' => 'What are the membership plans?',
                'answer' => 'We offer 4 plans: Free (basic access), Gold (3 months), Diamond (6 months), and Diamond Plus (12 months). Premium plans unlock contact viewing, more daily interests, and profile highlighting.',
                'category' => 'Payment',
                'is_visible' => true,
                'display_order' => 15,
            ],
            [
                'question' => 'How do I upgrade to premium?',
                'answer' => 'Go to the \'Plans\' page, select your desired plan, and complete payment via Razorpay (credit/debit card, UPI, net banking). Your membership activates instantly.',
                'category' => 'Payment',
                'is_visible' => true,
                'display_order' => 16,
            ],
            [
                'question' => 'Can I get a refund?',
                'answer' => 'Refunds are considered on a case-by-case basis within 7 days of purchase if no premium features have been used. Contact us at our support email.',
                'category' => 'Payment',
                'is_visible' => true,
                'display_order' => 17,
            ],
            [
                'question' => 'What happens when my membership expires?',
                'answer' => 'Your profile remains active, but premium features (contact viewing, higher interest limits, profile highlighting) are disabled. You can renew anytime.',
                'category' => 'Payment',
                'is_visible' => true,
                'display_order' => 18,
            ],

            // Communication (4)
            [
                'question' => 'How do I send an interest?',
                'answer' => 'Visit a profile and click \'Send Interest\'. Choose a message template or write a custom message (premium only). The other person will be notified and can accept or decline.',
                'category' => 'Communication',
                'is_visible' => true,
                'display_order' => 19,
            ],
            [
                'question' => 'What is the daily interest limit?',
                'answer' => 'Free users: 5 interests per day. Gold: 20/day. Diamond and Diamond Plus: 50/day. Limits reset at midnight IST.',
                'category' => 'Communication',
                'is_visible' => true,
                'display_order' => 20,
            ],
            [
                'question' => 'Can I cancel a sent interest?',
                'answer' => 'Yes, within 24 hours of sending, before the receiver responds. After 24 hours, cancellation is not possible.',
                'category' => 'Communication',
                'is_visible' => true,
                'display_order' => 21,
            ],
            [
                'question' => 'What is a silent decline?',
                'answer' => 'When declining an interest, you can choose \'Silent Decline\'. The sender won\'t receive a decline notification and your profile is hidden from their future searches.',
                'category' => 'Communication',
                'is_visible' => true,
                'display_order' => 22,
            ],

            // Contact (3)
            [
                'question' => 'How do I contact support?',
                'answer' => 'Please refer to the contact details on the Contact Us page. We\'re typically available Monday to Saturday, 9 AM to 6 PM.',
                'category' => 'Contact',
                'is_visible' => true,
                'display_order' => 23,
            ],
            [
                'question' => 'How do I report a fake profile?',
                'answer' => 'Visit the profile, click the three-dot menu, and select \'Report Profile\'. Our team will review and take action within 48 hours.',
                'category' => 'Contact',
                'is_visible' => true,
                'display_order' => 24,
            ],
            [
                'question' => 'How do I verify my ID?',
                'answer' => 'Go to \'ID Proof\' in your profile, select document type (Aadhaar, Passport, Voter ID, or Driving License), upload the document, and submit. Our team verifies within 24-48 hours.',
                'category' => 'Contact',
                'is_visible' => true,
                'display_order' => 25,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
