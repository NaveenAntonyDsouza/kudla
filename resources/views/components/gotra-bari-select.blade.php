{{--
    Gotra / Bari picker (Oct 2026). One field for both: Brahmins pick their
    gotra, Tulu families their bari (the family line from the mother's side).
    Searchable, grouped when the list has bari groups; "Other" opens a box to
    type one that isn't listed. Submits a single `gotra` value, as before.

    Labelled by caste ("Bari" / "Gotra" / "Gotra / Bari"), with that caste's
    groups first in the list (GotraBari::casteHints). Follows a caste
    <select name="caste"> changed on the same page.
--}}
@props(['value' => null, 'name' => 'gotra', 'caste' => null])

@php
    $gbValue = (string) ($value ?? '');
    $gbCaste = (string) ($caste ?? '');
    $gbHints = \App\Support\GotraBari::casteHints($gbCaste);
    $gbLabel = \App\Support\GotraBari::label($gbCaste);
    $gbLabelHtml = '<span x-text="gbLabel">'.e($gbLabel).'</span>';
@endphp

<div x-data="{
        gbChoice: {{ Js::from($gbValue) }},
        gbTyped: '',
        gbCaste: {{ Js::from($gbCaste) }},
        gbHints: {{ Js::from((object) $gbHints) }},
        get gbHint() { return this.gbHints[(this.gbCaste || '').trim().toLowerCase().replace(/\s+/g, ' ')] || {}; },
        get gbLabel() { return this.gbHint.label || {{ Js::from(\App\Support\GotraBari::LABEL) }}; },
        get gbHelp() { return this.gbHint.help || {{ Js::from(\App\Support\GotraBari::help(null)) }}; },
        get gbFirst() { return this.gbHint.first || []; },
    }" @change.window="if ($event.target.name === 'caste') gbCaste = $event.target.value">
    <x-searchable-select name="gotra_choice" id="gotra_choice" :label="$gbLabelHtml"
        :options="\App\Support\GotraBari::options()" :selected="$gbValue" x-model="gbChoice" first-groups="gbFirst" />
    <div class="float-field" x-show="gbChoice === 'Other'" x-cloak style="margin-top: 12px;">
        <input type="text" id="gotra_other" x-model="gbTyped" maxlength="50" placeholder=" ">
        <label for="gotra_other" x-text="'Type your ' + gbLabel.toLowerCase()">Type your {{ mb_strtolower($gbLabel) }}</label>
    </div>
    {{-- The submitted value: the choice, or what they typed after "Other" --}}
    <input type="hidden" name="{{ $name }}" value="{{ $gbValue }}" :value="gbChoice === 'Other' ? (gbTyped.trim() || 'Other') : gbChoice">
    <p style="margin: 6px 2px 0; font-size: 12px; line-height: 1.4; color: #6b7280;" x-text="gbHelp">{{ \App\Support\GotraBari::help($gbCaste) }}</p>
</div>
