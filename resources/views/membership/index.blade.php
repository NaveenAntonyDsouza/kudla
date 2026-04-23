<x-layouts.app title="Membership Plans">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-2">Upgrade and enjoy added benefits</h1>
        <p class="text-center text-gray-500 mb-4">Choose the plan that suits you best</p>

        {{-- Launch Offer Banner --}}
        <div class="mb-8 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-5 text-center">
            <div class="inline-flex items-center gap-2 bg-amber-500 text-white text-xs font-bold px-3 py-1 rounded-full mb-3">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                LAUNCH OFFER
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">All Premium Plans at just ₹1!</h3>
            <p class="text-sm text-gray-600 max-w-lg mx-auto">We're celebrating our launch by offering all premium features at a special introductory price. Grab your plan now — prices will increase soon!</p>
        </div>

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
        @if($activeMembership)
            <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-green-800">Your Active Plan: {{ $activeMembership->plan->plan_name }}</p>
                    <p class="text-xs text-green-600">
                        @if($activeMembership->ends_at)
                            Valid until {{ $activeMembership->ends_at->format('d M Y') }}
                        @else
                            Lifetime access
                        @endif
                    </p>
                </div>
                <span class="text-xs font-medium px-3 py-1 rounded-full bg-green-500 text-white">Active</span>
            </div>
        @endif

        {{-- Pricing Cards --}}
        @php
            $paidPlans = $plans->where('slug', '!=', 'free');
            $colors = ['#8B5CF6', '#8B1D91', '#D97706', '#059669'];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min($paidPlans->count(), 4) }} gap-6 mb-12">
            @foreach($paidPlans as $index => $plan)
                @php $color = $colors[$index % count($colors)]; @endphp
                <div class="relative bg-white rounded-xl border-2 {{ $plan->is_highlighted ? 'border-(--color-primary) shadow-lg' : 'border-gray-200' }} overflow-hidden">
                    @if($plan->is_highlighted)
                        <div class="absolute top-0 right-0 bg-(--color-primary) text-white text-xs font-bold px-3 py-1 rounded-bl-lg">POPULAR</div>
                    @endif
                    <div class="p-6 text-center">
                        <h3 class="text-lg font-bold uppercase tracking-wider" style="color: {{ $color }}">{{ $plan->plan_name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $plan->duration_months }} {{ Str::plural('Month', $plan->duration_months) }} Access</p>

                        <div class="mt-4">
                            @if($plan->strike_price_inr)
                                <span class="text-sm text-gray-400 line-through">&#8377;{{ number_format($plan->strike_price_inr) }}</span>
                            @endif
                            @if($plan->price_inr > 0)
                                <div class="text-3xl font-bold text-gray-900">&#8377;{{ number_format($plan->price_inr) }}</div>
                            @else
                                <div class="text-3xl font-bold text-green-600">Free</div>
                            @endif
                        </div>

                        @if($activePlanId === $plan->id)
                            <div class="mt-6 px-6 py-2.5 text-sm font-semibold text-green-700 bg-green-100 rounded-lg">Current Plan</div>
                        @elseif($plan->price_inr > 0)
                            <form method="POST" action="{{ route('membership.checkout') }}" class="mt-6" x-data="couponForm({{ $plan->id }}, {{ $plan->price_inr }})">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="hidden" name="coupon_code" :value="appliedCode">

                                {{-- Coupon Toggle --}}
                                <div class="mb-3 text-left">
                                    <button type="button" @click="showCoupon = !showCoupon" class="text-xs text-gray-500 hover:text-gray-700 underline">
                                        Have a coupon code?
                                    </button>

                                    <div x-show="showCoupon" x-transition class="mt-2">
                                        <div class="flex gap-1">
                                            <input type="text" x-model="couponInput" placeholder="Enter code"
                                                class="flex-1 px-3 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-purple-500 focus:border-purple-500 uppercase"
                                                :disabled="appliedCode !== ''" @keydown.enter.prevent="applyCoupon()">
                                            <button type="button"
                                                x-show="appliedCode === ''"
                                                @click="applyCoupon()"
                                                :disabled="loading"
                                                class="px-3 py-1.5 text-xs font-medium text-white rounded-md" style="background: {{ $color }};">
                                                <span x-show="!loading">Apply</span>
                                                <span x-show="loading">...</span>
                                            </button>
                                            <button type="button"
                                                x-show="appliedCode !== ''"
                                                @click="removeCoupon()"
                                                class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-md">
                                                Remove
                                            </button>
                                        </div>
                                        <p x-show="errorMsg" class="text-xs text-red-500 mt-1" x-text="errorMsg"></p>
                                        <div x-show="appliedCode" class="mt-1.5 p-2 bg-green-50 border border-green-200 rounded-md">
                                            <p class="text-xs text-green-700 font-medium">
                                                Coupon <span x-text="appliedCode" class="font-bold"></span> applied!
                                                Discount: ₹<span x-text="discountAmount"></span>
                                            </p>
                                            <p class="text-xs text-green-600 mt-0.5">
                                                You pay: ₹<span x-text="finalPrice" class="font-bold"></span>
                                                <span class="line-through text-gray-400 ml-1">₹{{ number_format($plan->price_inr) }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="w-full px-6 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors hover:opacity-90" style="background: {{ $color }};">
                                    UPGRADE
                                </button>
                            </form>
                        @else
                            <div class="mt-6 px-6 py-2.5 text-sm font-semibold text-green-700 bg-green-100 rounded-lg text-center">Free During Launch</div>
                        @endif
                    </div>

                    <div class="px-6 pb-6">
                        <div class="border-t border-gray-100 pt-4 space-y-3">
                            @foreach(is_array($plan->features) ? $plan->features : [] as $feature)
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                    <span class="text-gray-700">{{ $feature }}</span>
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
                                <th class="text-center px-4 py-3 font-semibold text-(--color-primary)">{{ $plan->plan_name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-6 py-3 text-gray-600">Duration</td>
                            @foreach($plans as $plan)
                                <td class="text-center px-4 py-3 font-medium text-gray-900">{{ $plan->duration_months ?: 'Free' }} {{ $plan->duration_months ? Str::plural('Month', $plan->duration_months) : '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-600">Price</td>
                            @foreach($plans as $plan)
                                <td class="text-center px-4 py-3 font-bold text-gray-900">{{ $plan->price_inr > 0 ? '₹' . number_format($plan->price_inr) : 'Free' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-600">Interests / Day</td>
                            @foreach($plans as $plan)
                                <td class="text-center px-4 py-3 font-medium text-gray-900">{{ $plan->daily_interest_limit }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-600">View Contact Details</td>
                            @foreach($plans as $plan)
                                <td class="text-center px-4 py-3">
                                    @if($plan->can_view_contact)
                                        <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-600">Personalized Messages</td>
                            @foreach($plans as $plan)
                                <td class="text-center px-4 py-3">
                                    @if($plan->price_inr > 0)
                                        <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-600">Featured Profile</td>
                            @foreach($plans as $plan)
                                <td class="text-center px-4 py-3">
                                    @if($plan->is_highlighted)
                                        <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function couponForm(planId, originalPrice) {
            return {
                showCoupon: false,
                couponInput: '',
                appliedCode: '',
                discountAmount: 0,
                finalPrice: originalPrice,
                errorMsg: '',
                loading: false,

                async applyCoupon() {
                    if (!this.couponInput.trim()) return;
                    this.loading = true;
                    this.errorMsg = '';

                    try {
                        const response = await fetch('{{ route("membership.applyCoupon") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                coupon_code: this.couponInput.trim(),
                                plan_id: planId,
                            }),
                        });
                        const data = await response.json();

                        if (data.valid) {
                            this.appliedCode = data.coupon_code;
                            this.discountAmount = data.discount;
                            this.finalPrice = data.final_price;
                            this.errorMsg = '';
                        } else {
                            this.errorMsg = data.message;
                            this.appliedCode = '';
                        }
                    } catch (e) {
                        this.errorMsg = 'Something went wrong. Please try again.';
                    }

                    this.loading = false;
                },

                removeCoupon() {
                    this.appliedCode = '';
                    this.couponInput = '';
                    this.discountAmount = 0;
                    this.finalPrice = originalPrice;
                    this.errorMsg = '';
                },
            };
        }
    </script>
</x-layouts.app>
