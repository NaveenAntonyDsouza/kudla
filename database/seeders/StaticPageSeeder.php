<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Illuminate\Database\Seeder;

class StaticPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'is_system' => true,
                'show_in_footer' => true,
                'sort_order' => 1,
                'content' => <<<'HTML'
<p>Last updated: {{ current_month_year }}</p>

<h2>1. Information We Collect</h2>
<p>When you register on {{ app_name }}, we collect personal information including your name, date of birth, gender, contact details, photographs, educational qualifications, professional details, family information, and partner preferences. This information is necessary to provide our matrimonial matchmaking services.</p>

<h2>2. How We Use Your Information</h2>
<p>Your information is used to:</p>
<ul>
<li>Create and manage your matrimonial profile</li>
<li>Display your profile to other registered members seeking a match</li>
<li>Enable communication between interested members</li>
<li>Send notifications about interest messages, profile views, and matches</li>
<li>Improve our services and user experience</li>
</ul>

<h2>3. Information Sharing</h2>
<p>Your profile information is visible to other registered members of {{ app_name }}. Contact details (phone number, email, address) are only shared based on your privacy settings and interest acceptance. We do not sell or share your personal information with third parties for marketing purposes.</p>

<h2>4. Data Security</h2>
<p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. Your password is encrypted and never stored in plain text.</p>

<h2>5. Your Rights</h2>
<p>You have the right to:</p>
<ul>
<li>Access and update your profile information at any time</li>
<li>Control who can see your profile through privacy settings</li>
<li>Hide your profile temporarily from search results</li>
<li>Delete your account and request removal of your data</li>
<li>Opt out of email notifications</li>
</ul>

<h2>6. Cookies</h2>
<p>We use essential cookies to maintain your login session and remember your preferences. We do not use third-party tracking cookies for advertising.</p>

<h2>7. Contact Us</h2>
<p>For any privacy-related concerns, please contact us at <strong>{{ email }}</strong>.</p>
HTML,
            ],
            [
                'slug' => 'terms-condition',
                'title' => 'Terms of Service',
                'is_system' => true,
                'show_in_footer' => true,
                'sort_order' => 2,
                'content' => <<<'HTML'
<p>Last updated: {{ current_month_year }}</p>

<h2>1. Acceptance of Terms</h2>
<p>By registering on {{ app_name }}, you agree to these Terms of Service. This platform is strictly for matrimonial purposes only and is not a dating platform.</p>

<h2>2. Eligibility</h2>
<ul>
<li>You must be at least 18 years of age</li>
<li>You must be legally eligible for marriage under Indian law</li>
<li>You must provide accurate and truthful information</li>
<li>Only one account per person is allowed</li>
</ul>

<h2>3. User Responsibilities</h2>
<p>You agree to:</p>
<ul>
<li>Provide accurate personal information in your profile</li>
<li>Upload only your own photographs</li>
<li>Not misuse the platform for any purpose other than seeking a matrimonial match</li>
<li>Treat other members with respect and dignity</li>
<li>Not share contact information of other members with third parties</li>
<li>Not harass, abuse, or send inappropriate messages to other members</li>
</ul>

<h2>4. Profile Verification</h2>
<p>{{ app_name }} reserves the right to verify profiles and request identity proof. Profiles found to contain false information may be suspended or permanently removed without notice.</p>

<h2>5. Membership &amp; Payments</h2>
<p>Free registration provides basic access. Premium memberships offer additional features as described on the Membership Plans page. All payments are non-refundable once the membership is activated.</p>

<h2>6. Account Termination</h2>
<p>We reserve the right to suspend or terminate accounts that violate these terms, contain fraudulent information, or engage in inappropriate behavior. You may delete your own account at any time from Profile Settings.</p>

<h2>7. Limitation of Liability</h2>
<p>{{ app_name }} acts as a platform to connect individuals seeking matrimonial matches. We do not guarantee the accuracy of member profiles, the outcome of any interaction, or the success of any match. Members are advised to exercise due diligence before proceeding with any match.</p>

<h2>8. Contact</h2>
<p>For questions about these terms, contact us at <strong>{{ email }}</strong>.</p>
HTML,
            ],
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'is_system' => true,
                'show_in_footer' => true,
                'sort_order' => 3,
                'content' => <<<'HTML'
