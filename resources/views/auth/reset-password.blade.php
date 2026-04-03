<x-layouts.app title="Reset Password">
    <div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <h1 class="text-2xl font-serif font-bold text-gray-900 text-center mb-8">Reset Password</h1>

            @if($errors->any())
                <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-600 font-medium">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="space-y-5">
                        <div class="float-field">
                            <input type="email" name="email" value="{{ $email ?? old('email') }}" required placeholder=" ">
                            <label>Email Address</label>
                        </div>
                        <div class="float-field">
                            <input type="password" name="password" required minlength="6" maxlength="14" placeholder=" ">
                            <label>New Password</label>
                        </div>
                        <div class="float-field">
                            <input type="password" name="password_confirmation" required placeholder=" ">
                            <label>Confirm New Password</label>
                        </div>
                    </div>
                    <button type="submit" class="w-full mt-6 bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
