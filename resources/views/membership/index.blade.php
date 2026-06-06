<x-layouts.app title="Membership Plans">
@php
    // Everything on this page is driven by the plans managed in admin
    // (/console/membership-plans) — no hardcoded plan data.
    $paidPlans = $plans->where('price_inr', '>', 0)->values();

    // Literal grid classes so Tailwind's build picks them up (no dynamic class names).
    $gridColsClass = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3'][$paidPlans->count()] ?? 'lg:grid-cols-4';

    // Distinct accent colour per paid plan (free = neutral gray). Used for the card
    // name + button + popular border and the compare-table column header.
    $palette = ['#0d9488', '#7c3aed', '#db2777', '#4f46e5', '#d97706', '#0284c7'];
    $planColors = [];
    $ci = 0;
    foreach ($plans as $pl) {
        if ($pl->price_inr > 0) { $planColors[$pl->id] = $palette[$ci % count($palette)]; $ci++; }
        else { $planColors[$pl->id] = '#6b7280'; }
    }

    // Contact details for the "Need help paying?" strip (admin-managed in Site Settings).
    $sitePhone    = \App\Models\SiteSetting::getValue('phone', '');
    $siteWhatsApp = \App\Models\SiteSetting::getValue('whatsapp', '');
    $phoneDigits  = preg_replace('/\D/', '', (string) $sitePhone);
    $waDigits     = preg_replace('/\D/', '', (string) $siteWhatsApp);

    // Plan-model convention: 0 = unlimited.
    $fmtLimit = fn ($v) => ((int) $v) === 0 ? 'Unlimited' : number_format((int) $v);

    // Tick = brand colour, cross = red.
    $checkIcon  = '<svg class="w-4 h-4 text-(--color-primary) shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>';
    $tableTick  = '<svg class="w-5 h-5 text-(--color-primary) mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>';
    $tableCross = '<svg class="w-5 h-5 text-red-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>';

    // Compare-table rows: [label, value(plan), type 'text'|'bool', alwaysShow].
    $rowDefs = [
        ['Duration',              fn ($p) => $p->duration_months ? $p->duration_months . ' ' . Str::plural('Month', $p->duration_months) : 'Free', 'text', true],
        ['Price',                 fn ($p) => $p->price_inr > 0 ? '&#8377;' . number_format($p->price_inr) : 'Free', 'text', true],
        ['View Contact Details',  fn ($p) => (bool) $p->can_view_contact, 'bool', false],
        ['Total Contact Views',   fn ($p) => $p->can_view_contact ? $fmtLimit($p->view_contacts_limit) : '&#8212;', 'text', false],
        ['Daily Contact Views',   fn ($p) => $p->can_view_contact ? $fmtLimit($p->daily_contact_views) : '&#8212;', 'text', false],
        ['Interests per Day',     fn ($p) => number_format((int) $p->daily_interest_limit), 'text', false],
        ['Personalized Messages', fn ($p) => (bool) $p->personalized_messages, 'bool', false],
        ['Featured Profile',      fn ($p) => (bool) $p->featured_profile, 'bool', false],
        ['Priority Support',      fn ($p) => (bool) $p->priority_support, 'bool', false],
    ];
    // Auto-hide rows where every plan is identical/empty (keep the alwaysShow rows).
    $visibleRows = collect($rowDefs)->filter(function ($r) use ($plans) {
        if ($r[3]) return true;
        $vals = $plans->map(function ($p) use ($r) {
            $v = $r[1]($p);
            return is_bool($v) ? ($v ? '1' : '0') : (string) $v;
        });
        return $vals->unique()->count() > 1;
    })->values();
@endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-gray-900 text-center mb-2">Upgrade and enjoy added benefits</h1>
        <p class="text-center text-gray-500 mb-8">Choose the plan that suits you best</p>

        @if(session('success'))
            <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-700 font-medium">{{ session('info') }}</p>
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

        {{-- ── Pricing Cards ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 {{ $gridColsClass }} gap-6 mb-12">
            @foreach($paidPlans as $plan)
                @php $pc = $planColors[$plan->id]; @endphp
                <div id="plan-{{ $plan->id }}" class="relative bg-white rounded-xl border-2 overflow-hidden flex flex-col scroll-mt-24 {{ $plan->is_popular ? 'shadow-lg' : 'border-gray-200' }}" @if($plan->is_popular) style="border-color: {{ $pc }};" @endif>
                    @if($plan->is_popular)
                        <div class="absolute top-0 right-0 text-white text-[11px] font-bold px-3 py-1 rounded-bl-lg tracking-wide" style="background-color: {{ $pc }};">POPULAR</div>
                    @endif

                    <div class="p-6 text-center">
                        <h3 class="text-lg font-bold uppercase tracking-wider" style="color: {{ $pc }};">{{ $plan->plan_name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">Get {{ $plan->duration_months }} {{ Str::plural('Month', $plan->duration_months) }} Access</p>

                        <div class="mt-4">
                            @if($plan->strike_price_inr)
                                <span class="text-sm text-gray-400 line-through">&#8377;{{ number_format($plan->strike_price_inr) }}</span>
                            @endif
                            <div class="text-3xl font-bold text-gray-900">&#8377;{{ number_format($plan->price_inr) }}</div>
                            <p class="text-[11px] text-gray-400 mt-1">Taxes extra as applicable</p>
                        </div>

                        @if($activePlanId === $plan->id)
                            <div class="mt-5 px-6 py-2.5 text-sm font-semibold text-green-700 bg-green-100 rounded-lg">Current Plan</div>
                        @else
                            <form method="POST" action="{{ route('membership.checkout') }}" class="mt-5" x-data="couponForm({{ $plan->id }}, {{ $plan->price_inr }})">
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
                                                class="flex-1 px-3 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-(--color-primary) focus:border-(--color-primary) uppercase"
                                                :disabled="appliedCode !== ''" @keydown.enter.prevent="applyCoupon()">
                                            <button type="button"
                                                x-show="appliedCode === ''"
                                                @click="applyCoupon()"
                                                :disabled="loading"
                                                class="px-3 py-1.5 text-xs font-medium text-white rounded-md hover:opacity-90" style="background-color: {{ $pc }};">
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
                                                Discount: &#8377;<span x-text="discountAmount"></span>
                                            </p>
                                            <p class="text-xs text-green-600 mt-0.5">
                                                You pay: &#8377;<span x-text="finalPrice" class="font-bold"></span>
                                                <span class="line-through text-gray-400 ml-1">&#8377;{{ number_format($plan->price_inr) }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="w-full px-6 py-2.5 text-sm font-semibold text-white rounded-lg hover:opacity-90 transition-opacity" style="background-color: {{ $pc }};">
                                    UPGRADE
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- Highlights + feature labels (all from plan fields) --}}
                    <div class="px-6 pb-6 mt-auto">
                        <div class="border-t border-gray-100 pt-4 space-y-2.5 text-left">
                            @if($plan->can_view_contact)
                                <div class="flex items-center gap-2 text-sm">{!! $checkIcon !!}<span class="text-gray-700">View Contacts: <strong class="font-semibold">{{ $fmtLimit($plan->view_contacts_limit) }}</strong></span></div>
                                <div class="flex items-center gap-2 text-sm">{!! $checkIcon !!}<span class="text-gray-700">Daily Contact Views: <strong class="font-semibold">{{ $fmtLimit($plan->daily_contact_views) }}</strong></span></div>
                            @endif
                            <div class="flex items-center gap-2 text-sm">{!! $checkIcon !!}<span class="text-gray-700">Interests: <strong class="font-semibold">{{ number_format($plan->daily_interest_limit) }}/day</strong></span></div>
                            @foreach(is_array($plan->features) ? $plan->features : [] as $feature)
                                <div class="flex items-center gap-2 text-sm">{!! $checkIcon !!}<span class="text-gray-700">{{ $feature }}</span></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── Compare Plans Table ── --}}
        <div id="compareplans" class="scroll-mt-24 bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden mb-12">
            <div class="p-6 text-center border-b border-gray-100">
                <h2 class="text-xl font-serif font-bold text-gray-900">Compare Plans</h2>
                <p class="text-sm text-gray-500 mt-1">Find the plan that fits you best</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" style="min-width:640px;">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-5 py-3 font-semibold text-gray-700" style="position:sticky;left:0;background:#f9fafb;z-index:1;">Features</th>
                            @foreach($plans as $plan)
                                <th class="text-center px-4 py-3 font-semibold" style="color: {{ $planColors[$plan->id] }};">
                                    {{ $plan->plan_name }}
                                    @if($plan->is_popular)
                                        <div class="text-[10px] font-bold tracking-wide" style="color: {{ $planColors[$plan->id] }};">POPULAR</div>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($visibleRows as $row)
                            <tr>
                                <td class="px-5 py-3 text-gray-600" style="position:sticky;left:0;background:#fff;z-index:1;">{{ $row[0] }}</td>
                                @foreach($plans as $plan)
                                    @php $val = $row[1]($plan); @endphp
                                    <td class="text-center px-4 py-3">
                                        @if($row[2] === 'bool')
                                            {!! $val ? $tableTick : $tableCross !!}
                                        @else
                                            <span class="{{ in_array($row[0], ['Duration', 'Price']) ? 'font-bold text-gray-900' : 'text-gray-800' }}">{!! $val !!}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        {{-- Upgrade row --}}
                        <tr class="bg-gray-50">
                            <td class="px-5 py-4" style="position:sticky;left:0;background:#f9fafb;z-index:1;"></td>
                            @foreach($plans as $plan)
                                <td class="text-center px-3 py-4">
                                    @if($activePlanId === $plan->id)
                                        <span class="text-xs font-semibold text-green-700">Current</span>
                                    @elseif($plan->price_inr > 0)
                                        <form method="POST" action="{{ route('membership.checkout') }}">
                                            @csrf
                                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                            <button type="submit" class="px-4 py-2 text-xs font-semibold text-white rounded-lg hover:opacity-90 transition-opacity" style="background-color: {{ $planColors[$plan->id] }};">UPGRADE</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">Free</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Need help paying? strip (phone / WhatsApp from Site Settings) ── --}}
        @if($phoneDigits || $waDigits)
            <div class="bg-(--color-primary-light) border border-(--color-primary)/20 rounded-xl p-5 sm:p-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Need help making a payment?</h3>
                    <p class="text-sm text-gray-600 mt-0.5">Our team is happy to help you choose or pay for a plan.</p>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-3 shrink-0">
                    @if($phoneDigits)
                        <a href="tel:{{ $sitePhone }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-(--color-primary) bg-white border border-(--color-primary)/30 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                            {{ $sitePhone }}
                        </a>
                    @endif
                    @if($waDigits)
                        <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-green-500 hover:bg-green-600 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.929-1.027zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
                            WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        @endif
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
