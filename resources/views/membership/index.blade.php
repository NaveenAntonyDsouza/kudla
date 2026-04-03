<x-layouts.app title="Membership Plans">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-2">Upgrade and enjoy added benefits</h1>
        <p class="text-center text-gray-500 mb-10">Choose the plan that suits you best</p>

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

        {{-- Active subscription banner --}}
        @if($activeSub)
            <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-green-800">Your Active Plan: {{ $activeSub->plan_name }}</p>
                    <p class="text-xs text-green-600">Valid until {{ $activeSub->expires_at->format('d M Y') }}</p>
                </div>
                <span class="text-xs font-medium px-3 py-1 rounded-full bg-green-500 text-white">Active</span>
            </div>
        @endif

        {{-- Pricing Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-12">
            @foreach($plans as $plan)
                <div class="relative bg-white rounded-xl border-2 {{ ($plan['popular'] ?? false) ? 'border-(--color-primary) shadow-lg' : 'border-gray-200' }} overflow-hidden">
                    @if($plan['popular'] ?? false)
                        <div class="absolute top-0 right-0 bg-(--color-primary) text-white text-xs font-bold px-3 py-1 rounded-bl-lg">POPULAR</div>
                    @endif
                    <div class="p-6 text-center">
                        <h3 class="text-lg font-bold uppercase tracking-wider" style="color: {{ $plan['color'] }}">{{ $plan['name'] }}</h3>
                        <p class="text-sm text-gray-500 mt-1">Get {{ $plan['duration_months'] }} Months Access</p>

                        <div class="mt-4">
                            <span class="text-sm text-gray-400 line-through">&#8377;{{ number_format($plan['original_price']) }}</span>
                            <div class="text-3xl font-bold text-gray-900">&#8377;{{ number_format($plan['price']) }}</div>
                        </div>

                        @if($activeSub && $activeSub->plan_id === $plan['id'])
                            <div class="mt-6 px-6 py-2.5 text-sm font-semibold text-green-700 bg-green-100 rounded-lg">Current Plan</div>
                        @else
                            <form method="POST" action="{{ route('membership.checkout') }}" class="mt-6">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan['id'] }}">
                                <button type="submit" class="w-full px-6 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors" style="background: {{ $plan['color'] }};">
                                    UPGRADE
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="px-6 pb-6">
                        <div class="border-t border-gray-100 pt-4 space-y-3">
                            @foreach($plan['features'] as $feature => $included)
                                <div class="flex items-center gap-2 text-sm">
                                    @if($included)
                                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                    @else
                                        <svg class="w-4 h-4 text-red-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                                    @endif
                                    <span class="{{ $included ? 'text-gray-700' : 'text-gray-400' }}">{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Compare Plans Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="p-6 text-center border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Compare Plans</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="text-left px-6 py-3 font-semibold text-gray-700">Features</th>
                            @foreach($plans as $plan)
                                <th class="text-center px-4 py-3 font-semibold" style="color: {{ $plan['color'] }}">{{ $plan['name'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-6 py-3 text-gray-600">Duration</td>
                            @foreach($plans as $plan)
                                <td class="text-center px-4 py-3 font-medium text-gray-900">{{ $plan['duration_months'] }} Months</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-600">Price</td>
                            @foreach($plans as $plan)
                                <td class="text-center px-4 py-3 font-bold text-gray-900">&#8377;{{ number_format($plan['price']) }}</td>
                            @endforeach
                        </tr>
                        @php
                            $allFeatures = collect($plans)->flatMap(fn($p) => array_keys($p['features']))->unique()->values();
                        @endphp
                        @foreach($allFeatures as $feature)
                            <tr>
                                <td class="px-6 py-3 text-gray-600">{{ $feature }}</td>
                                @foreach($plans as $plan)
                                    <td class="text-center px-4 py-3">
                                        @if($plan['features'][$feature] ?? false)
                                            <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                        @else
                                            <svg class="w-5 h-5 text-red-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
