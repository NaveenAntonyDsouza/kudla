<x-layouts.auth title="Verify Phone - Registration">
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-(--color-primary-light) mb-4">
            <svg class="w-8 h-8 text-(--color-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-xl font-serif font-bold text-gray-900">Verify Your Phone</h2>
        <p class="mt-2 text-sm text-gray-500">We will send a 6-digit OTP to <strong>{{ auth()->user()->phone }}</strong></p>
    </div>

    @if (session('otp_sent'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-700 font-medium">OTP sent successfully! Check your phone.</p>
        </div>
    @endif

    <div x-data="{
        otpSent: {{ session('otp_sent') ? 'true' : 'false' }},
        cooldown: 0,
        timer: null,
        startCooldown() {
            this.cooldown = 30;
            this.timer = setInterval(() => {
                this.cooldown--;
                if (this.cooldown <= 0) clearInterval(this.timer);
            }, 1000);
        }
    }">
        {{-- Send OTP Button --}}
        <div x-show="!otpSent" class="mb-4">
            <form method="POST" action="{{ route('register.sendotp') }}" @submit="otpSent = true; $nextTick(() => startCooldown())">
                @csrf
                <button type="submit"
                    class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">
                    Send OTP
                </button>
            </form>
        </div>

        {{-- OTP Verification Form --}}
        <div x-show="otpSent" x-transition>
            <form method="POST" action="{{ route('register.verifyotp') }}">
                @csrf
                <div class="mb-4">
                    <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">Enter 6-digit OTP</label>
                    <input type="text" name="otp" id="otp" maxlength="6" pattern="[0-9]{6}" required
                        class="border border-gray-300 rounded-lg px-3 py-3 text-center text-lg tracking-widest font-mono focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="------" autocomplete="one-time-code" inputmode="numeric">
                    @error('otp') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                    class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">
                    Verify OTP
                </button>
            </form>

            {{-- Resend OTP --}}
            <div class="mt-4 text-center">
                <form method="POST" action="{{ route('register.sendotp') }}" @submit="startCooldown()">
                    @csrf
                    <button type="submit" :disabled="cooldown > 0"
                        class="text-sm font-medium disabled:text-gray-400 disabled:cursor-not-allowed"
                        :class="cooldown > 0 ? 'text-gray-400' : 'text-(--color-primary) hover:underline'">
                        <span x-show="cooldown > 0">Resend OTP in <span x-text="cooldown"></span>s</span>
                        <span x-show="cooldown <= 0">Resend OTP</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6 pt-4 border-t border-gray-200">
        <a href="{{ route('register.step5') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Step 5</a>
    </div>
</x-layouts.auth>
