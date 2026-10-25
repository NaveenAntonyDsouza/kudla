@props(['id' => 'dob', 'label' => 'Date of Birth'])
{{--
    Date of birth as Day / Month / Year dropdowns (Oct 2026, like the big
    Indian portals). The phone date picker opened on the latest allowed date,
    and many members just tapped OK, so Kudla had men "exactly 18". Here
    nothing is pre-filled: all three must be picked.

    Lives inside a sign-up form whose Alpine scope has `dob` (Y-m-d) and
    `gender`, and usually `calculatedAge`; it reads and writes that `dob`, and
    submits it as date_of_birth. Too young for the chosen gender
    (App\Support\MinimumAge) → the year box says why and blocks the submit.
--}}
@php
    $thisYear = (int) now()->format('Y');
    $years = range($thisYear - \App\Support\MinimumAge::lowest(), $thisYear - 75);
    $months = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'];
    $minMale = \App\Support\MinimumAge::forGender('male');
    $minFemale = \App\Support\MinimumAge::forGender('female');
    $minAny = \App\Support\MinimumAge::lowest();
    $selectStyle = 'padding: 10px 4px 10px 10px;'; // no floating label: the empty choice already says Day / Month / Year
@endphp
<div x-data="{
    dd: '', mm: '', yy: '',
    setFrom(v) { const p = String(v || '').split('-'); if (p.length === 3) { this.yy = p[0]; this.mm = p[1]; this.dd = p[2]; } },
    days() { return this.mm ? new Date(this.yy ? +this.yy : 2000, +this.mm, 0).getDate() : 31; },
    composed() { if (this.dd && +this.dd > this.days()) this.dd = ''; return (this.dd && this.mm && this.yy) ? this.yy + '-' + this.mm + '-' + this.dd : ''; },
    minAge(g) { return g === 'male' ? {{ $minMale }} : (g === 'female' ? {{ $minFemale }} : {{ $minAny }}); },
    tooYoung(v, g) {
        if (!v) return false;
        const b = new Date(v + 'T00:00:00'), t = new Date();
        let age = t.getFullYear() - b.getFullYear();
        if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) age--;
        return age < this.minAge(g);
    },
    message(g) { return g === 'male' ? @js(\App\Support\MinimumAge::message('male')) : (g === 'female' ? @js(\App\Support\MinimumAge::message('female')) : @js(\App\Support\MinimumAge::message(null))); }
}" x-init="setFrom(dob)" x-effect="$refs.year.setCustomValidity(tooYoung(dob, gender) ? message(gender) : '')">
    <p style="font-size: 12px; font-weight: 500; color: #4b5563; margin: 0 0 6px;">{{ $label }} <span class="text-red-500">*</span></p>
    <div style="display: grid; grid-template-columns: 0.85fr 1fr 1.25fr; gap: 8px;">
        <div class="float-field">
            <select id="{{ $id }}_day" x-model="dd" @change="dob = composed()" required style="{{ $selectStyle }}" aria-label="Day of birth">
                <option value="">Day</option>
                <template x-for="n in days()" :key="n">
                    <option :value="String(n).padStart(2, '0')" x-text="n" :selected="String(n).padStart(2, '0') === dd"></option>
                </template>
            </select>
        </div>
        <div class="float-field">
            <select id="{{ $id }}_month" x-model="mm" @change="dob = composed()" required style="{{ $selectStyle }}" aria-label="Month of birth">
                <option value="">Month</option>
                @foreach($months as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="float-field">
            <select id="{{ $id }}_year" x-ref="year" x-model="yy" @change="dob = composed()" required style="{{ $selectStyle }}" aria-label="Year of birth">
                <option value="">Year</option>
                @foreach($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <input type="hidden" name="date_of_birth" :value="dob">
    <p x-show="tooYoung(dob, gender)" x-text="message(gender)" style="margin: 6px 0 0; font-size: 12px; color: #dc2626;" role="alert"></p>
    <p x-show="dob && !tooYoung(dob, gender) && typeof calculatedAge !== 'undefined' && calculatedAge" style="margin: 6px 0 0; font-size: 12px; color: #6b7280;">Age: <span x-text="typeof calculatedAge !== 'undefined' ? calculatedAge : ''"></span></p>
</div>
