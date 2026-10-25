@props(['name', 'label', 'value' => 0, 'max' => 10])
{{-- 0..max dropdown for counts (siblings, children): no phone keyboard, no negative or silly numbers. A saved number above max stays selectable. --}}
@php $value = (int) ($value ?? 0); $top = max((int) $max, $value); @endphp
<div class="float-field">
    <select name="{{ $name }}" id="{{ $name }}">
        @for($i = 0; $i <= $top; $i++)
            <option value="{{ $i }}" @selected($value === $i)>{{ $i }}</option>
        @endfor
    </select>
    <label for="{{ $name }}">{{ $label }}</label>
</div>
