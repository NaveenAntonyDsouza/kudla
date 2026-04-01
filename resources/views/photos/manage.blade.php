<x-layouts.app title="Manage Photos">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        activeTab: '{{ request('tab', 'album') }}',
        showArchived: {{ $archivedPhotos->count() > 0 ? 'true' : 'false' }},
        previewUrl: null,
        previewType: null,
        showUploadModal: false,

        openUpload(type) {
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = null;
            this.previewType = type;
            this.showUploadModal = true;
            this.$nextTick(() => {
                const input = document.querySelector('input[name=photo]');
                if (input) input.value = '';
            });
        },
        previewFile(event) {
            const file = event.target.files[0];
            if (file) {
                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = URL.createObjectURL(file);
            }
        }
    }">

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
            <div class="lg:w-80 shrink-0">
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

                        {{-- Privacy Settings --}}
                        <div class="border-t border-gray-100 pt-4">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Photo Privacy</h3>
                            <form method="POST" action="{{ route('photos.privacy') }}" x-data="{ saving: false }">
                                @csrf
                                <div class="space-y-2">
                                    @foreach(['visible_to_all' => 'Visible To All', 'interest_accepted' => 'Visible To Interest Sent or Accepted', 'hidden' => 'Hide Photos'] as $val => $label)
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer" :class="saving && 'opacity-50 pointer-events-none'">
                                            <input type="radio" name="privacy_level" value="{{ $val }}"
                                                {{ ($privacy?->privacy_level ?? 'visible_to_all') === $val ? 'checked' : '' }}
                                                class="text-(--color-primary) focus:ring-(--color-primary)"
                                                @change="saving = true; $el.form.submit()">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
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
                        <div class="flex">
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

        {{-- ══ UPLOAD MODAL ══ --}}
        <div x-show="showUploadModal" x-cloak
            @keydown.escape.window="showUploadModal = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="showUploadModal = false"
                class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Upload <span x-text="previewType === 'profile' ? 'Profile' : previewType === 'album' ? 'Album' : 'Family'" class="capitalize"></span> Photo
                    </h3>
                </div>

                <form method="POST" action="{{ route('photos.upload') }}" enctype="multipart/form-data" x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="tab" :value="activeTab">
                    <input type="hidden" name="photo_type" :value="previewType">

                    <div class="p-6">
                        {{-- Preview area --}}
                        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-4 flex items-center justify-center">
                            <template x-if="previewUrl">
                                <img :src="previewUrl" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!previewUrl">
                                <div class="text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25a2.25 2.25 0 00-2.25-2.25H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
                                    </svg>
                                    <p class="text-sm">Select a photo to preview</p>
                                </div>
                            </template>
                        </div>

                        {{-- File input --}}
                        <label class="block w-full cursor-pointer">
                            <span class="sr-only">Choose photo</span>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                                @change="previewFile($event)" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-(--color-primary-light) file:text-(--color-primary) hover:file:bg-(--color-primary) hover:file:text-white file:cursor-pointer file:transition-colors">
                        </label>
                        <p class="mt-2 text-xs text-gray-400">JPG, PNG, GIF, WebP. Max 5 MB.</p>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                        <button type="button" @click="showUploadModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="submitting"
                            :class="submitting && 'opacity-50 cursor-not-allowed'"
                            class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                            <span x-show="!submitting">Upload</span>
                            <span x-show="submitting" x-cloak>Uploading...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
