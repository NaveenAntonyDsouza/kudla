<x-layouts.public title="Home">
    @php
        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
        $siteTagline = \App\Models\SiteSetting::getValue('tagline', 'Find Your Perfect Match');
    @endphp
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center">
            <h1 class="text-4xl font-serif font-bold text-gray-900">Welcome to {{ $siteName }}</h1>
            <p class="mt-4 text-lg text-gray-600">{{ $siteTagline }}</p>
            <div class="mt-8 flex justify-center gap-4">
                <a href="/register" class="px-6 py-3 text-white font-semibold rounded-lg transition-colors bg-(--color-primary) hover:bg-(--color-primary-hover)">Register Free</a>
                <a href="/login" class="px-6 py-3 border font-semibold rounded-lg transition-colors border-(--color-primary) text-(--color-primary) hover:bg-(--color-primary-light)">Login</a>
            </div>
        </div>
    </div>
</x-layouts.public>
