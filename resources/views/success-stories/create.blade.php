<x-layouts.app title="Share Your Success Story">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('success-stories.index') }}" class="hover:text-(--color-primary)">Success Stories</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Share Your Story</span>
        </p>

        <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
            <h1 class="text-lg font-semibold text-gray-900 mb-1">Share Your Success Story</h1>
            <p class="text-sm text-gray-500 mb-6">Tell us about your journey! Your story will be published after admin review.</p>

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-600">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('success-stories.store') }}" enctype="multipart/form-data" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf

                {{-- Couple Names --}}
                <div class="mb-4">
                    <label for="couple_names" class="block text-sm font-medium text-gray-700 mb-1">Couple Names *</label>
                    <input type="text" name="couple_names" id="couple_names" value="{{ old('couple_names') }}" required maxlength="150"
                        placeholder="e.g. John & Mary"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    {{-- Location --}}
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input type="text" name="location" id="location" value="{{ old('location') }}" maxlength="100"
                            placeholder="e.g. Mangalore, Karnataka"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
                    </div>

                    {{-- Wedding Date --}}
                    <div>
                        <label for="wedding_date" class="block text-sm font-medium text-gray-700 mb-1">Wedding Date</label>
                        <input type="date" name="wedding_date" id="wedding_date" value="{{ old('wedding_date') }}"
                            max="{{ now()->format('Y-m-d') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
                    </div>
                </div>

                {{-- Story --}}
                <div class="mb-4">
                    <label for="story" class="block text-sm font-medium text-gray-700 mb-1">Your Story *</label>
                    <textarea name="story" id="story" rows="6" required minlength="20" maxlength="2000"
                        placeholder="Share how you met, your journey, and your experience with our platform..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('story') }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">Min 20, max 2000 characters</p>
                </div>

                {{-- Photo Upload --}}
                <div class="mb-6">
                    <label for="photo" class="block text-sm font-medium text-gray-700 mb-1">Couple Photo (optional)</label>
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-(--color-primary-light) file:text-(--color-primary) hover:file:bg-(--color-primary)/10">
                    <p class="mt-1 text-xs text-gray-400">JPG, PNG or WebP. Max 3MB.</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
                        class="px-6 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                        <span x-show="!submitting">Submit Story</span>
                        <span x-show="submitting" x-cloak>Submitting...</span>
                    </button>
                    <a href="{{ route('success-stories.index') }}" class="px-6 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
