<x-layouts.app title="Your email address">

    <div class="max-w-xl mx-auto px-4 py-8">

        <div class="bg-white rounded-lg border border-gray-200 p-5 sm:p-6">

            <h1 class="text-xl font-semibold text-gray-900">Your email is not reaching you</h1>
            <p class="mt-2 text-sm text-gray-600">
                Messages we send to <span class="font-medium text-gray-900">{{ $user->email }}</span> are coming back to us,
                so you are not getting your weekly matches or interest alerts. Give us an address that works and we will
                start again.
            </p>

            @if ($errors->any())
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="text-sm text-red-600 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('otp_resent'))
                <p class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">{{ session('otp_resent') }}</p>
            @endif

            @if (session('fix_email_sent'))
                {{-- Step 2: the code we just sent to the new address --}}
                <p class="mt-4 text-sm text-gray-700">
                    We have sent a six-digit code to <span class="font-medium text-gray-900">{{ session('fix_email_address') }}</span>.
                    Open that mailbox and type the code below. It can take a minute to arrive, and it may be in the spam folder.
                </p>

                <form method="POST" action="{{ route('email.fix.confirm') }}" class="mt-4 flex flex-col gap-3">
                    @csrf
                    <label for="code" class="text-sm font-medium text-gray-700">Code</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                           maxlength="6" required autofocus
                           class="w-40 rounded-lg border border-gray-300 px-3 py-2 text-lg tracking-widest focus:border-(--color-primary) focus:ring-(--color-primary)">
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                                class="px-4 py-2 rounded-lg text-white font-medium" style="background: var(--color-primary);">
                            Confirm this address
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('email.fix.send') }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="email" value="{{ session('fix_email_address') }}">
                    <input type="hidden" name="resend" value="1">
                    <button type="submit" class="text-sm underline" style="color: var(--color-primary);">Send the code again</button>
                </form>
            @else
                {{-- Step 1: the new address --}}
                <form method="POST" action="{{ route('email.fix.send') }}" class="mt-5 flex flex-col gap-3">
                    @csrf
                    <label for="email" class="text-sm font-medium text-gray-700">Your new email address</label>
                    <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-(--color-primary) focus:ring-(--color-primary)">
                    <p class="text-xs text-gray-500">We will send a six-digit code there to make sure it works.</p>
                    <div>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg text-white font-medium" style="background: var(--color-primary);">
                            Send me a code
                        </button>
                    </div>
                </form>
            @endif

            <p class="mt-6 text-xs text-gray-500">
                Prefer to tell us instead? Use <a href="{{ route('contact') }}" class="underline">Contact us</a> and we will fix it for you.
            </p>

        </div>
    </div>

</x-layouts.app>
