<x-layouts.app title="Upload ID Proof">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Submit ID Proof</span>
        </p>

        @if(session('success'))
            <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg">
                @foreach($errors->all() as $error)
                    <p class="text-sm text-red-600 font-medium">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            {{-- Left sidebar --}}
            <div class="lg:w-72 shrink-0">
                <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-(--color-primary-light) rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 text-center mb-3">Verify Your Profile</h3>
                    <p class="text-xs text-gray-500 text-center mb-4">{{ config('app.name') }} requires proof of the candidate's identity. Your ID proof is in safe hands and we won't divulge it to any third party.</p>

                    <div class="border-t border-gray-100 pt-4 mt-4 text-xs text-gray-500 space-y-2">
                        <p>You can also send your ID proof via:</p>
                        <p class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75"/></svg>
                            {{ \App\Models\SiteSetting::getValue('email', 'info@example.com') }}
                        </p>
                    </div>
                </div>

                {{-- Existing uploads --}}
                @if($idProofs->count() > 0)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-5 mt-4">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Uploaded Documents</h4>
                        <div class="space-y-3">
                            @foreach($idProofs as $proof)
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $proof->document_type }}</p>
                                        <p class="text-xs {{ $proof->verification_status === 'verified' ? 'text-green-600' : ($proof->verification_status === 'rejected' ? 'text-red-500' : 'text-amber-600') }}">
                                            {{ ucfirst($proof->verification_status) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ Storage::disk('public')->url($proof->document_url) }}" target="_blank" class="text-xs text-(--color-primary) hover:underline">View</a>
                                        <form method="POST" action="{{ route('idproof.destroy', $proof) }}" onsubmit="return confirm('Remove this document?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:underline">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right: Upload form --}}
            <div class="flex-1 min-w-0">
                <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">ID Proof</h2>
                    <p class="text-sm text-gray-500 mb-6">Upload your government approved identification proof i.e. Passport / Voter ID / Aadhaar Card (Both Sides), or Driving License (Front Side) along with your name, date of birth, and address.</p>

                    <form method="POST" action="{{ route('idproof.store') }}" enctype="multipart/form-data" x-data="{ submitting: false }" @submit="submitting = true">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                            <div class="float-field">
                                <select name="document_type" required>
                                    <option value="">Select</option>
                                    @foreach(['Passport', 'Voter ID', 'Aadhaar Card', 'Driving License', 'PAN Card'] as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                <label>ID Proof Type <span class="text-red-500">*</span></label>
                            </div>
                            <div class="float-field">
                                <input type="text" name="document_number" required maxlength="50" placeholder=" ">
                                <label>ID Proof Number <span class="text-red-500">*</span></label>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Front Side <span class="text-red-500">*</span></label>
                            <input type="file" name="document_front" required accept=".jpg,.jpeg,.png,.pdf,.webp"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-(--color-primary-light) file:text-(--color-primary) hover:file:bg-(--color-primary) hover:file:text-white file:cursor-pointer file:transition-colors">
                        </div>
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Back Side <span class="text-xs text-gray-400">(Optional — required for Aadhaar)</span></label>
                            <input type="file" name="document_back" accept=".jpg,.jpeg,.png,.pdf,.webp"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-(--color-primary-light) file:text-(--color-primary) hover:file:bg-(--color-primary) hover:file:text-white file:cursor-pointer file:transition-colors">
                            <p class="mt-1 text-xs text-gray-400">PNG/JPG/JPEG/WebP/PDF format, max 5MB each.</p>
                        </div>

                        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
                            class="px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                            <span x-show="!submitting">Upload ID Proof</span>
                            <span x-show="submitting" x-cloak>Uploading...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
