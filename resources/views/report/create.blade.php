<x-layouts.app title="Report Profile">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('profile.view', $profile) }}" class="hover:text-(--color-primary)">{{ $profile->matri_id }}</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Report</span>
        </p>

        <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
            <h1 class="text-lg font-semibold text-gray-900 mb-1">Report Profile</h1>
            <p class="text-sm text-gray-500 mb-6">Reporting {{ $profile->matri_id }}. Your identity will not be disclosed to the reported member.</p>

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-600">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('report.store', $profile) }}" x-data="{ reason: '{{ old('reason', '') }}', submitting: false }" @submit="submitting = true">
                @csrf

                {{-- Reason --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Reason for reporting *</label>
                    <div class="space-y-2">
                        @foreach($reasons as $key => $label)
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-gray-50 transition-colors"
                                :class="reason === '{{ $key }}' ? 'border-(--color-primary) bg-(--color-primary-light)' : 'border-gray-200'"
                                @click="reason = '{{ $key }}'">
                                <input type="radio" name="reason" value="{{ $key }}" x-model="reason"
                                    class="mt-0.5 text-(--color-primary) focus:ring-(--color-primary)">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Description --}}
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Additional details (optional)</label>
                    <textarea name="description" id="description" rows="4" maxlength="1000" placeholder="Please provide any details that will help us investigate..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('description') }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">Max 1000 characters</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="submitting || !reason"
                        :class="(submitting || !reason) && 'opacity-50 cursor-not-allowed'"
                        class="px-6 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">
                        <span x-show="!submitting">Submit Report</span>
                        <span x-show="submitting" x-cloak>Submitting...</span>
                    </button>
                    <a href="{{ route('profile.view', $profile) }}" class="px-6 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                </div>

                <p class="mt-4 text-xs text-gray-400">Your report will be reviewed by our team within 24-48 hours. False reports may result in action against your account.</p>
            </form>
        </div>
    </div>
</x-layouts.app>
