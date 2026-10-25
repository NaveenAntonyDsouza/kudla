@props(['name', 'label', 'options' => [], 'value' => null, 'maxlength' => 100, 'stacked' => false])
{{--
    Dropdown with an "Other (type it)" choice that opens a text box (Oct 2026:
    parent occupations, reference person's relationship). The hidden input
    carries the real value. A saved value that isn't in the list (typed before
    this was a dropdown) opens as "Other" with the text filled in, so nothing
    is lost on save. Renders two grid cells (display: contents); :stacked="true"
    for a one-column form that spaces its children with space-y-*.
--}}
@php
    $value = trim((string) ($value ?? ''));
    $listed = in_array($value, $options, true);
@endphp
<div class="{{ $stacked ? 'space-y-5' : 'contents' }}" x-data="{ choice: @js($value === '' ? '' : ($listed ? $value : '__other__')), other: @js($listed ? '' : $value) }">
    <div class="float-field">
        <select x-model="choice" id="{{ $name }}_choice">
            <option value="">Select</option>
            @foreach($options as $opt)
                <option value="{{ $opt }}" @selected($listed && $value === $opt)>{{ $opt }}</option>
            @endforeach
            <option value="__other__" @selected($value !== '' && ! $listed)>Other (type it)</option>
        </select>
        <label for="{{ $name }}_choice">{{ $label }}</label>
        <input type="hidden" name="{{ $name }}" :value="choice === '__other__' ? other : choice" value="{{ $value }}">
    </div>
    <div class="float-field" x-show="choice === '__other__'" x-cloak>
        <input type="text" id="{{ $name }}_other" x-model="other" maxlength="{{ $maxlength }}" placeholder=" ">
        <label for="{{ $name }}_other">{{ $label }} (please specify)</label>
    </div>
</div>
