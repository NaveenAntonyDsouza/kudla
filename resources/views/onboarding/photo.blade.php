{{--
    /onboarding/photo — Step 5 of the onboarding funnel.

    Uses the same rich photo-management UI as /manage-photos (profile slot,
    album/family tabs, privacy controls, archive accordion, Cropper.js modal),
    wrapped in the onboarding layout so the 5-step progress bar renders.
    Both pages share the photos._grid-content partial.

    On upload, PhotoController::upload reads `redirect_to` (whitelisted) and
    bounces the user back here so they can keep adding photos without losing
    the onboarding-step context. When they're ready, the Continue button
    POSTs to /onboarding/finish which flips onboarding_completed=true.

    Why a richer UI here than at /register/photo? Onboarding users have
    invested more time and signaled intent to fully fill out their profile;
    /register users are still on the signup conveyor and want a one-shot
    upload to keep momentum.
--}}
<x-layouts.onboarding title="Add your photos" :step="5" :completionPct="$completionPct">

    {{-- Onboarding-specific heading. The grid below renders its own "Profile
         Photo" header inside the left column. --}}
    <div class="text-center mb-6">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-full mb-3">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
            </svg>
            Almost done
        </div>
        <h2 class="text-xl font-semibold text-gray-900">Add your photos</h2>
        <p class="text-sm text-gray-500 mt-1">Optional — but profiles with photos get up to 7× more interest.</p>
        @if($profilePhoto)
            <p class="text-xs text-green-600 mt-2 font-medium">You already have a profile photo on file. You can keep adding album / family photos below.</p>
        @endif
    </div>

    @include('photos._grid-content', [
        'profilePhoto' => $profilePhoto,
        'albumPhotos' => $albumPhotos,
        'familyPhotos' => $familyPhotos,
        'archivedPhotos' => $archivedPhotos,
        'privacy' => $privacy,
        'redirectTo' => route('onboarding.photo'),
    ])

    {{-- Action row: Back ← left, Continue → right.
         Continue posts to onboarding.finish, which flips onboarding_completed
         and lands on the dashboard. Single CTA whose label reflects whether
         a profile photo is on file — no separate Skip button needed. --}}
    <div class="flex items-center justify-between mt-8 gap-3">
        <a href="{{ route('onboarding.lifestyle') }}"
            class="border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-6 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
            Back
        </a>
        <form method="POST" action="{{ route('onboarding.finish') }}">
            @csrf
            <button type="submit"
                class="bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                @if($profilePhoto)
                    Done &amp; Continue &rarr;
                @else
                    Skip &amp; Continue &rarr;
                @endif
            </button>
        </form>
    </div>

</x-layouts.onboarding>
