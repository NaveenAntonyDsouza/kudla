{{-- One pop-up a day at most (19 Nov 2026): photo, waiting replies, plans or WhatsApp Channel.
     Every rule is in App\Support\DailyPopup::choose(). Inline styles: no CSS build. --}}
@php $__popup = \App\Support\DailyPopup::choose(); @endphp
@if($__popup)
    @php
        $__user = auth()->user();
        $__site = \App\Models\SiteSetting::getValue('site_name', config('app.name'));
        $__btn = 'display:block;margin-top:20px;padding:11px 16px;border-radius:9999px;background:var(--color-primary,#7B1FA2);color:#fff;font-size:15px;font-weight:600;text-decoration:none';
        $__text = 'margin:0;font-size:14px;line-height:1.5;color:#4b5563';
    @endphp
    <div x-data="{ open: true }" x-show="open" x-cloak @keydown.escape.window="open = false"
         role="dialog" aria-modal="true" aria-labelledby="daily-popup-title"
         data-daily-popup="{{ $__popup }}" @if($__popup === 'photo') data-photo-popup @endif
         style="position:fixed;inset:0;z-index:70">
        {{-- x-show sets and clears display on the outer box, so the centring lives on this one --}}
        <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;padding:16px;box-sizing:border-box;background:rgba(17,24,39,.55)">
        <div @click.outside="open = false"
             style="position:relative;width:100%;max-width:{{ $__popup === 'plans' ? '420' : '380' }}px;max-height:100%;overflow-y:auto;background:#fff;border-radius:14px;padding:26px 22px 18px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.25);box-sizing:border-box">
            {{-- close (same as "Not now"): owner, 19 Nov 2026 --}}
            <button type="button" @click="open = false" aria-label="Close" data-popup-close
                    style="position:absolute;top:8px;right:8px;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:0;border-radius:9999px;background:transparent;color:#6b7280;cursor:pointer">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <span style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:9999px;background:{{ $__popup === 'channel' ? '#DCFCE7' : 'var(--color-primary-light,#F3E8F7)' }};color:{{ $__popup === 'channel' ? '#15803D' : 'var(--color-primary,#7B1FA2)' }}">
                @switch($__popup)
                    @case('photo')
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        @break
                    @case('waiting')
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        @break
                    @case('plans')
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        @break
                    @default
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.8 14.03c-.25.69-1.45 1.32-2 1.4-.51.08-1.16.11-1.87-.12-.43-.14-.98-.32-1.69-.62-2.97-1.28-4.91-4.27-5.06-4.47-.15-.2-1.21-1.61-1.21-3.07 0-1.46.77-2.18 1.04-2.48.27-.3.6-.37.8-.37h.57c.18 0 .43-.07.67.51.25.6.84 2.06.92 2.21.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.31.39-.45.52-.15.15-.3.31-.13.61.17.3.77 1.27 1.65 2.06 1.13 1.01 2.09 1.32 2.39 1.47.3.15.47.12.65-.07.18-.2.75-.87.95-1.17.2-.3.4-.25.67-.15.27.1 1.73.82 2.03.97.3.15.5.22.57.35.08.12.08.72-.17 1.41z"/></svg>
                @endswitch
            </span>

            @switch($__popup)
                @case('photo')
                    <h2 id="daily-popup-title" style="margin:14px 0 6px;font-size:18px;font-weight:700;color:#111827">Add your photo</h2>
                    <p style="{{ $__text }}">A photo helps families notice your profile. You choose who can see it.</p>
                    <a href="{{ route('photos.manage') }}" style="{{ $__btn }}">Add photo</a>
                    @break

                @case('waiting')
                    @php $__n = \App\Support\DailyPopup::waitingCount($__user); @endphp
                    <h2 id="daily-popup-title" style="margin:14px 0 6px;font-size:18px;font-weight:700;color:#111827">{{ $__n === 1 ? '1 member is' : $__n.' members are' }} waiting for your reply</h2>
                    <p style="{{ $__text }}">They sent you an interest. Accept or decline in one tap. A quick answer is kind, even when it is a no.</p>
                    <a href="{{ route('interests.inbox', ['tab' => 'received']) }}" style="{{ $__btn }}">Reply now</a>
                    @break

                @case('plans')
                    @php
                        $__plans = \App\Support\DailyPopup::paidPlans();
                        $__offerOn = \App\Models\SiteSetting::getValue('offer_banner_enabled', '0') === '1';
                        $__offer = $__offerOn ? trim((string) \App\Models\SiteSetting::getValue('offer_banner_text', '')) : '';
                    @endphp
                    <h2 id="daily-popup-title" style="margin:14px 0 6px;font-size:18px;font-weight:700;color:#111827">Talk directly to the families you like</h2>
                    <p style="{{ $__text }}">With a plan you can see phone and WhatsApp numbers, chat with members and send more interests.</p>
                    <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;text-align:left">
                        @foreach($__plans as $__plan)
                            {{-- a tap on a plan opens that plan's card on the plans page --}}
                            <a href="{{ route('membership.index', ['from' => 'popup']) }}#plan-{{ $__plan->id }}" data-popup-plan="{{ $__plan->id }}"
                               style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid {{ $__plan->is_popular ? 'var(--color-primary,#7B1FA2)' : '#e5e7eb' }};border-radius:10px;text-decoration:none;color:inherit">
                                <span style="font-size:14px;color:#111827">
                                    <strong>{{ $__plan->plan_name }}</strong>
                                    <span style="color:#6b7280">· {{ $__plan->duration_months }} {{ $__plan->duration_months === 1 ? 'month' : 'months' }}</span>
                                    @if($__plan->is_popular)<span style="display:inline-block;margin-left:4px;padding:1px 6px;border-radius:9999px;background:var(--color-primary-light,#F3E8F7);color:var(--color-primary,#7B1FA2);font-size:11px;font-weight:600">Popular</span>@endif
                                </span>
                                <span style="white-space:nowrap;font-size:14px;text-align:right">
                                    @if($__plan->strike_price_inr && $__plan->strike_price_inr > $__plan->price_inr)<span style="color:#9ca3af;text-decoration:line-through;margin-right:4px">₹{{ number_format($__plan->strike_price_inr) }}</span>@endif
                                    <strong style="color:#111827">₹{{ number_format($__plan->price_inr) }}</strong>
                                    @if($__plan->duration_months > 1)
                                        <span style="display:block;font-size:12px;color:#6b7280">₹{{ number_format(round($__plan->price_inr / $__plan->duration_months)) }} a month</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                    @if($__offer !== '')
                        <p style="margin:12px 0 0;padding:8px 10px;border-radius:8px;background:#FFF7ED;color:#9A3412;font-size:13px;line-height:1.45">{{ $__offer }}</p>
                    @endif
                    <a href="{{ route('membership.index', ['from' => 'popup']) }}" style="{{ $__btn }}">See plans</a>
                    @break

                @default
                    <h2 id="daily-popup-title" style="margin:14px 0 6px;font-size:18px;font-weight:700;color:#111827">Follow {{ $__site }} on WhatsApp</h2>
                    <p style="{{ $__text }}">New profiles, tips and offers in our WhatsApp Channel. Your number stays private: other followers can't see it.</p>
                    <a href="{{ \App\Support\WhatsApp::channelUrl() }}" target="_blank" rel="noopener" @click="open = false"
                       style="{{ $__btn }};background:#16A34A">Follow the Channel</a>
            @endswitch

            <button type="button" @click="open = false"
                    style="display:block;width:100%;margin-top:8px;padding:9px;border:0;background:transparent;color:#6b7280;font-size:14px;cursor:pointer">Not now</button>
        </div>
        </div>
    </div>
@endif
