{{--
    Gotra / Bari picker (Oct 2026). One field for both: Brahmins pick their
    gotra, Tulu families their bari (the family line from the mother's side).
    Searchable, grouped when the list has bari groups; "Other" opens a box to
    type one that isn't listed. Submits a single `gotra` value, as before.
--}}
@props(['value' => null, 'name' => 'gotra'])

@php
    $gbValue = (string) ($value ?? '');
@endphp

<div x-data="{ gbChoice: {{ Js::from($gbValue) }}, gbTyped: '' }">
    <x-searchable-select name="gotra_choice" id="gotra_choice" :label="\App\Support\GotraBari::LABEL"
        :options="\App\Support\GotraBari::options()" :selected="$gbValue" x-model="gbChoice" />
    <div class="float-field" x-show="gbChoice === 'Other'" x-cloak style="margin-top: 12px;">
        <input type="text" id="gotra_other" x-model="gbTyped" maxlength="50" placeholder=" ">
        <label for="gotra_other">Type your gotra / bari</label>
    </div>
    {{-- The submitted value: the choice, or what they typed after "Other" --}}
    <input type="hidden" name="{{ $name }}" value="{{ $gbValue }}" :value="gbChoice === 'Other' ? (gbTyped.trim() || 'Other') : gbChoice">
    <p style="margin: 6px 2px 0; font-size: 12px; line-height: 1.4; color: #6b7280;">
        Bari is your family line from your mother's side (for example Bangera or Salian). Brahmins: choose your gotra.
    </p>
</div>
