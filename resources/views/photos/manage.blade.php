<x-layouts.app title="Manage Photos">
    {{-- Cropper.js CDN (no npm build required) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

    {{-- Cropper.js visual polish: replace transparent checkerboard with clean gray,
         use brand colors for crop box handles + guides --}}
    <style>
        .cropper-bg { background-image: none !important; background-color: #f3f4f6 !important; }
        .cropper-modal { background-color: rgba(17, 24, 39, 0.5) !important; }
        .cropper-view-box { outline: 2px solid rgba(255, 255, 255, 0.9); outline-color: rgba(255, 255, 255, 0.9); }
        .cropper-line, .cropper-point { background-color: var(--color-primary, #8B1D91) !important; }
        .cropper-dashed { border-color: rgba(255, 255, 255, 0.6); }
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="photoManagerEditor({{ $archivedPhotos->count() > 0 ? 'true' : 'false' }}, '{{ request('tab', 'album') }}')">

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

        @if (session('success'))
            <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg">
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-600 font-medium">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">

            {{-- ══ LEFT COLUMN: Profile Photo ══ --}}
            <div class="w-full lg:w-80 shrink-0">
                <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
                    <div class="p-5">
                        <h2 class="text-base font-semibold text-gray-900 mb-4">Profile Photo</h2>

                        {{-- Photo Display --}}
                        <div class="relative aspect-[3/4] bg-gray-100 rounded-lg overflow-hidden mb-4">
                            @if($profilePhoto)
                                <img src="{{ $profilePhoto->full_url }}" alt="Profile Photo"
                                    class="w-full h-full object-cover">
                                @if($profilePhoto->is_primary)
                                    <div class="absolute bottom-3 left-3">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-500 text-white">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                            Primary
                                        </span>
                                    </div>
                                @endif
                                {{-- Archive button --}}
                                <div class="absolute top-3 right-3">
                                    <form method="POST" action="{{ route('photos.destroy', $profilePhoto) }}" onsubmit="return confirm('Archive this photo?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-white/80 rounded-full hover:bg-white shadow-sm" title="Archive">
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                    <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                    </svg>
                                    <p class="text-sm">No profile photo</p>
                                </div>
                            @endif
                        </div>

                        {{-- Upload Profile Photo Button --}}
                        @if(!$profilePhoto)
                            <button @click="openUpload('profile')"
                                class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors mb-4">
                                Upload Profile Photo
                            </button>
                        @else
                            <button @click="openUpload('profile')"
                                class="w-full border border-gray-300 text-gray-700 hover:border-gray-400 rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors mb-4">
                                Change Profile Photo
                            </button>
                        @endif

                        {{-- Privacy Settings — per-type (profile/album/family) --}}
                        <div class="border-t border-gray-100 pt-4">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Photo Privacy</h3>
                            <form method="POST" action="{{ route('photos.privacy') }}" x-data="{ saving: false }" @submit="saving = true">
                                @csrf
                                @php
                                    $levels = \App\Models\PhotoPrivacySetting::LEVELS;
                                    $typeLevels = [
                                        'profile' => $privacy?->profile_photo_privacy ?? 'visible_to_all',
                                        'album' => $privacy?->album_photos_privacy ?? 'visible_to_all',
                                        'family' => $privacy?->family_photos_privacy ?? 'interest_accepted',
                                    ];
                                @endphp

                                <div class="space-y-3" :class="saving && 'opacity-50 pointer-events-none'">
                                    @foreach([
                                        'profile' => ['label' => 'Profile Photo', 'icon' => '👤'],
                                        'album' => ['label' => 'Album Photos', 'icon' => '🖼️'],
                                        'family' => ['label' => 'Family Photos', 'icon' => '👨‍👩‍👧'],
                                    ] as $type => $meta)
                                        <div>
                                            <label class="flex items-center justify-between text-xs font-medium text-gray-700 mb-1">
                                                <span><span class="mr-1">{{ $meta['icon'] }}</span>{{ $meta['label'] }}</span>
                                            </label>
                                            <select name="{{ $type }}_photo_privacy"
                                                class="w-full text-sm border-gray-300 rounded-md focus:ring-(--color-primary) focus:border-(--color-primary)"
                                                @change="saving = true; $el.form.submit()">
                                                @foreach($levels as $val => $label)
                                                    <option value="{{ $val }}" {{ $typeLevels[$type] === $val ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                </div>

                                <p class="text-xs text-gray-400 mt-3">Changes save automatically when you pick a different option.</p>
                            </form>
                        </div>

                        {{-- Photo Guidelines --}}
                        <div class="border-t border-gray-100 pt-4 mt-4">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Photo Policy</h3>
                            <ul class="text-xs text-gray-500 space-y-1">
                                <li>Upload clear, recent photos</li>
                                <li>Max 5 MB per photo</li>
                                <li>JPG, PNG, GIF, WebP accepted</li>
                                <li>No group photos as profile photo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ RIGHT COLUMN: Album & Family Photos ══ --}}
            <div class="flex-1 min-w-0">
                {{-- Tabs --}}
                <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
                    <div class="border-b border-gray-200">
                        <div class="flex overflow-x-auto">
                            <button @click="activeTab = 'album'"
                                :class="activeTab === 'album' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="px-6 py-3 text-sm font-semibold border-b-2 transition-colors">
                                Album Photo (<span>{{ $albumPhotos->count() }}/9</span>)
                            </button>
                            <button @click="activeTab = 'family'"
                                :class="activeTab === 'family' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="px-6 py-3 text-sm font-semibold border-b-2 transition-colors">
                                Family / Group Photos (<span>{{ $familyPhotos->count() }}/3</span>)
                            </button>
                        </div>
                    </div>

                    <div class="p-5">
                        {{-- Format info --}}
                        <p class="text-xs text-gray-400 mb-4">PNG/GIF/JPG/JPEG/WebP format with maximum 5 MB size allowed.</p>

                        {{-- Album Photos Grid --}}
                        <div x-show="activeTab === 'album'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            @foreach($albumPhotos as $photo)
                                <div class="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden">
                                    <img src="{{ $photo->full_url }}" alt="Album photo" class="w-full h-full object-cover">
                                    {{-- Hover actions --}}
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center pb-2 gap-2">
                                        <form method="POST" action="{{ route('photos.primary', $photo) }}">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-white rounded-full shadow-sm hover:bg-green-50" title="Set as Profile Photo">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('photos.destroy', $photo) }}" onsubmit="return confirm('Archive this photo?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 bg-white rounded-full shadow-sm hover:bg-red-50" title="Archive">
                                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Add Photo Card --}}
                            @if($albumPhotos->count() < 9)
                                <button @click="openUpload('album')"
                                    class="aspect-square border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center text-gray-400 hover:border-(--color-primary) hover:text-(--color-primary) transition-colors cursor-pointer">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span class="text-xs font-medium">Add Photo</span>
                                </button>
                            @endif
                        </div>

                        {{-- Family Photos Grid --}}
                        <div x-show="activeTab === 'family'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            @foreach($familyPhotos as $photo)
                                <div class="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden">
                                    <img src="{{ $photo->full_url }}" alt="Family photo" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center pb-2 gap-2">
                                        <form method="POST" action="{{ route('photos.primary', $photo) }}">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-white rounded-full shadow-sm hover:bg-green-50" title="Set as Profile Photo">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('photos.destroy', $photo) }}" onsubmit="return confirm('Archive this photo?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 bg-white rounded-full shadow-sm hover:bg-red-50" title="Archive">
                                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            @if($familyPhotos->count() < 3)
                                <button @click="openUpload('family')"
                                    class="aspect-square border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center text-gray-400 hover:border-(--color-primary) hover:text-(--color-primary) transition-colors cursor-pointer">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span class="text-xs font-medium">Add Photo</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ══ ARCHIVED PHOTOS ══ --}}
                @if($archivedPhotos->count() > 0)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden mt-6">
                        <button @click="showArchived = !showArchived"
                            class="w-full flex items-center justify-between px-5 py-4 text-left">
                            <h2 class="text-base font-semibold text-gray-900">
                                Archived Photos <span class="text-sm font-normal text-gray-500">({{ $archivedPhotos->count() }})</span>
                                <span class="text-xs text-gray-400 ml-2">Including profile and album photos.</span>
                            </h2>
                            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="showArchived && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="showArchived" x-transition class="px-5 pb-5">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                @foreach($archivedPhotos as $photo)
                                    <div class="relative aspect-square bg-gray-100 rounded-lg overflow-hidden opacity-75">
                                        <img src="{{ $photo->full_url }}" alt="Archived photo" class="w-full h-full object-cover">
                                        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 p-2 flex justify-center gap-2">
                                            <form method="POST" action="{{ route('photos.restore', $photo) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 bg-white text-xs font-medium rounded text-green-700 hover:bg-green-50" title="Restore">
                                                    Restore
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('photos.deletePermanent', $photo) }}" onsubmit="return confirm('Permanently delete this photo? This cannot be undone.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-3 py-1 bg-white text-xs font-medium rounded text-red-700 hover:bg-red-50" title="Delete permanently">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ══ UPLOAD MODAL (with Cropper.js editor) ══ --}}
        <div x-show="showUploadModal" x-cloak
            @keydown.escape.window="showUploadModal = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="!submitting && (showUploadModal = false)"
                class="bg-white rounded-xl shadow-2xl w-full max-w-5xl overflow-hidden">

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Upload <span x-text="previewType === 'profile' ? 'Profile' : previewType === 'album' ? 'Album' : 'Family'" class="capitalize"></span> Photo
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5" x-show="sourceImage">
                            <template x-if="previewType === 'profile'"><span>Cropped to 3:4 portrait — ideal for matrimony profiles</span></template>
                            <template x-if="previewType !== 'profile'"><span>Free crop — any aspect ratio</span></template>
                        </p>
                    </div>
                    <button type="button" @click="!submitting && (showUploadModal = false)" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                @csrf
                <input type="hidden" name="tab" :value="activeTab">
                <input type="hidden" name="photo_type" :value="previewType">

                {{-- Empty state: file picker (shown when no image selected) --}}
                <div x-show="!sourceImage" class="p-8">
                    <label class="block w-full cursor-pointer">
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-12 text-center hover:border-(--color-primary) hover:bg-(--color-primary-light) transition-colors">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            <p class="text-base font-medium text-gray-700 mb-1">Click to select a photo</p>
                            <p class="text-xs text-gray-500">JPG, PNG, GIF, WebP. Max 5 MB. You can crop and rotate after selecting.</p>
                        </div>
                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" @change="loadIntoCropper($event)" class="hidden">
                    </label>
                </div>

                {{-- Cropper editor (shown after file is selected) --}}
                {{-- CRITICAL: using x-show (not x-if) so x-ref="cropperImage" is registered on page load --}}
                <div x-show="sourceImage">
                    {{-- Image canvas — clean light gray background, no checkerboard --}}
                    <div class="bg-gray-100 relative" style="height: 500px;">
                        <img x-ref="cropperImage" x-bind:src="sourceImage" alt="To be cropped" class="block max-w-full">
                    </div>

                    {{-- Toolbar: transformations on the left, brightness on the right --}}
                    <div class="px-4 py-3 border-t border-gray-100 bg-white flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-1">
                            {{-- Rotate counter-clockwise (proper rotation icon — NOT undo) --}}
                            <button type="button" @click="rotateLeft()"
                                class="px-3 py-2 flex items-center gap-1.5 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                title="Rotate 90° counter-clockwise">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                    <path d="M3 3v5h5"/>
                                </svg>
                                <span class="text-xs font-medium hidden sm:inline">Rotate L</span>
                            </button>

                            {{-- Rotate clockwise --}}
                            <button type="button" @click="rotateRight()"
                                class="px-3 py-2 flex items-center gap-1.5 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                title="Rotate 90° clockwise">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/>
                                    <path d="M21 3v5h-5"/>
                                </svg>
                                <span class="text-xs font-medium hidden sm:inline">Rotate R</span>
                            </button>

                            {{-- Flip horizontal (mirror) --}}
                            <button type="button" @click="flipHorizontal()"
                                class="px-3 py-2 flex items-center gap-1.5 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                title="Flip horizontally (mirror)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M12 3v18"/>
                                    <path d="M16 7l4 5-4 5"/>
                                    <path d="M8 7l-4 5 4 5"/>
                                </svg>
                                <span class="text-xs font-medium hidden sm:inline">Flip</span>
                            </button>

                            <div class="w-px h-6 bg-gray-200 mx-1"></div>

                            {{-- Reset --}}
                            <button type="button" @click="resetCropper()"
                                class="px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                title="Reset all transformations">
                                Reset
                            </button>
                        </div>

                        {{-- Brightness (compact slider with sun icon) --}}
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
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3 border-t border-gray-100 flex items-center justify-between bg-gray-50">
                    <button type="button" @click="clearSelection()" x-show="sourceImage"
                        class="text-xs font-medium text-gray-500 hover:text-gray-700 underline underline-offset-2">
                        Choose different photo
                    </button>
                    <div x-show="!sourceImage" class="text-xs text-gray-500">
                        Select a photo to continue
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="!submitting && (showUploadModal = false)"
                            class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg">
                            Cancel
                        </button>
                        <button type="button" @click="submitCropped()" :disabled="!sourceImage || submitting"
                            :class="(!sourceImage || submitting) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-(--color-primary-hover) shadow-sm'"
                            class="px-6 py-2.5 text-sm font-semibold text-white bg-(--color-primary) rounded-lg transition-colors flex items-center gap-2">
                            <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                            </svg>
                            <span x-show="!submitting">Upload Photo</span>
                            <span x-show="submitting" x-cloak>Uploading…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alpine photo manager component (handles Cropper.js lifecycle + upload) --}}
    <script>
        function photoManagerEditor(initialShowArchived, initialActiveTab) {
            return {
                activeTab: initialActiveTab,
                showArchived: initialShowArchived,
                showUploadModal: false,
                previewType: null,
                sourceImage: null,
                brightness: 0,
                submitting: false,
                _cropper: null,

                openUpload(type) {
                    this.previewType = type;
                    this.sourceImage = null;
                    this.brightness = 0;
                    this.showUploadModal = true;
                    this.destroyCropper();
                },

                clearSelection() {
                    this.destroyCropper();
                    this.sourceImage = null;
                    this.brightness = 0;
                },

                destroyCropper() {
                    if (this._cropper) {
                        try { this._cropper.destroy(); } catch (e) {}
                        this._cropper = null;
                    }
                },

                loadIntoCropper(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    if (file.size > 5 * 1024 * 1024) {
                        alert('File is too large. Maximum 5 MB.');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.sourceImage = e.target.result;
                        // Wait one tick for x-show to become visible + src to bind, then init
                        this.$nextTick(() => this.initCropper());
                    };
                    reader.readAsDataURL(file);
                },

                initCropper() {
                    // The img uses x-show (not x-if) so $refs.cropperImage is ALWAYS
                    // registered on page load. No polling needed. Fallback querySelector
                    // kept as belt-and-suspenders in case the Alpine ref binding is lagging.
                    const img = this.$refs.cropperImage
                        || (this.$el && this.$el.querySelector('img[x-ref="cropperImage"]'));

                    if (!img || !window.Cropper) {
                        console.error('Cropper init failed', { hasRef: !!this.$refs.cropperImage, hasCropper: !!window.Cropper });
                        alert('Failed to load the photo editor. Please refresh the page and try again.');
                        return;
                    }

                    this.destroyCropper();

                    const aspectRatio = this.previewType === 'profile' ? 3/4 : NaN;

                    this._cropper = new Cropper(img, {
                        aspectRatio: aspectRatio,
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

                rotateLeft() { this._cropper?.rotate(-90); },
                rotateRight() { this._cropper?.rotate(90); },
                flipHorizontal() {
                    if (!this._cropper) return;
                    const current = this._cropper.getData().scaleX || 1;
                    this._cropper.scaleX(current === 1 ? -1 : 1);
                },
                resetCropper() {
                    this.brightness = 0;
                    this._cropper?.reset();
                    this.applyBrightness();
                },
                applyBrightness() {
                    const canvas = this._cropper?.getCropperCanvas?.() || document.querySelector('.cropper-container img');
                    if (canvas) {
                        const amount = 1 + (this.brightness / 100);
                        canvas.style.filter = `brightness(${amount})`;
                    }
                },

                submitCropped() {
                    if (!this._cropper || this.submitting) return;
                    this.submitting = true;

                    // Get cropped canvas with a max size cap (so full-size processing is efficient)
                    const canvas = this._cropper.getCroppedCanvas({
                        maxWidth: 2400,
                        maxHeight: 2400,
                        imageSmoothingQuality: 'high',
                    });

                    // Apply brightness via canvas filter before export
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

                        const form = new FormData();
                        form.append('photo', blob, 'photo.jpg');
                        form.append('photo_type', this.previewType);
                        form.append('tab', this.activeTab);
                        form.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}');

                        fetch('{{ route('photos.upload') }}', {
                            method: 'POST',
                            body: form,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        })
                        .then((res) => {
                            this.submitting = false;
                            if (res.ok || res.redirected) {
                                window.location.href = '{{ route('photos.manage') }}?tab=' + (this.previewType === 'profile' ? 'album' : this.previewType);
                            } else {
                                alert('Upload failed. Please try again.');
                            }
                        })
                        .catch(() => {
                            this.submitting = false;
                            alert('Upload failed. Please check your connection and try again.');
                        });
                    }, 'image/jpeg', 0.92);
                },
            };
        }
    </script>
</x-layouts.app>
