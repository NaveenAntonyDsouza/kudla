{{--
    /manage-photos — full photo-management page for completed-onboarding users.

    The grid, cropper modal, and Alpine controller live in the shared partial
    photos._grid-content (also used by /onboarding/photo). This view's job is
    just the surrounding app layout + page header / breadcrumb.
--}}
<x-layouts.app title="Manage Photos">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Page Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-serif font-bold text-gray-900">Manage Photos</h1>
                <p class="text-sm text-gray-500 mt-1">
                    <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
                    <span class="mx-1">/</span>
                    <span>Manage Photos</span>
                </p>
            </div>
        </div>

        @include('photos._grid-content', [
            'profilePhoto' => $profilePhoto,
            'albumPhotos' => $albumPhotos,
            'familyPhotos' => $familyPhotos,
            'archivedPhotos' => $archivedPhotos,
            'privacy' => $privacy,
            'redirectTo' => route('photos.manage'),
        ])

    </div>
</x-layouts.app>
