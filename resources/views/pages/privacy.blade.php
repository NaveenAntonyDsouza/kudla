<x-layouts.app title="Privacy Policy">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-8">Privacy Policy</h1>
        <div class="prose prose-sm max-w-none text-gray-700 space-y-6">
            <p>Last updated: {{ date('F Y') }}</p>

            <h2 class="text-lg font-semibold text-gray-900">1. Information We Collect</h2>
            <p>When you register on {{ config('app.name') }}, we collect personal information including your name, date of birth, gender, contact details, photographs, educational qualifications, professional details, family information, and partner preferences. This information is necessary to provide our matrimonial matchmaking services.</p>

            <h2 class="text-lg font-semibold text-gray-900">2. How We Use Your Information</h2>
            <p>Your information is used to:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Create and manage your matrimonial profile</li>
                <li>Display your profile to other registered members seeking a match</li>
                <li>Enable communication between interested members</li>
                <li>Send notifications about interest messages, profile views, and matches</li>
                <li>Improve our services and user experience</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900">3. Information Sharing</h2>
            <p>Your profile information is visible to other registered members of {{ config('app.name') }}. Contact details (phone number, email, address) are only shared based on your privacy settings and interest acceptance. We do not sell or share your personal information with third parties for marketing purposes.</p>

            <h2 class="text-lg font-semibold text-gray-900">4. Data Security</h2>
            <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. Your password is encrypted and never stored in plain text.</p>

            <h2 class="text-lg font-semibold text-gray-900">5. Your Rights</h2>
            <p>You have the right to:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Access and update your profile information at any time</li>
                <li>Control who can see your profile through privacy settings</li>
                <li>Hide your profile temporarily from search results</li>
                <li>Delete your account and request removal of your data</li>
                <li>Opt out of email notifications</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900">6. Cookies</h2>
            <p>We use essential cookies to maintain your login session and remember your preferences. We do not use third-party tracking cookies for advertising.</p>

            <h2 class="text-lg font-semibold text-gray-900">7. Contact Us</h2>
            <p>For any privacy-related concerns, please contact us at <strong>{{ \App\Models\SiteSetting::getValue('email', 'info@example.com') }}</strong>.</p>
        </div>
    </div>
</x-layouts.app>
