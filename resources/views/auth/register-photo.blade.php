<x-layouts.registration title="Add your photo" :step="5">

    {{-- Reward framing — registration data is already saved. The photo step
         is a strongly-nudged opt-in, not Step 6 of 6. The progress bar above
         still shows the user at "Final Step" so we don't undo the
         "almost done" feeling step 5 just gave them. --}}

    <div class="text-center mb-6">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-full mb-3">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
            </svg>
            Profile saved
        </div>
        <h2 class="text-xl font-semibold text-gray-900">Add a profile photo</h2>
        <p class="text-sm text-gray-500 mt-1">Optional — but profiles with photos get up to 7× more interest.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below:</p>
            <ul class="mt-1 text-xs text-red-500 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.photo.store') }}" enctype="multipart/form-data"
        x-data="{
            preview: null,
            fileName: '',
            onFile(event) {
                const file = event.target.files?.[0];
                if (!file) { this.preview = null; this.fileName = ''; return; }
                this.fileName = file.name;
                const reader = new FileReader();
                reader.onload = e => this.preview = e.target.result;
                reader.readAsDataURL(file);
            },
            clear() { this.preview = null; this.fileName = ''; this.$refs.fileInput.value = ''; }
        }">
        @csrf

        {{-- Drop zone / preview --}}
        <div class="rounded-lg border-2 border-dashed border-gray-300 hover:border-(--color-primary) transition-colors p-6 text-center"
             :class="{ 'border-(--color-primary)': preview }">
            <template x-if="!preview">
                <label for="photo" class="block cursor-pointer">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-700">Tap to choose a photo</p>
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP — up to {{ (int) config('matrimony.max_photo_size_mb', 5) }} MB</p>
                </label>
            </template>

            <template x-if="preview">
                <div class="flex flex-col items-center">
                    <img :src="preview" alt="Photo preview" class="max-h-64 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-500 mt-3" x-text="fileName"></p>
                    <button type="button" @click="clear()"
                        class="mt-2 text-xs text-(--color-primary) hover:underline">
                        Choose a different photo
                    </button>
                </div>
            </template>

            <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp,image/gif"
                x-ref="fileInput" @change="onFile($event)" class="hidden" required>
        </div>

        {{-- Privacy note: makes the user feel safe uploading. The visibility
             toggles themselves live at /manage-photos for ongoing control. --}}
        <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex gap-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <p class="text-xs text-amber-800">
                Your photo is shown only to signed-in members by default. Fine-tune privacy any time at
                <span class="font-medium">My Photos</span>.
            </p>
        </div>

        {{-- Action row: skip vs save+continue.
             The Skip button overrides the form's action/encoding/validation
             via HTML5 button-level form attributes — this lets one <form>
             cleanly handle both paths without nesting (which is invalid
             HTML). On Skip we don't need multipart or the required-photo
             validation. --}}
        <div class="flex items-center justify-between mt-6 gap-3">
            <button type="submit"
                formaction="{{ route('register.photo.skip') }}"
                formmethod="POST"
                formenctype="application/x-www-form-urlencoded"
                formnovalidate
                class="border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-6 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Skip for now
            </button>
            <button type="submit"
                class="bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Save & Continue
            </button>
        </div>
    </form>
</x-layouts.registration>
