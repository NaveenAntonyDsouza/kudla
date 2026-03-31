<x-layouts.auth title="Login">
    @php
        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    @endphp

    <h2 class="text-xl font-serif font-bold text-gray-900 text-center mb-6">Login to {{ $siteName }}</h2>

    {{-- Tab Navigation --}}
    <div x-data="{ tab: 'mobile', otpSent: {{ session('otp_sent') ? 'true' : 'false' }}, cooldown: 0, phone: '{{ session('login_phone', old('phone', '')) }}' }" x-init="
        if (otpSent) { tab = 'mobile'; cooldown = 30; let timer = setInterval(() => { cooldown--; if (cooldown <= 0) clearInterval(timer); }, 1000); }
    ">
        {{-- Tabs --}}
        <div class="flex border-b border-gray-200 mb-6">
            <button
                x-on:click="tab = 'mobile'"
                :class="tab === 'mobile' ? 'border-b-2 border-(--color-primary) text-(--color-primary)' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 pb-3 text-sm font-semibold text-center transition-colors"
            >
                Mobile Number
            </button>
            <button
                x-on:click="tab = 'email'"
                :class="tab === 'email' ? 'border-b-2 border-(--color-primary) text-(--color-primary)' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 pb-3 text-sm font-semibold text-center transition-colors"
            >
                Email & Password
            </button>
        </div>

        {{-- Mobile OTP Tab --}}
        <div x-show="tab === 'mobile'" x-cloak>
            {{-- Send OTP Form --}}
            <div x-show="!otpSent">
                <form method="POST" action="{{ route('login.otp.send') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="login_phone" class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                IN +91
                            </span>
                            <input
                                type="tel"
                                id="login_phone"
                                name="phone"
                                x-model="phone"
                                maxlength="10"
                                pattern="[0-9]{10}"
                                placeholder="Enter 10-digit mobile number"
                                class="flex-1 border border-gray-300 rounded-r-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)"
                                required
                            >
                        </div>
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
            <div x-show="otpSent">
                <form method="POST" action="{{ route('login.otp.verify') }}">
                    @csrf
                    <input type="hidden" name="phone" x-bind:value="phone">

                    <p class="text-sm text-gray-600 mb-4">
                        OTP sent to <strong x-text="'+91 ' + phone"></strong>
                        <button type="button" x-on:click="otpSent = false" class="text-(--color-primary) hover:underline ml-1 text-sm">Change</button>
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

        {{-- Email & Password Tab --}}
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

    {{-- Register link --}}
    <p class="mt-6 text-center text-sm text-gray-500">
        New here? <a href="{{ route('register') }}" class="text-(--color-primary) font-semibold hover:underline">Register Free</a>
    </p>
</x-layouts.auth>
