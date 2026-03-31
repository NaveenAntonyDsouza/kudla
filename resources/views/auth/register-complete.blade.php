<x-layouts.auth title="Registration Complete">
    <div class="text-center">
        {{-- Green Checkmark --}}
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h2 class="text-2xl font-serif font-bold text-gray-900 mb-2">Congratulations!</h2>
        <p class="text-gray-600 mb-6">Your registration is complete. Your profile has been created successfully.</p>

        {{-- Profile ID Badge --}}
        <div class="inline-block bg-(--color-primary-light) rounded-lg px-6 py-4 mb-6">
            <p class="text-sm text-gray-600 mb-1">Your Matrimony ID</p>
            <p class="text-2xl font-bold text-(--color-primary) tracking-wider">{{ $profile->matri_id }}</p>
        </div>

        <p class="text-sm text-gray-500 mb-8">Please save your Matrimony ID for future reference. You can use it to log in and share your profile.</p>

        {{-- Go to Dashboard --}}
        <a href="{{ route('home') }}"
            class="inline-block bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-8 py-3 font-semibold text-sm transition-colors">
            Go to Dashboard
        </a>
    </div>
</x-layouts.auth>
