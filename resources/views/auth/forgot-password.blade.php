<x-layouts.app title="Forgot Password">
    <div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <h1 class="text-2xl font-serif font-bold text-gray-900 text-center mb-2">Forgot Password?</h1>
            <p class="text-sm text-gray-500 text-center mb-8">Enter your registered email and we'll send you a reset link.</p>

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

            <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="float-field mb-5">
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder=" ">
                        <label>Email Address</label>
                    </div>
                    <button type="submit" class="w-full bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">
                        Send Reset Link
                    </button>
                </form>
            </div>

            <p class="text-center mt-4 text-sm text-gray-500">
                Remember your password? <a href="{{ route('login') }}" class="text-(--color-primary) hover:underline font-medium">Login</a>
            </p>
        </div>
    </div>
</x-layouts.app>
