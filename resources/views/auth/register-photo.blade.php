<x-layouts.registration title="Add your photo" :step="5">

    {{-- Cropper.js library + brand-color overrides. Shared with /manage-photos.
         The Alpine component below (registerPhotoEditor) is bespoke to this
         lightweight one-shot upload context — see also photoManagerEditor in
         /manage-photos for the modal-based multi-photo version. --}}
    <x-photo-cropper-assets />

    {{-- Reward framing — registration data is already saved. The photo step
         is a strongly-nudged opt-in, not Step 6 of 6. Progress bar above
         still shows "Final Step" so we don't undo step 5's "almost done"
         feeling. --}}
    <div class="text-center mb-6">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-full mb-3">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
            </svg>
            Profile saved
        </div>
        <h2 class="text-xl font-semibold text-gray-900">Add a profile photo</h2>
        <p class="text-sm text-gray-500 mt-1">Optional — but members with a photo get about 60% more profile views and interests.</p>

        {{-- How the profile looks to others, without and with a photo (Oct 2026) --}}
        <div class="mt-4 flex justify-center gap-4" aria-hidden="true">
            @foreach ([false, true] as $withPhoto)
                <div class="rounded-lg border {{ $withPhoto ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }} p-2 text-center" style="width: 8rem;">
                    <div class="mx-auto h-20 w-16 rounded-md flex items-end justify-center overflow-hidden" style="background: {{ $withPhoto ? 'var(--color-primary-light)' : '#e5e7eb' }};">
                        <svg viewBox="0 0 40 50" class="h-16 w-14" fill="{{ $withPhoto ? 'var(--color-primary)' : '#b8b8b8' }}">
                            <circle cx="20" cy="17" r="10"/><path d="M2 50c0-12 8-19 18-19s18 7 18 19z"/>
                        </svg>
                    </div>
                    <p class="mt-1 font-semibold {{ $withPhoto ? 'text-green-700' : 'text-gray-500' }}" style="font-size: 11px;">{{ $withPhoto ? 'With a photo' : 'Without a photo' }}</p>
                    <p class="text-gray-500" style="font-size: 10px;">{{ $withPhoto ? 'Gets noticed' : 'Often scrolled past' }}</p>
                </div>
            @endforeach
        </div>
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

    <form id="registerPhotoForm" method="POST" action="{{ route('register.photo.store') }}" enctype="multipart/form-data"
        x-data="registerPhotoEditor()" x-init="$nextTick(() => init())">
        @csrf

        {{-- Source file input. Hidden — clicked via the picker label. --}}
        <input x-ref="fileInput" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"
               @change="loadIntoCropper($event)" class="hidden" required>

        {{-- ── Picker (shown when no image yet) ── --}}
        <div x-show="!sourceImage" class="rounded-lg border-2 border-dashed border-gray-300 hover:border-(--color-primary) transition-colors p-6 text-center">
            <label for="" @click="$refs.fileInput.click()" class="block cursor-pointer">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-700">Tap to choose a photo</p>
                <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP — up to {{ (int) config('matrimony.max_photo_size_mb', 5) }} MB. You can crop and rotate after selecting.</p>
            </label>
        </div>

        {{-- ── Cropper editor (shown after file is selected) ── --}}
        {{-- CRITICAL: x-show (not x-if) so the cropperImage ref is registered on page load. --}}
        <div x-show="sourceImage" class="rounded-lg border border-gray-200 overflow-hidden bg-white">
            <div class="bg-gray-100 relative" style="height: 420px;">
                <img x-ref="cropperImage" x-bind:src="sourceImage" alt="To be cropped" class="block max-w-full">
            </div>

            {{-- Toolbar: rotations + brightness --}}
            <div class="px-3 py-2 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1">
                    <button type="button" @click="rotateLeft()"
                        class="px-2.5 py-1.5 flex items-center gap-1.5 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Rotate 90° counter-clockwise">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                        </svg>
                        <span class="text-xs font-medium hidden sm:inline">Rotate L</span>
                    </button>
                    <button type="button" @click="rotateRight()"
                        class="px-2.5 py-1.5 flex items-center gap-1.5 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Rotate 90° clockwise">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/>
                            <path d="M21 3v5h-5"/>
                        </svg>
                        <span class="text-xs font-medium hidden sm:inline">Rotate R</span>
                    </button>
                    <button type="button" @click="resetCropper()"
                        class="px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Reset all transformations">
                        Reset
                    </button>
                </div>

                {{-- Brightness slider --}}
                <div class="flex items-center gap-2 px-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                    </svg>
                    <input type="range" min="-50" max="50" step="5" x-model="brightness" @input="applyBrightness()"
                        class="w-24 md:w-32 accent-(--color-primary)">
                    <span class="text-xs text-gray-500 w-8 text-center font-mono tabular-nums" x-text="brightness"></span>
                </div>
            </div>

            <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <button type="button" @click="clearSelection()"
                    class="text-xs font-medium text-gray-500 hover:text-gray-700 underline underline-offset-2">
                    Choose a different photo
                </button>
                <p class="text-xs text-gray-500">Drag to reframe · 3:4 portrait crop</p>
            </div>
        </div>

        @if ($profile->gender === 'female')
            {{-- Who sees it, chosen right here (Oct 2026): on Kudla only 31 of 144 women showed
                 a photo, and none had found this setting in My Photos. --}}
            <fieldset class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <legend class="text-sm font-medium text-amber-800">Who can see your photo?</legend>
                <label class="mt-2 flex items-start gap-2 text-sm text-gray-800">
                    <input type="radio" name="photo_privacy" value="visible_to_all" checked class="mt-0.5" style="accent-color: var(--color-primary);">
                    <span>All signed-in members</span>
                </label>
                <label class="mt-2 flex items-start gap-2 text-sm text-gray-800">
                    <input type="radio" name="photo_privacy" value="interest_accepted" class="mt-0.5" style="accent-color: var(--color-primary);">
                    <span>Only members whose interest I accept
                        <span class="block text-xs text-gray-500">Others see a locked photo until you accept.</span></span>
                </label>
                <p class="mt-2 text-xs text-amber-800">You can change this any time in <span class="font-medium">My Photos</span>.</p>
            </fieldset>
        @else
            {{-- Privacy reassurance --}}
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
        @endif

        {{-- Photo on WhatsApp instead (site setting photo_via_whatsapp, Kudla, Oct 2026):
             the member sends it to the site's WhatsApp number; staff add it from the admin. --}}
        @php
            $waPhotoLink = \App\Models\SiteSetting::getValue('photo_via_whatsapp', '0') === '1'
                ? \App\Support\WhatsApp::link('Hi, please add the photo I am sending to my profile '.$profile->matri_id.' ('.$profile->full_name.').')
                : null;
        @endphp
        @if ($waPhotoLink)
            <div x-show="!sourceImage" class="mt-4 p-4 rounded-lg border border-green-200 bg-green-50 text-center">
                <p class="text-sm font-medium text-gray-800">Photo on WhatsApp? Send it to us instead.</p>
                <p class="text-xs text-gray-600 mt-1">Our team adds it to your profile ({{ $profile->matri_id }}), usually within a day.</p>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    <a href="{{ $waPhotoLink }}" target="_blank" rel="noopener"
                       onclick="if (window.gtag) { gtag('event', 'photo_whatsapp_click', { source: 'registration' }); } if (window.posthog) { posthog.capture('photo_whatsapp_click', { source: 'registration' }); }"
                       class="inline-flex items-center gap-2 rounded-lg text-white px-5 py-3 text-sm font-semibold" style="background: #25D366;">
                        <svg viewBox="0 0 32 32" width="18" height="18" fill="#fff" aria-hidden="true"><path d="M16 3C9 3 3.3 8.7 3.3 15.8c0 2.2.6 4.4 1.7 6.3L3.2 29l7-1.8c1.8 1 3.8 1.4 5.8 1.4 7 0 12.8-5.7 12.8-12.8S23.1 3 16 3zm5.9 17.5c-.3.8-1.6 1.5-2.2 1.5-.6.1-1.3.1-2-.1-.5-.2-1.1-.4-1.8-.7-3.2-1.4-5.3-4.6-5.5-4.8-.2-.2-1.3-1.7-1.3-3.3s.8-2.3 1.1-2.7c.3-.3.6-.4.9-.4h.6c.2 0 .5 0 .7.6.3.6.9 2.2 1 2.4.1.2.1.3 0 .6-.1.2-.2.3-.3.5-.2.2-.3.4-.5.6-.2.2-.3.3-.1.6.2.3.8 1.4 1.8 2.2 1.2 1.1 2.3 1.4 2.6 1.6.3.2.5.1.7-.1.2-.2.8-1 1-1.3.2-.3.4-.3.7-.2.3.1 1.9.9 2.2 1.1.3.2.5.2.6.4.1.1.1.8-.2 1.5z"/></svg>
                        Send photo on WhatsApp
                    </a>
                    <button type="submit" formaction="{{ route('register.photo.skip', ['via' => 'whatsapp']) }}" formmethod="POST"
                            formenctype="application/x-www-form-urlencoded" formnovalidate
                            class="text-sm font-medium text-green-800 underline underline-offset-2 px-3 py-2">
                        I've sent it, continue
                    </button>
                </div>
            </div>
        @endif

        {{-- Action row: Back ← left, Skip + Save → right.
             Back goes to the merged Step 4 (which holds all the previously-
             entered data, including Profile Creation Details). Pure
             navigation — doesn't submit the form, doesn't touch the file
             input. --}}
        <div class="flex items-center justify-between mt-6 gap-3">
            <a href="{{ route('register.step4') }}"
                class="border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-6 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Back
            </a>
            <div class="flex items-center gap-3">
                <button type="submit"
                    formaction="{{ route('register.photo.skip') }}"
                    formmethod="POST"
                    formenctype="application/x-www-form-urlencoded"
                    formnovalidate
                    class="border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-6 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                    Skip for now
                </button>
                <button type="button" @click="submitCropped()" :disabled="!sourceImage || submitting"
                    :class="(!sourceImage || submitting) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-(--color-primary-hover)'"
                    class="bg-(--color-primary) text-white rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors flex items-center gap-2">
                    <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                        <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                    </svg>
                    <span x-show="!submitting">Save &amp; Continue</span>
                    <span x-show="submitting" x-cloak>Uploading…</span>
                </button>
            </div>
        </div>
    </form>

    <script>
        function registerPhotoEditor() {
            return {
                sourceImage: null,
                brightness: 0,
                submitting: false,
                _cropper: null,
                _maxBytes: {{ (int) config('matrimony.max_photo_size_mb', 5) * 1024 * 1024 }},

                init() {
                    // Cropper attaches once a file is loaded; nothing to do at boot.
                },

                loadIntoCropper(event) {
                    const file = event.target.files?.[0];
                    if (!file) return;
                    if (file.size > this._maxBytes) {
                        alert('File is too large. Maximum {{ (int) config("matrimony.max_photo_size_mb", 5) }} MB.');
                        this.$refs.fileInput.value = '';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.sourceImage = e.target.result;
                        // Wait one tick for x-show to make the img visible + src to bind.
                        this.$nextTick(() => this.initCropper());
                    };
                    reader.readAsDataURL(file);
                },

                initCropper() {
                    const img = this.$refs.cropperImage
                        || (this.$el && this.$el.querySelector('img[x-ref="cropperImage"]'));
                    if (!img || !window.Cropper) {
                        console.error('Cropper init failed', { hasRef: !!this.$refs.cropperImage, hasCropper: !!window.Cropper });
                        alert('Failed to load the photo editor. Please refresh the page and try again.');
                        return;
                    }
                    this.destroyCropper();
                    this._cropper = new Cropper(img, {
                        aspectRatio: 3/4,             // matches /manage-photos profile crop
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 0.9,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: true,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                },

                destroyCropper() {
                    if (this._cropper) {
                        try { this._cropper.destroy(); } catch (e) {}
                        this._cropper = null;
                    }
                },

                clearSelection() {
                    this.destroyCropper();
                    this.sourceImage = null;
                    this.brightness = 0;
                    this.$refs.fileInput.value = '';
                },

                rotateLeft()  { this._cropper?.rotate(-90); },
                rotateRight() { this._cropper?.rotate(90); },

                resetCropper() {
                    this.brightness = 0;
                    this._cropper?.reset();
                    this.applyBrightness();
                },

                applyBrightness() {
                    // Live preview only — final brightness is baked in at submit time.
                    const wrapper = document.querySelector('.cropper-container .cropper-canvas');
                    const target = wrapper?.querySelector('img') || document.querySelector('.cropper-container img');
                    if (target) {
                        const amount = 1 + (this.brightness / 100);
                        target.style.filter = `brightness(${amount})`;
                    }
                },

                submitCropped() {
                    if (!this._cropper || this.submitting) return;
                    this.submitting = true;

                    const canvas = this._cropper.getCroppedCanvas({
                        maxWidth: 2400,
                        maxHeight: 2400,
                        imageSmoothingQuality: 'high',
                    });

                    // Bake brightness into the exported canvas (cosmetic filter only
                    // affects the live preview; without re-drawing, the upload is plain).
                    if (this.brightness !== 0) {
                        const amount = 1 + (this.brightness / 100);
                        const ctx = canvas.getContext('2d');
                        ctx.filter = `brightness(${amount})`;
                        ctx.drawImage(canvas, 0, 0);
                    }

                    canvas.toBlob((blob) => {
                        if (!blob) {
                            this.submitting = false;
                            alert('Failed to process the photo. Please try a different image.');
                            return;
                        }

                        // Replace the file input's File with the cropped blob, then
                        // submit the form normally. Server stays unchanged — receives
                        // the cropped image as if the user had uploaded it directly.
                        try {
                            const dt = new DataTransfer();
                            dt.items.add(new File([blob], 'photo.jpg', { type: 'image/jpeg' }));
                            this.$refs.fileInput.files = dt.files;
                        } catch (e) {
                            // Older browsers: fall back to sending via fetch + redirect.
                            return this._submitViaFetch(blob);
                        }

                        document.getElementById('registerPhotoForm').submit();
                    }, 'image/jpeg', 0.92);
                },

                _submitViaFetch(blob) {
                    const form = new FormData();
                    form.append('photo', blob, 'photo.jpg');
                    form.append('_token', document.querySelector('input[name=_token]')?.value || '{{ csrf_token() }}');
                    fetch('{{ route('register.photo.store') }}', {
                        method: 'POST',
                        body: form,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    })
                    .then((res) => {
                        if (res.redirected) {
                            window.location.href = res.url;
                        } else if (res.ok) {
                            window.location.href = res.url;
                        } else {
                            this.submitting = false;
                            alert('Upload failed. Please try again.');
                        }
                    })
                    .catch(() => {
                        this.submitting = false;
                        alert('Upload failed. Please check your connection and try again.');
                    });
                },
            };
        }
    </script>
</x-layouts.registration>
