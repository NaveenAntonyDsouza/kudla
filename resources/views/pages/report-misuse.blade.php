<x-layouts.app title="Report Misuse">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-8">Report Misuse</h1>
        <div class="prose prose-sm max-w-none text-gray-700 space-y-6">
            <p>We have stringent policies against those members who misuse our services. For us to act and stop any abuse or violation, we need all your support and extended co-operation.</p>

            <p>Alert us by writing to us: <strong><a href="mailto:{{ \App\Models\SiteSetting::getValue('email', 'info@kudlamatrimony.com') }}" class="text-(--color-primary) hover:underline">{{ \App\Models\SiteSetting::getValue('email', 'info@kudlamatrimony.com') }}</a></strong>, for us to initiate necessary action against the offender.</p>

            <p>Also, while reporting such complaints, please provide all evidence including any e-mail (full header of the e-mail) you may have received.</p>

            <p><strong>Note: Your personal details will not be disclosed.</strong></p>

            <h2 class="text-lg font-semibold text-gray-900">The following are considered as abuse of our services:</h2>
            <ul class="list-disc pl-5 space-y-2">
                <li>If a member sends obscene or unfitting e-mails</li>
                <li>If a member seeks marriage proposal with a fraudulent or obscene profile</li>
                <li>If a member provokes you with harassing email remarks</li>
                <li>If a photograph of a member is misrepresented or not real</li>
                <li>If a member found to be using our services for private business purposes with advertisements or other business material</li>
            </ul>

            <p>Please report anything offensive, fraudulent or suspicious with proper evidence to us by sending mail to <strong><a href="mailto:{{ \App\Models\SiteSetting::getValue('email', 'info@kudlamatrimony.com') }}" class="text-(--color-primary) hover:underline">{{ \App\Models\SiteSetting::getValue('email', 'info@kudlamatrimony.com') }}</a></strong></p>
        </div>
    </div>
</x-layouts.app>
