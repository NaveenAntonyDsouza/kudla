<x-layouts.app title="Advertise With Us">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-8">Advertise With Us</h1>
        <div class="prose prose-sm max-w-none text-gray-700 space-y-6">
            <p>Reach thousands of potential customers by advertising on {{ config('app.name') }}. Our platform attracts users looking for matrimonial services and related products.</p>
            <p>For advertising inquiries, please contact us at <a href="mailto:{{ \App\Models\SiteSetting::getValue('email', 'info@example.com') }}" class="text-(--color-primary) hover:underline">{{ \App\Models\SiteSetting::getValue('email', 'info@example.com') }}</a></p>
        </div>
    </div>
</x-layouts.app>
