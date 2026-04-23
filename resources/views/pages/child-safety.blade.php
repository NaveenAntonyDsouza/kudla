<x-layouts.app title="Child Safety Policy">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-2">Child Safety Policy</h1>
        <p class="text-sm text-gray-500 mb-8">Last Updated: October 2025</p>
        <div class="prose prose-sm max-w-none text-gray-700 space-y-6">
            <h2 class="text-lg font-semibold text-gray-900">1. Our Commitment to Child Safety</h2>
            <p>{{ config('app.name') }} is a service intended exclusively for consenting adults aged 18 and over seeking matrimonial alliances. We maintain a <strong>zero-tolerance policy</strong> for any activity that facilitates or promotes Child Sexual Abuse and Exploitation (CSAE) or endangers a minor. This includes CSAM, grooming, solicitation, and underage users.</p>

            <h2 class="text-lg font-semibold text-gray-900">2. Age Requirement</h2>
            <p>Our service is <strong>strictly for users aged 18 and older</strong>. It is a direct violation of our Terms of Service for anyone under 18 to register or use the app. Accounts found violating this will be immediately terminated.</p>

            <h2 class="text-lg font-semibold text-gray-900">3. Prohibited Content and Behavior</h2>
            <p>The following will result in immediate and permanent ban:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li><strong>CSAM:</strong> Possessing, creating, sharing, or promoting any content depicting sexual abuse of a minor.</li>
                <li><strong>Grooming:</strong> Any attempt to build emotional or sexual relationship with a minor for exploitation.</li>
                <li><strong>Solicitation:</strong> Any attempt to solicit sexual content/acts from a minor.</li>
                <li><strong>Child Endangerment:</strong> Content or behavior threatening physical, sexual, or emotional safety of a minor.</li>
                <li><strong>Underage Presence:</strong> Any user found to be under 18.</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900">4. How to Report Concerns</h2>
            <p><strong>In-App:</strong> Report any user directly from their profile using the "Report" or "Block" feature.</p>
            <p><strong>Email:</strong> Send a detailed report to <strong>{{ \App\Models\SiteSetting::getValue('email', 'info@example.com') }}</strong> with screenshots and user profile details.</p>
            <p>All reports are treated with strict confidentiality.</p>

            <h2 class="text-lg font-semibold text-gray-900">5. Our Moderation and Enforcement Process</h2>
            <p>We use automated detection tools and human moderation. Our trained safety team reviews reports and flagged content. Violations result in removal of offending content, immediate and permanent account termination, and ban from creating future accounts. We maintain a "one-strike" policy for severe violations.</p>

            <h2 class="text-lg font-semibold text-gray-900">6. Reporting to Law Enforcement</h2>
            <p>We report all instances of apparent CSAM to NCMEC and/or regional/national authorities (Indian Cyber Crime Reporting Portal). We fully cooperate with law enforcement investigations.</p>

            <h2 class="text-lg font-semibold text-gray-900">7. Contact Us</h2>
            <p>For questions about our child safety practices: <strong>{{ \App\Models\SiteSetting::getValue('email', 'info@example.com') }}</strong></p>
        </div>
    </div>
</x-layouts.app>
