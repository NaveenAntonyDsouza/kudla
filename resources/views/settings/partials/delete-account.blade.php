{{--
    Delete Account, one step at a time (Oct 2026, modelled on Chavara
    Matrimony's flow): reason → how they met (married / fixed) → wedding
    date and optional partner ID or site → "Are you sure?". A member who
    met their partner here is logged for staff as a possible success story.
    Inline styles: the live CSS build is not rebuilt on deploy.
--}}
@php $daSite = config('app.name'); @endphp

<div class="delete-account" x-data="{
        step: 'reason', reason: '', via: '', weddingDate: '', partnerId: '', otherSite: '', otherText: '', asking: false,
        pick(r) { this.reason = r; this.step = r === 'Other Reasons' ? 'other' : 'via'; },
        pickVia(v) { this.via = v; if (v === 'other') { this.asking = true; } else { this.step = 'details'; } },
        back() { this.step = this.step === 'details' ? 'via' : 'reason'; },
        get ready() { return this.step === 'other' ? this.otherText.trim().length > 0 : this.weddingDate !== ''; },
    }">
    <form method="POST" action="{{ route('settings.delete') }}" x-ref="form">
        @csrf
        <input type="hidden" name="reason" :value="reason">
        <input type="hidden" name="met_via" :value="via">
        <input type="hidden" name="wedding_date" :value="weddingDate">
        <input type="hidden" name="partner_matri_id" :value="partnerId">
        <input type="hidden" name="other_site" :value="otherSite">
        <input type="hidden" name="other_reason" :value="otherText">

        <button type="button" x-show="step !== 'reason'" x-cloak @click="back()"
            style="display: inline-flex; align-items: center; gap: 4px; margin-bottom: 12px; font-size: 13px; color: #6b7280; background: none; border: 0; cursor: pointer; padding: 0;">&larr; Back</button>

        {{-- 1. Reason --}}
        <div x-show="step === 'reason'">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Delete Account</h2>
            <p class="text-sm text-gray-500 mb-4">Choose the reason for deleting your account</p>
            @foreach(['I am Married', 'My Marriage is Fixed', 'Other Reasons'] as $r)
                <button type="button" class="da-row" @click="pick(@js($r))">{{ $r }}<span aria-hidden="true">&rsaquo;</span></button>
            @endforeach
        </div>

        {{-- 2. How they met --}}
        <div x-show="step === 'via'" x-cloak>
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Congratulations!</h2>
            <p class="text-sm text-gray-500 mb-4">How did you find your partner?</p>
            <button type="button" class="da-row" @click="pickVia('this_site')">Through {{ $daSite }}<span aria-hidden="true">&rsaquo;</span></button>
            <button type="button" class="da-row" @click="pickVia('other_site')">Through another matrimony site or app<span aria-hidden="true">&rsaquo;</span></button>
            <button type="button" class="da-row" @click="pickVia('other')">Through other means<span aria-hidden="true">&rsaquo;</span></button>
        </div>

        {{-- 3a. Wedding date + partner / site --}}
        <div x-show="step === 'details'" x-cloak style="max-width: 460px;">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Wedding date</h2>
            <input type="date" x-model="weddingDate" aria-label="Wedding date" class="da-input">
            <div x-show="via === 'this_site'">
                <h3 class="text-sm font-semibold text-gray-900" style="margin: 20px 0 8px;">Your partner's Matri ID <span style="font-weight: 400; color: #6b7280;">(optional)</span></h3>
                <input type="text" x-model="partnerId" maxlength="20" placeholder="For example KM100123" class="da-input">
            </div>
            <div x-show="via === 'other_site'">
                <h3 class="text-sm font-semibold text-gray-900" style="margin: 20px 0 8px;">Matrimony site or app name <span style="font-weight: 400; color: #6b7280;">(optional)</span></h3>
                <input type="text" x-model="otherSite" maxlength="60" placeholder="Site or app name" class="da-input">
            </div>
            <button type="button" class="da-submit" :disabled="!ready" @click="asking = true">Delete Account</button>
        </div>

        {{-- 3b. Other reasons --}}
        <div x-show="step === 'other'" x-cloak style="max-width: 560px;">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Please tell us why you are leaving</h2>
            <textarea x-model="otherText" rows="4" maxlength="190" placeholder="Enter your reasons" class="da-input" style="resize: vertical;"></textarea>
            <p style="margin: 4px 2px 0; font-size: 12px; color: #6b7280;"><span x-text="otherText.length">0</span> / 190 characters</p>
            <button type="button" class="da-submit" :disabled="!ready" @click="asking = true">Delete Account</button>
        </div>

        {{-- "Are you sure?" --}}
        {{-- x-show drops an inline display, so the centring lives on the inner wrapper --}}
        <div x-show="asking" x-cloak @keydown.escape.window="asking = false" role="dialog" aria-modal="true" aria-label="Confirm deleting your account"
             style="position: fixed; inset: 0; z-index: 60; background: rgba(17, 24, 39, 0.5);">
          <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; padding: 16px; box-sizing: border-box;">
            <div @click.outside="asking = false" style="width: 100%; max-width: 420px; background: #fff; border-radius: 10px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,.2); text-align: center;">
                <p style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 8px;">Are you sure you want to delete your account?</p>
                <p style="font-size: 13px; color: #6b7280; margin: 0 0 20px;">Your profile will be hidden from everyone right away and you'll be signed out. Your details are permanently deleted after 90 days; to come back before then, just contact us.</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" @click="asking = false" style="min-width: 96px; padding: 9px 18px; border-radius: 6px; border: 1px solid var(--color-primary, #8B1D91); color: var(--color-primary, #8B1D91); background: #fff; font-weight: 600; font-size: 14px; cursor: pointer;">No</button>
                    <button type="button" @click="$refs.form.submit()" style="min-width: 96px; padding: 9px 18px; border-radius: 6px; border: 0; color: #fff; background: var(--color-primary, #8B1D91); font-weight: 600; font-size: 14px; cursor: pointer;">Yes, delete</button>
                </div>
            </div>
          </div>
        </div>
    </form>

    <style>
        .delete-account .da-row { display: flex; width: 100%; align-items: center; justify-content: space-between; padding: 16px 4px; font-size: 15px; color: #111827; background: none; border: 0; border-bottom: 1px solid #e5e7eb; cursor: pointer; text-align: left; }
        .delete-account .da-row:hover { color: var(--color-primary, #8B1D91); }
        .delete-account .da-row span { font-size: 22px; line-height: 1; color: #6b7280; }
        .delete-account .da-input { display: block; width: 100%; padding: 10px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 6px; }
        .delete-account .da-submit { margin-top: 24px; min-width: 200px; padding: 11px 24px; font-size: 14px; font-weight: 600; color: #fff; background: #dc2626; border: 0; border-radius: 6px; cursor: pointer; }
        .delete-account .da-submit:disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
    </style>
</div>
