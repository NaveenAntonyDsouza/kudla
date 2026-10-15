@php
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $waChat = \App\Support\WhatsApp::link('Hi ' . $siteName . ', I have a question.');
    $waDigits = \App\Support\WhatsApp::digits();
    $waShown = $waDigits ? '+' . substr($waDigits, 0, 2) . ' ' . substr($waDigits, 2, 5) . ' ' . substr($waDigits, 7) : null;
@endphp
<x-layouts.app :title="$siteName . ' WhatsApp Channel & Number'"
    :metaDescription="'Follow ' . $siteName . '\'s official WhatsApp channel for new profiles and news' . ($waShown ? ', or chat with us on WhatsApp at ' . $waShown : '') . '.'">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900">{{ $siteName }} on WhatsApp</h1>
        <p class="text-gray-600 mt-3">Our official WhatsApp channel shares new profiles, tips and news. Following it is free, and your phone number stays private: we cannot see it, and nor can other followers.</p>

        <x-whatsapp-channel class="mt-8" />

        @if($waChat)
            <h2 class="text-xl font-semibold text-gray-900 mt-12">Chat with us on WhatsApp</h2>
            <p class="text-gray-600 mt-2">For help with registration, your profile or photos, message our team at
                <a href="{{ $waChat }}" target="_blank" rel="noopener" class="font-semibold text-(--color-primary) hover:underline">{{ $waShown }}</a>.
                @if(\App\Models\SiteSetting::getValue('photo_via_whatsapp', '0') === '1')You can also send your profile photo there, with your member ID.@endif</p>
        @endif

        <h2 class="text-xl font-semibold text-gray-900 mt-12">Does {{ $siteName }} have a WhatsApp group?</h2>
        <p class="text-gray-600 mt-2">Our official WhatsApp channel is where we share new profiles and news. Groups and numbers that use our name but are not listed on this page are not run by us.</p>

        <h2 class="text-xl font-semibold text-gray-900 mt-12">Stay safe on WhatsApp</h2>
        <ul class="list-disc pl-6 mt-2 space-y-2 text-gray-600">
            <li>We never ask for money, bank details, OTPs or passwords on WhatsApp.</li>
            <li>Check a profile on the website before sharing your phone number or photos with anyone.</li>
            <li>Something feels wrong? <a href="{{ url('/contact-us') }}" class="text-(--color-primary) hover:underline">Report it to us</a>.</li>
        </ul>

        <p class="mt-12"><a href="{{ route('register') }}" class="inline-block px-6 py-3 rounded-lg bg-(--color-primary) text-white font-semibold">Register free</a></p>
    </div>
</x-layouts.app>