<p><strong>{{ app_name }}</strong> is a modern matchmaking platform connecting people who are legally qualified for marriage. We help individuals find compatible life partners based on their preferences, values, and goals.</p>
<p>Users can register and search according to their specific criteria on age, religion, caste, height, community, profession, location, and much more — on their computer, tablet, or mobile.</p>
<h2>Our Objective</h2>
<p>To provide a superior, affordable, and trustworthy online matchmaking experience to our community.</p>
HTML,
            ],
            [
                'slug' => 'refund-policy',
                'title' => 'Refund Policy',
                'is_system' => true,
                'show_in_footer' => true,
                'sort_order' => 4,
                'content' => <<<'HTML'
<p>{{ app_name }} will not refund any payment to any member for any reason whatsoever except in the case of error on {{ app_name }}'s part.</p>

<p>{{ app_name }} will not refund any member who has decided that they no longer wish to use {{ app_name }}. A refund can NOT be given in part or whole for any subscription used or not used by any member for whatever reason. Users who wish to cancel their subscription are not permitted to seek a partial or full refund for any reason. Please read the full terms for our refund policy below. Agreeing to our terms and conditions when you create an account means you agree to our refund policy.</p>

<ul>
<li>Anyone found to be using the website under the age of 18 will be banned immediately without refund.</li>
<li>You agree to include FACTUAL information about yourself which is a TRUE and ACCURATE representation of yourself. You may NOT use fake pictures and any other misleading or untruthful personal information. Misrepresentation can result in a permanent ban without refund.</li>
<li>Abuse to staff will NOT be tolerated in any way, shape or form, and will result in a permanent ban without refund.</li>
<li>It is your responsibility to communicate with members in a polite, respectful manner. Rude, offensive, or inappropriate messages will result in a permanent ban without refund.</li>
<li>A refund can NOT be given on the basis of members choosing not to correspond with you.</li>
<li>It is YOUR responsibility to CANCEL your membership when you are no longer interested in being a member. {{ app_name }} will NOT refund any members who have failed to do so.</li>
<li>ALL members — whether free or paid — MUST adhere to the rules mentioned in our terms and conditions. Failure to do so can result in a ban without refund.</li>
<li>Profiles are approved within 24 hours and any profiles deemed suspicious or fraudulent will be rejected immediately with a permanent ban and NO refund.</li>
<li>You will not engage in gathering personal information such as email addresses, telephone numbers, addresses, financial information or any other kind of information of our members.</li>
</ul>
HTML,
            ],
            [
                'slug' => 'child-safety',
                'title' => 'Child Safety Policy',
                'is_system' => true,
                'show_in_footer' => true,
                'sort_order' => 5,
                'content' => <<<'HTML'
<p><em>Last Updated: October 2025</em></p>

<h2>1. Our Commitment to Child Safety</h2>
<p>{{ app_name }} is a service intended exclusively for consenting adults aged 18 and over seeking matrimonial alliances. We maintain a <strong>zero-tolerance policy</strong> for any activity that facilitates or promotes Child Sexual Abuse and Exploitation (CSAE) or endangers a minor. This includes CSAM, grooming, solicitation, and underage users.</p>

<h2>2. Age Requirement</h2>
<p>Our service is <strong>strictly for users aged 18 and older</strong>. It is a direct violation of our Terms of Service for anyone under 18 to register or use the app. Accounts found violating this will be immediately terminated.</p>

<h2>3. Prohibited Content and Behavior</h2>
<p>The following will result in immediate and permanent ban:</p>
<ul>
<li><strong>CSAM:</strong> Possessing, creating, sharing, or promoting any content depicting sexual abuse of a minor.</li>
<li><strong>Grooming:</strong> Any attempt to build emotional or sexual relationship with a minor for exploitation.</li>
<li><strong>Solicitation:</strong> Any attempt to solicit sexual content/acts from a minor.</li>
<li><strong>Child Endangerment:</strong> Content or behavior threatening physical, sexual, or emotional safety of a minor.</li>
<li><strong>Underage Presence:</strong> Any user found to be under 18.</li>
</ul>

<h2>4. How to Report Concerns</h2>
<p><strong>In-App:</strong> Report any user directly from their profile using the "Report" or "Block" feature.</p>
<p><strong>Email:</strong> Send a detailed report to <strong>{{ email }}</strong> with screenshots and user profile details.</p>
<p>All reports are treated with strict confidentiality.</p>

<h2>5. Our Moderation and Enforcement Process</h2>
<p>We use automated detection tools and human moderation. Our trained safety team reviews reports and flagged content. Violations result in removal of offending content, immediate and permanent account termination, and ban from creating future accounts. We maintain a "one-strike" policy for severe violations.</p>

<h2>6. Reporting to Law Enforcement</h2>
<p>We report all instances of apparent CSAM to NCMEC and/or regional/national authorities (Indian Cyber Crime Reporting Portal). We fully cooperate with law enforcement investigations.</p>

<h2>7. Contact Us</h2>
<p>For questions about our child safety practices: <strong>{{ email }}</strong></p>
HTML,
            ],
            [
                'slug' => 'report-misuse',
                'title' => 'Report Misuse',
                'is_system' => true,
                'show_in_footer' => true,
                'sort_order' => 6,
                'content' => <<<'HTML'
<p>We have stringent policies against those members who misuse our services. For us to act and stop any abuse or violation, we need all your support and extended co-operation.</p>

<p>Alert us by writing to us: <strong>{{ email }}</strong>, for us to initiate necessary action against the offender.</p>

<p>Also, while reporting such complaints, please provide all evidence including any e-mail (full header of the e-mail) you may have received.</p>

<p><strong>Note: Your personal details will not be disclosed.</strong></p>

<h2>The following are considered as abuse of our services:</h2>
<ul>
<li>If a member sends obscene or unfitting e-mails</li>
<li>If a member seeks marriage proposal with a fraudulent or obscene profile</li>
<li>If a member provokes you with harassing email remarks</li>
<li>If a photograph of a member is misrepresented or not real</li>
<li>If a member found to be using our services for private business purposes with advertisements or other business material</li>
</ul>

<p>Please report anything offensive, fraudulent or suspicious with proper evidence to us by sending mail to <strong>{{ email }}</strong></p>
HTML,
            ],
            [
                'slug' => 'add-with-us',
                'title' => 'Advertise With Us',
                'is_system' => false,
                'show_in_footer' => false,
                'sort_order' => 7,
                'content' => <<<'HTML'
<p>Reach thousands of potential customers by advertising on {{ app_name }}. Our platform attracts users from Coastal Karnataka and Kasaragod region looking for matrimonial services and related products.</p>
<p>For advertising inquiries, please contact us at <strong>{{ email }}</strong></p>
HTML,
            ],
        ];

        foreach ($pages as $page) {
            StaticPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
