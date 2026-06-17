<x-layouts.app title="Membership">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 mb-3">All Features Are Free</h1>
        <p class="text-gray-600 max-w-lg mx-auto mb-8">
            Every feature on {{ config('app.name') }} is currently free for all members.
            You already have full access — no plan or payment needed. Enjoy connecting!
        </p>
        <a href="{{ url('/dashboard') }}"
           class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-(--color-primary) text-white font-semibold hover:opacity-90 transition">
            Go to Dashboard
        </a>
    </div>
</x-layouts.app>
