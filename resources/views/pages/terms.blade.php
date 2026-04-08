<x-layouts.app title="Terms of Service">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-8">Terms of Service</h1>
        <div class="prose prose-sm max-w-none text-gray-700 space-y-6">
            <p>Last updated: {{ date('F Y') }}</p>

            <h2 class="text-lg font-semibold text-gray-900">1. Acceptance of Terms</h2>
            <p>By registering on {{ config('app.name') }}, you agree to these Terms of Service. This platform is strictly for matrimonial purposes only and is not a dating platform.</p>

            <h2 class="text-lg font-semibold text-gray-900">2. Eligibility</h2>
            <ul class="list-disc pl-5 space-y-1">
                <li>You must be at least 18 years of age</li>
                <li>You must be legally eligible for marriage under Indian law</li>
                <li>You must provide accurate and truthful information</li>
                <li>Only one account per person is allowed</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900">3. User Responsibilities</h2>
            <p>You agree to:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Provide accurate personal information in your profile</li>
                <li>Upload only your own photographs</li>
                <li>Not misuse the platform for any purpose other than seeking a matrimonial match</li>
                <li>Treat other members with respect and dignity</li>
                <li>Not share contact information of other members with third parties</li>
                <li>Not harass, abuse, or send inappropriate messages to other members</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900">4. Profile Verification</h2>
            <p>{{ config('app.name') }} reserves the right to verify profiles and request identity proof. Profiles found to contain false information may be suspended or permanently removed without notice.</p>

            <h2 class="text-lg font-semibold text-gray-900">5. Membership & Payments</h2>
            <p>Free registration provides basic access. Premium memberships offer additional features as described on the Membership Plans page. All payments are non-refundable once the membership is activated.</p>

            <h2 class="text-lg font-semibold text-gray-900">6. Account Termination</h2>
            <p>We reserve the right to suspend or terminate accounts that violate these terms, contain fraudulent information, or engage in inappropriate behavior. You may delete your own account at any time from Profile Settings.</p>

            <h2 class="text-lg font-semibold text-gray-900">7. Limitation of Liability</h2>
            <p>{{ config('app.name') }} acts as a platform to connect individuals seeking matrimonial matches. We do not guarantee the accuracy of member profiles, the outcome of any interaction, or the success of any match. Members are advised to exercise due diligence before proceeding with any match.</p>

            <h2 class="text-lg font-semibold text-gray-900">8. Contact</h2>
            <p>For questions about these terms, contact us at <strong>{{ \App\Models\SiteSetting::getValue('email', 'info@kudlamatrimony.com') }}</strong>.</p>
        </div>
    </div>
</x-layouts.app>
