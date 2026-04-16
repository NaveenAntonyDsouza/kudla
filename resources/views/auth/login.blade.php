<x-layouts.auth title="Login">
    @php
        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
        $mobileOtpEnabled = \App\Models\SiteSetting::getValue('mobile_otp_login_enabled', '1') === '1';
        $emailOtpEnabled = \App\Models\SiteSetting::getValue('email_otp_login_enabled', '0') === '1';

        // Determine default tab
        $defaultTab = $mobileOtpEnabled ? 'mobile' : ($emailOtpEnabled ? 'email_otp' : 'email');

        // Count enabled tabs for layout
        $enabledTabs = ($mobileOtpEnabled ? 1 : 0) + ($emailOtpEnabled ? 1 : 0) + 1; // email+password always enabled
    @endphp

    <h2 class="text-xl font-serif font-bold text-gray-900 text-center mb-6">Login to {{ $siteName }}</h2>

    {{-- Tab Navigation --}}
    <div x-data="{
        tab: '{{ session('email_otp_sent') ? 'email_otp' : (session('otp_sent') ? 'mobile' : $defaultTab) }}',
        mobileOtpSent: {{ session('otp_sent') ? 'true' : 'false' }},
        emailOtpSent: {{ session('email_otp_sent') ? 'true' : 'false' }},
        cooldown: 0,
        emailCooldown: 0,
        phone: '{{ session('login_phone', old('phone', '')) }}',
        loginEmail: '{{ session('login_email', old('email', '')) }}'
    }" x-init="
        if (mobileOtpSent) { tab = 'mobile'; cooldown = 30; let timer = setInterval(() => { cooldown--; if (cooldown <= 0) clearInterval(timer); }, 1000); }
        if (emailOtpSent) { tab = 'email_otp'; emailCooldown = 30; let timer2 = setInterval(() => { emailCooldown--; if (emailCooldown <= 0) clearInterval(timer2); }, 1000); }
    ">
        {{-- Tabs (hide when only 1 option) --}}
        <div class="flex border-b border-gray-200 mb-6" @if($enabledTabs <= 1) style="display:none" @endif>
            @if($mobileOtpEnabled)
            <button
                x-on:click="tab = 'mobile'"
                :class="tab === 'mobile' ? 'border-b-2 border-(--color-primary) text-(--color-primary)' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 pb-3 text-sm font-semibold text-center transition-colors"
            >
                Mobile OTP
            </button>
            @endif

            @if($emailOtpEnabled)
            <button
                x-on:click="tab = 'email_otp'"
                :class="tab === 'email_otp' ? 'border-b-2 border-(--color-primary) text-(--color-primary)' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 pb-3 text-sm font-semibold text-center transition-colors"
            >
                Email OTP
            </button>
            @endif

            <button
                x-on:click="tab = 'email'"
                :class="tab === 'email' ? 'border-b-2 border-(--color-primary) text-(--color-primary)' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 pb-3 text-sm font-semibold text-center transition-colors"
            >
                Email & Password
            </button>
        </div>

        {{-- ========== Mobile OTP Tab ========== --}}
        @if($mobileOtpEnabled)
        <div x-show="tab === 'mobile'" x-cloak>
            {{-- Send OTP Form --}}
            <div x-show="!mobileOtpSent">
                <form method="POST" action="{{ route('login.otp.send') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                        <x-phone-input name="phone" variant="login" :required="true" maxlength="10" xModel="phone" />
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">
                        Send OTP
                    </button>
                </form>
            </div>

            {{-- Verify OTP Form --}}
            <div x-show="mobileOtpSent">
                <form method="POST" action="{{ route('login.otp.verify') }}">
                    @csrf
                    <input type="hidden" name="phone" x-bind:value="phone">

                    <p class="text-sm text-gray-600 mb-4">
                        OTP sent to <strong x-text="'+91 ' + phone"></strong>
                        <button type="button" x-on:click="mobileOtpSent = false" class="text-(--color-primary) hover:underline ml-1 text-sm">Change</button>
                    </p>

                    <div class="mb-4">
                        <label for="login_otp" class="block text-sm font-medium text-gray-700 mb-1">Enter OTP</label>
                        <input
                            type="text"
                            id="login_otp"
                            name="otp"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            placeholder="Enter 6-digit OTP"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full tracking-widest text-center text-lg"
                            required
                            autofocus
                        >
                        @error('otp')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">
                        Verify & Login
                    </button>

                    {{-- Resend OTP with cooldown --}}
                    <div class="mt-4 text-center">
                        <template x-if="cooldown > 0">
                            <span class="text-sm text-gray-400">Resend OTP in <span x-text="cooldown"></span>s</span>
                        </template>
                        <template x-if="cooldown <= 0">
                            <form method="POST" action="{{ route('login.otp.send') }}" class="inline">
                                @csrf
                                <input type="hidden" name="phone" x-bind:value="phone">
                                <button type="submit" class="text-sm text-(--color-primary) hover:underline font-medium">
                                    Resend OTP
                                </button>
                            </form>
                        </template>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- ========== Email OTP Tab ========== --}}
        @if($emailOtpEnabled)
        <div x-show="tab === 'email_otp'" x-cloak>
            {{-- Send Email OTP Form --}}
            <div x-show="!emailOtpSent">
                <form method="POST" action="{{ route('login.email-otp.send') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="email_otp_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input
                            type="email"
                            id="email_otp_email"
                            name="email"
                            x-model="loginEmail"
                            placeholder="you@example.com"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                            required
                        >
                        @error('login_email_otp')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">
                        Send OTP
                    </button>
                </form>
            </div>

            {{-- Verify Email OTP Form --}}
            <div x-show="emailOtpSent">
                <form method="POST" action="{{ route('login.email-otp.verify') }}">
                    @csrf
                    <input type="hidden" name="email" x-bind:value="loginEmail">

                    <p class="text-sm text-gray-600 mb-4">
                        OTP sent to <strong x-text="loginEmail"></strong>
                        <button type="button" x-on:click="emailOtpSent = false" class="text-(--color-primary) hover:underline ml-1 text-sm">Change</button>
                    </p>

                    <div class="mb-4">
                        <label for="email_login_otp" class="block text-sm font-medium text-gray-700 mb-1">Enter OTP</label>
                        <input
                            type="text"
                            id="email_login_otp"
                            name="otp"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            placeholder="Enter 6-digit OTP"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full tracking-widest text-center text-lg"
                            required
                            autofocus
                        >
                        @error('otp')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">
                        Verify & Login
                    </button>

                    {{-- Resend OTP with cooldown --}}
                    <div class="mt-4 text-center">
                        <template x-if="emailCooldown > 0">
                            <span class="text-sm text-gray-400">Resend OTP in <span x-text="emailCooldown"></span>s</span>
                        </template>
                        <template x-if="emailCooldown <= 0">
                            <form method="POST" action="{{ route('login.email-otp.send') }}" class="inline">
                                @csrf
                                <input type="hidden" name="email" x-bind:value="loginEmail">
                                <button type="submit" class="text-sm text-(--color-primary) hover:underline font-medium">
                                    Resend OTP
                                </button>
                            </form>
                        </template>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- ========== Email & Password Tab ========== --}}
        <div x-show="tab === 'email'" x-cloak>
            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="mb-4">
                    <label for="login_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input
                        type="email"
                        id="login_email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        required
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="login_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input
                        type="password"
                        id="login_password"
                        name="password"
                        placeholder="Enter your password"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        required
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                        Remember me
                    </label>
                    <a href="/forgot-password" class="text-sm text-(--color-primary) hover:underline">Forgot Password?</a>
                </div>

                <button type="submit" class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">
                    Login
                </button>
            </form>
        </div>
    </div>

    {{-- Forgot password + Register --}}
    <p class="mt-4 text-center text-sm">
        <a href="{{ route('password.request') }}" class="text-gray-500 hover:text-(--color-primary) hover:underline">Forgot Password?</a>
    </p>
    <p class="mt-2 text-center text-sm text-gray-500">
        New here? <a href="{{ route('register') }}" class="text-(--color-primary) font-semibold hover:underline">Register Free</a>
    </p>
</x-layouts.auth>
