<x-layouts.auth title="Step 1 - Registration" maxWidth="2xl">
    {{-- Progress Bar --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700">Step 1 of 5</span>
            <span class="text-sm text-gray-500">Basic Info</span>
        </div>
        <div class="flex gap-1">
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
        </div>
    </div>

    <h2 class="text-xl font-serif font-bold text-gray-900 mb-6">Create Your Account</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store1') }}" x-data="{
        religion: '{{ old('religion', '') }}',
        maritalStatus: '{{ old('marital_status', '') }}',
        physicalStatus: '{{ old('physical_status', '') }}',
        communities: [],
        subCommunities: [],
        selectedCaste: '{{ old('caste', '') }}',
        selectedSubCaste: '{{ old('sub_caste', '') }}',

        async fetchCommunities() {
            if (!this.religion || this.religion === 'Other' || this.religion === 'No Religion') {
                this.communities = [];
                this.subCommunities = [];
                this.selectedCaste = '';
                this.selectedSubCaste = '';
                return;
            }
            const response = await fetch(`/api/cascade/communities?religion=${encodeURIComponent(this.religion)}`);
            this.communities = await response.json();
            this.subCommunities = [];
            this.selectedSubCaste = '';
            if (this.selectedCaste) {
                this.loadSubCommunities();
            }
        },

        loadSubCommunities() {
            const community = this.communities.find(c => c.community_name === this.selectedCaste);
            this.subCommunities = community ? (community.sub_communities || []) : [];
            this.selectedSubCaste = '';
        },

        init() {
            if (this.religion) {
                this.fetchCommunities();
            }
        }
    }">
        @csrf

        {{-- ── Personal Details ─────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Personal Details</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Full Name --}}
                <div class="sm:col-span-2">
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Enter your full name">
                    @error('full_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Gender --}}
                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                    <select name="gender" id="gender" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                    @error('gender') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Date of Birth --}}
                <div>
                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                    <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}" required
                        max="{{ now()->subYears(18)->format('Y-m-d') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('date_of_birth') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required
                        pattern="[0-9]{10}" maxlength="10"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="10-digit mobile number">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="your@email.com">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" id="password" required minlength="6" maxlength="14"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="6-14 characters">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Re-enter password">
                </div>
            </div>
        </fieldset>

        {{-- ── Physical Details ─────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Physical Details</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Height --}}
                <div>
                    <label for="height_cm" class="block text-sm font-medium text-gray-700 mb-1">Height (cm)</label>
                    <input type="number" name="height_cm" id="height_cm" value="{{ old('height_cm') }}" min="122" max="213"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="e.g. 170">
                    @error('height_cm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Complexion --}}
                <div>
                    <label for="complexion" class="block text-sm font-medium text-gray-700 mb-1">Complexion</label>
                    <select name="complexion" id="complexion"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Very Fair', 'Fair', 'Wheatish', 'Wheatish Brown', 'Dark'] as $opt)
                            <option value="{{ $opt }}" {{ old('complexion') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('complexion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Body Type --}}
                <div>
                    <label for="body_type" class="block text-sm font-medium text-gray-700 mb-1">Body Type</label>
                    <select name="body_type" id="body_type"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Slim', 'Average', 'Athletic', 'Heavy'] as $opt)
                            <option value="{{ $opt }}" {{ old('body_type') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('body_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Physical Status --}}
                <div>
                    <label for="physical_status" class="block text-sm font-medium text-gray-700 mb-1">Physical Status</label>
                    <select name="physical_status" id="physical_status" x-model="physicalStatus"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Normal', 'Differently Abled'] as $opt)
                            <option value="{{ $opt }}" {{ old('physical_status') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('physical_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Marital Status --}}
                <div>
                    <label for="marital_status" class="block text-sm font-medium text-gray-700 mb-1">Marital Status <span class="text-red-500">*</span></label>
                    <select name="marital_status" id="marital_status" x-model="maritalStatus" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Never Married', 'Divorced', 'Widowed', 'Awaiting Divorce', 'Annulled'] as $opt)
                            <option value="{{ $opt }}" {{ old('marital_status') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('marital_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Children fields (shown when not "Never Married") --}}
                <template x-if="maritalStatus && maritalStatus !== 'Never Married'">
                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="children_with_me" class="block text-sm font-medium text-gray-700 mb-1">Children Living With Me</label>
                            <input type="number" name="children_with_me" id="children_with_me" value="{{ old('children_with_me', 0) }}" min="0"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                            @error('children_with_me') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="children_not_with_me" class="block text-sm font-medium text-gray-700 mb-1">Children Not Living With Me</label>
                            <input type="number" name="children_not_with_me" id="children_not_with_me" value="{{ old('children_not_with_me', 0) }}" min="0"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                            @error('children_not_with_me') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </template>
            </div>
        </fieldset>

        {{-- ── Religion & Community ─────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Religion & Community</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Religion --}}
                <div class="sm:col-span-2">
                    <label for="religion" class="block text-sm font-medium text-gray-700 mb-1">Religion <span class="text-red-500">*</span></label>
                    <select name="religion" id="religion" x-model="religion" @change="fetchCommunities()" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select Religion</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Christian">Christian</option>
                        <option value="Muslim">Muslim</option>
                        <option value="Jain">Jain</option>
                        <option value="No Religion">No Religion</option>
                        <option value="Other">Other</option>
                    </select>
                    @error('religion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- ── Christian Fields ──────────────── --}}
                <div x-show="religion === 'Christian'" x-transition class="sm:col-span-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
                        {{-- Caste / Community (cascading from API) --}}
                        <div>
                            <label for="caste_christian" class="block text-sm font-medium text-gray-700 mb-1">Caste / Community</label>
                            <select name="caste" x-model="selectedCaste" @change="loadSubCommunities()"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select Community</option>
                                <template x-for="community in communities" :key="community.id">
                                    <option :value="community.community_name" x-text="community.community_name"
                                        :selected="community.community_name === selectedCaste"></option>
                                </template>
                            </select>
                            @error('caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Sub-Caste / Sub-Community --}}
                        <div x-show="subCommunities.length > 0" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sub-Caste / Sub-Community</label>
                            <select name="sub_caste" x-model="selectedSubCaste"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select Sub-Community</option>
                                <template x-for="sub in subCommunities" :key="sub">
                                    <option :value="sub" x-text="sub" :selected="sub === selectedSubCaste"></option>
                                </template>
                            </select>
                            @error('sub_caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Denomination (grouped) --}}
                        <div>
                            <label for="denomination" class="block text-sm font-medium text-gray-700 mb-1">Denomination <span class="text-red-500">*</span></label>
                            <select name="denomination" id="denomination"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select</option>
                                @foreach($denominations as $group => $items)
                                    <optgroup label="{{ $group }}">
                                        @foreach($items as $denom)
                                            <option value="{{ $denom }}" {{ old('denomination') === $denom ? 'selected' : '' }}>{{ $denom }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('denomination') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Diocese (dropdown) --}}
                        <div>
                            <label for="diocese" class="block text-sm font-medium text-gray-700 mb-1">Diocese</label>
                            <select name="diocese" id="diocese"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select Diocese</option>
                                @foreach($dioceses as $dio)
                                    <option value="{{ $dio }}" {{ old('diocese') === $dio ? 'selected' : '' }}>{{ $dio }}</option>
                                @endforeach
                            </select>
                            @error('diocese') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Diocese Name --}}
                        <div>
                            <label for="diocese_name" class="block text-sm font-medium text-gray-700 mb-1">Diocese Name</label>
                            <input type="text" name="diocese_name" id="diocese_name" value="{{ old('diocese_name') }}"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                            @error('diocese_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Parish Name & Place --}}
                        <div>
                            <label for="parish_name_place" class="block text-sm font-medium text-gray-700 mb-1">Parish Name & Place</label>
                            <input type="text" name="parish_name_place" id="parish_name_place" value="{{ old('parish_name_place') }}"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                                placeholder="e.g. St. Sebastian's, Bendur">
                            @error('parish_name_place') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- ── Hindu / Jain Fields ──────────── --}}
                <div x-show="religion === 'Hindu' || religion === 'Jain'" x-transition class="sm:col-span-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
                        {{-- Caste / Community (cascading from API) --}}
                        <div>
                            <label for="caste" class="block text-sm font-medium text-gray-700 mb-1">Caste / Community</label>
                            <select name="caste" id="caste" x-model="selectedCaste" @change="loadSubCommunities()"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select Community</option>
                                <template x-for="community in communities" :key="community.id">
                                    <option :value="community.community_name" x-text="community.community_name"
                                        :selected="community.community_name === selectedCaste"></option>
                                </template>
                            </select>
                            @error('caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Sub-Caste / Sub-Community --}}
                        <div x-show="subCommunities.length > 0" x-transition>
                            <label for="sub_caste" class="block text-sm font-medium text-gray-700 mb-1">Sub-Caste / Sub-Community</label>
                            <select name="sub_caste" id="sub_caste" x-model="selectedSubCaste"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select Sub-Community</option>
                                <template x-for="sub in subCommunities" :key="sub">
                                    <option :value="sub" x-text="sub" :selected="sub === selectedSubCaste"></option>
                                </template>
                            </select>
                            @error('sub_caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="gotra" class="block text-sm font-medium text-gray-700 mb-1">Gothram</label>
                            <input type="text" name="gotra" id="gotra" value="{{ old('gotra') }}"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                            @error('gotra') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="nakshatra" class="block text-sm font-medium text-gray-700 mb-1">Nakshatra (Star)</label>
                            <select name="nakshatra" id="nakshatra"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select</option>
                                @foreach(['Ashwini', 'Bharani', 'Krittika', 'Rohini', 'Mrigashira', 'Ardra', 'Punarvasu', 'Pushya', 'Ashlesha', 'Magha', 'Purva Phalguni', 'Uttara Phalguni', 'Hasta', 'Chitra', 'Swati', 'Vishakha', 'Anuradha', 'Jyeshtha', 'Mula', 'Purva Ashadha', 'Uttara Ashadha', 'Shravana', 'Dhanishta', 'Shatabhisha', 'Purva Bhadrapada', 'Uttara Bhadrapada', 'Revati'] as $star)
                                    <option value="{{ $star }}" {{ old('nakshatra') === $star ? 'selected' : '' }}>{{ $star }}</option>
                                @endforeach
                            </select>
                            @error('nakshatra') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rashi" class="block text-sm font-medium text-gray-700 mb-1">Rashi (Moon Sign)</label>
                            <select name="rashi" id="rashi"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select</option>
                                @foreach(['Mesha (Aries)', 'Vrishabha (Taurus)', 'Mithuna (Gemini)', 'Karka (Cancer)', 'Simha (Leo)', 'Kanya (Virgo)', 'Tula (Libra)', 'Vrishchika (Scorpio)', 'Dhanu (Sagittarius)', 'Makara (Capricorn)', 'Kumbha (Aquarius)', 'Meena (Pisces)'] as $sign)
                                    <option value="{{ $sign }}" {{ old('rashi') === $sign ? 'selected' : '' }}>{{ $sign }}</option>
                                @endforeach
                            </select>
                            @error('rashi') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="dosh" class="block text-sm font-medium text-gray-700 mb-1">Manglik / Dosh</label>
                            <select name="dosh" id="dosh"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select</option>
                                @foreach(['Yes', 'No', 'Not Sure', 'Not Applicable'] as $opt)
                                    <option value="{{ $opt }}" {{ old('dosh') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                            @error('dosh') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="time_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Time of Birth</label>
                            <input type="time" name="time_of_birth" id="time_of_birth" value="{{ old('time_of_birth') }}"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                            @error('time_of_birth') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="place_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Place of Birth</label>
                            <input type="text" name="place_of_birth" id="place_of_birth" value="{{ old('place_of_birth') }}"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                                placeholder="City / Town">
                            @error('place_of_birth') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Jain-specific --}}
                        <div x-show="religion === 'Jain'" x-transition>
                            <label for="jain_sect" class="block text-sm font-medium text-gray-700 mb-1">Jain Sect</label>
                            <select name="jain_sect" id="jain_sect"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select</option>
                                @foreach(['Digambar', 'Shwetambar', 'Other'] as $opt)
                                    <option value="{{ $opt }}" {{ old('jain_sect') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                            @error('jain_sect') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- ── Muslim Fields ─────────────────── --}}
                <div x-show="religion === 'Muslim'" x-transition class="sm:col-span-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
                        {{-- Caste / Community (cascading from API) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Caste / Community</label>
                            <select name="caste" x-model="selectedCaste" @change="loadSubCommunities()"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select Community</option>
                                <template x-for="community in communities" :key="community.id">
                                    <option :value="community.community_name" x-text="community.community_name"
                                        :selected="community.community_name === selectedCaste"></option>
                                </template>
                            </select>
                            @error('caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Sub-Caste / Sub-Community --}}
                        <div x-show="subCommunities.length > 0" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sub-Caste / Sub-Community</label>
                            <select name="sub_caste" x-model="selectedSubCaste"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select Sub-Community</option>
                                <template x-for="sub in subCommunities" :key="sub">
                                    <option :value="sub" x-text="sub" :selected="sub === selectedSubCaste"></option>
                                </template>
                            </select>
                            @error('sub_caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="muslim_sect" class="block text-sm font-medium text-gray-700 mb-1">Sect</label>
                            <select name="muslim_sect" id="muslim_sect"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select</option>
                                @foreach(['Sunni', 'Shia', 'Ahmadiyya', 'Sufi', 'Other'] as $opt)
                                    <option value="{{ $opt }}" {{ old('muslim_sect') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                            @error('muslim_sect') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="muslim_community" class="block text-sm font-medium text-gray-700 mb-1">Community Detail</label>
                            <input type="text" name="muslim_community" id="muslim_community" value="{{ old('muslim_community') }}"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                                placeholder="e.g. Beary, Nawayath">
                            @error('muslim_community') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="religious_observance" class="block text-sm font-medium text-gray-700 mb-1">Religious Observance</label>
                            <select name="religious_observance" id="religious_observance"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select</option>
                                @foreach(['Very Religious', 'Religious', 'Moderate', 'Liberal'] as $opt)
                                    <option value="{{ $opt }}" {{ old('religious_observance') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                            @error('religious_observance') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- ── Other Religion ────────────────── --}}
                <div x-show="religion === 'Other'" x-transition class="sm:col-span-2">
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <label for="other_religion_name" class="block text-sm font-medium text-gray-700 mb-1">Specify Religion</label>
                        <input type="text" name="other_religion_name" id="other_religion_name" value="{{ old('other_religion_name') }}"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                            placeholder="Enter your religion">
                        @error('other_religion_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </fieldset>

        {{-- ── Submit ───────────────────────────────────────── --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            <button type="submit"
                class="bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-6 py-2.5 font-semibold text-sm transition-colors">
                Continue &rarr;
            </button>
        </div>
    </form>
</x-layouts.auth>
