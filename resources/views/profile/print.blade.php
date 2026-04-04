<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $profile->matri_id }} — {{ $profile->full_name }} | {{ $siteName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; color: #333; line-height: 1.5; padding: 20px; }

        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #8B1D91; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #8B1D91; }
        .header .matri-id { font-size: 14px; color: #666; }

        .profile-top { display: flex; gap: 24px; margin-bottom: 24px; }
        .photo-container { width: 160px; height: 200px; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; flex-shrink: 0; }
        .photo-container img { width: 100%; height: 100%; object-fit: cover; }
        .photo-placeholder { width: 100%; height: 100%; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px; }
        .quick-info { flex: 1; }
        .quick-info h2 { font-size: 18px; color: #1a1a1a; margin-bottom: 4px; }
        .quick-info .id { font-size: 13px; color: #8B1D91; font-weight: 600; margin-bottom: 12px; }
        .quick-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; }
        .info-item { display: flex; gap: 6px; }
        .info-item .label { color: #888; min-width: 100px; }
        .info-item .value { font-weight: 500; color: #333; }

        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: 700; color: #8B1D91; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; }
        .info-row { display: flex; gap: 6px; padding: 3px 0; }
        .info-row .label { color: #888; min-width: 140px; font-size: 12px; }
        .info-row .value { font-size: 12px; font-weight: 500; }

        .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #ddd; text-align: center; font-size: 11px; color: #999; }

        .no-print { margin-bottom: 20px; text-align: center; }
        .no-print button { background: #8B1D91; color: white; border: none; padding: 10px 30px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .no-print button:hover { background: #6B1571; }
        .no-print a { color: #8B1D91; text-decoration: none; margin-left: 16px; font-size: 14px; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            @page { margin: 15mm; }
        }
    </style>
</head>
<body>

{{-- Print / Back buttons --}}
<div class="no-print">
    <button onclick="window.print()">Print Profile</button>
    <a href="{{ url()->previous() }}">Back to Profile</a>
</div>

{{-- Header --}}
<div class="header">
    <h1>{{ $siteName }}</h1>
    <span class="matri-id">Generated on {{ now()->format('d M Y') }}</span>
</div>

{{-- Profile Top: Photo + Quick Info --}}
<div class="profile-top">
    <div class="photo-container">
        @if($profile->primaryPhoto)
            <img src="{{ $profile->primaryPhoto->full_url }}" alt="{{ $profile->full_name }}">
        @else
            <div class="photo-placeholder">No Photo</div>
        @endif
    </div>
    <div class="quick-info">
        <h2>{{ $profile->full_name }}</h2>
        <p class="id">{{ $profile->matri_id }}</p>
        <div class="quick-info-grid">
            @if($profile->age)<div class="info-item"><span class="label">Age:</span><span class="value">{{ $profile->age }} Yrs</span></div>@endif
            @if($profile->height)<div class="info-item"><span class="label">Height:</span><span class="value">{{ $profile->height }}</span></div>@endif
            @if($profile->date_of_birth)<div class="info-item"><span class="label">Date of Birth:</span><span class="value">{{ $profile->date_of_birth->format('d M Y') }}</span></div>@endif
            @if($profile->marital_status)<div class="info-item"><span class="label">Marital Status:</span><span class="value">{{ $profile->marital_status }}</span></div>@endif
            @if($profile->religiousInfo?->religion)<div class="info-item"><span class="label">Religion:</span><span class="value">{{ $profile->religiousInfo->religion }}</span></div>@endif
            @if($profile->religiousInfo?->denomination ?? $profile->religiousInfo?->caste)<div class="info-item"><span class="label">Denomination:</span><span class="value">{{ $profile->religiousInfo->denomination ?? $profile->religiousInfo->caste }}</span></div>@endif
            @if($profile->mother_tongue)<div class="info-item"><span class="label">Mother Tongue:</span><span class="value">{{ $profile->mother_tongue }}</span></div>@endif
            @if($profile->complexion)<div class="info-item"><span class="label">Complexion:</span><span class="value">{{ $profile->complexion }}</span></div>@endif
        </div>
    </div>
</div>

{{-- About Me --}}
@if($profile->about_me)
<div class="section">
    <div class="section-title">About Me</div>
    <p style="font-size: 12px;">{{ $profile->about_me }}</p>
</div>
@endif

{{-- Religious Information --}}
@if($profile->religiousInfo)
<div class="section">
    <div class="section-title">Religious Information</div>
    <div class="info-grid">
        @foreach([
            'Religion' => $profile->religiousInfo->religion,
            'Denomination' => $profile->religiousInfo->denomination,
            'Caste' => $profile->religiousInfo->caste,
            'Sub Caste' => $profile->religiousInfo->sub_caste,
            'Diocese' => $profile->religiousInfo->diocese_name ?? $profile->religiousInfo->diocese,
            'Parish' => $profile->religiousInfo->parish_name_place,
            'Gotra' => $profile->religiousInfo->gotra,
            'Nakshatra' => $profile->religiousInfo->nakshatra,
            'Rashi' => $profile->religiousInfo->rashi,
        ] as $label => $value)
            @if($value)
                <div class="info-row"><span class="label">{{ $label }}:</span><span class="value">{{ $value }}</span></div>
            @endif
        @endforeach
    </div>
</div>
@endif

{{-- Education & Profession --}}
@if($profile->educationDetail)
<div class="section">
    <div class="section-title">Education & Profession</div>
    <div class="info-grid">
        @foreach([
            'Education' => $profile->educationDetail->highest_education,
            'Education Detail' => $profile->educationDetail->education_detail,
            'College' => $profile->educationDetail->college_name,
            'Occupation' => $profile->educationDetail->occupation,
            'Occupation Detail' => $profile->educationDetail->occupation_detail,
            'Employer' => $profile->educationDetail->employer_name,
            'Annual Income' => $profile->educationDetail->annual_income,
            'Working Country' => $profile->educationDetail->working_country,
            'Working State' => $profile->educationDetail->working_state,
        ] as $label => $value)
            @if($value)
                <div class="info-row"><span class="label">{{ $label }}:</span><span class="value">{{ $value }}</span></div>
            @endif
        @endforeach
    </div>
</div>
@endif

{{-- Family Details --}}
@if($profile->familyDetail)
<div class="section">
    <div class="section-title">Family Details</div>
    <div class="info-grid">
        @foreach([
            'Father\'s Name' => $profile->familyDetail->father_name,
            'Father\'s Occupation' => $profile->familyDetail->father_occupation,
            'Mother\'s Name' => $profile->familyDetail->mother_name,
            'Mother\'s Occupation' => $profile->familyDetail->mother_occupation,
            'Family Type' => $profile->familyDetail->family_type,
            'Family Status' => $profile->familyDetail->family_status,
            'Family Values' => $profile->familyDetail->family_values,
            'Brothers' => $profile->familyDetail->num_brothers ? $profile->familyDetail->num_brothers . ' (' . ($profile->familyDetail->brothers_married ?? 0) . ' married)' : null,
            'Sisters' => $profile->familyDetail->num_sisters ? $profile->familyDetail->num_sisters . ' (' . ($profile->familyDetail->sisters_married ?? 0) . ' married)' : null,
            'Family Living In' => $profile->familyDetail->family_living_in,
        ] as $label => $value)
            @if($value)
                <div class="info-row"><span class="label">{{ $label }}:</span><span class="value">{{ $value }}</span></div>
            @endif
        @endforeach
    </div>
</div>
@endif

{{-- Location --}}
@if($profile->locationInfo)
<div class="section">
    <div class="section-title">Location</div>
    <div class="info-grid">
        @foreach([
            'Residing Country' => $profile->locationInfo->residing_country,
            'Native Country' => $profile->locationInfo->native_country,
            'Native State' => $profile->locationInfo->native_state,
            'Native District' => $profile->locationInfo->native_district,
            'Residency Status' => $profile->locationInfo->residency_status,
        ] as $label => $value)
            @if($value)
                <div class="info-row"><span class="label">{{ $label }}:</span><span class="value">{{ $value }}</span></div>
            @endif
        @endforeach
    </div>
</div>
@endif

{{-- Contact (only for own profile) --}}
@if($isOwn && $profile->contactInfo)
<div class="section">
    <div class="section-title">Contact Information</div>
    <div class="info-grid">
        @foreach([
            'Contact Person' => $profile->contactInfo->contact_person,
            'Relationship' => $profile->contactInfo->contact_relationship,
            'Phone' => $profile->contactInfo->primary_phone,
            'WhatsApp' => $profile->contactInfo->whatsapp_number,
            'Email' => $profile->contactInfo->email,
        ] as $label => $value)
            @if($value)
                <div class="info-row"><span class="label">{{ $label }}:</span><span class="value">{{ $value }}</span></div>
            @endif
        @endforeach
    </div>
</div>
@endif

{{-- Lifestyle --}}
@if($profile->lifestyleInfo)
<div class="section">
    <div class="section-title">Lifestyle</div>
    <div class="info-grid">
        @foreach([
            'Diet' => $profile->lifestyleInfo->diet,
            'Smoking' => $profile->lifestyleInfo->smoking,
            'Drinking' => $profile->lifestyleInfo->drinking,
            'Hobbies' => is_array($profile->lifestyleInfo->hobbies) ? implode(', ', $profile->lifestyleInfo->hobbies) : $profile->lifestyleInfo->hobbies,
        ] as $label => $value)
            @if($value)
                <div class="info-row"><span class="label">{{ $label }}:</span><span class="value">{{ $value }}</span></div>
            @endif
        @endforeach
    </div>
</div>
@endif

{{-- Partner Preferences --}}
@if($profile->partnerPreference)
@php $pp = $profile->partnerPreference; @endphp
<div class="section">
    <div class="section-title">Partner Preferences</div>
    <div class="info-grid">
        @if($pp->age_from && $pp->age_to)<div class="info-row"><span class="label">Age:</span><span class="value">{{ $pp->age_from }} - {{ $pp->age_to }} Yrs</span></div>@endif
        @if($pp->height_from_cm && $pp->height_to_cm)<div class="info-row"><span class="label">Height:</span><span class="value">{{ $pp->height_from_cm }} - {{ $pp->height_to_cm }}</span></div>@endif
        @if($pp->religions)<div class="info-row"><span class="label">Religion:</span><span class="value">{{ implode(', ', $pp->religions) }}</span></div>@endif
        @if($pp->denomination)<div class="info-row"><span class="label">Denomination:</span><span class="value">{{ implode(', ', $pp->denomination) }}</span></div>@endif
        @if($pp->caste)<div class="info-row"><span class="label">Caste:</span><span class="value">{{ implode(', ', $pp->caste) }}</span></div>@endif
        @if($pp->mother_tongues)<div class="info-row"><span class="label">Mother Tongue:</span><span class="value">{{ implode(', ', $pp->mother_tongues) }}</span></div>@endif
        @if($pp->education_levels)<div class="info-row"><span class="label">Education:</span><span class="value">{{ implode(', ', $pp->education_levels) }}</span></div>@endif
        @if($pp->occupations)<div class="info-row"><span class="label">Occupation:</span><span class="value">{{ implode(', ', $pp->occupations) }}</span></div>@endif
        @if($pp->marital_status)<div class="info-row"><span class="label">Marital Status:</span><span class="value">{{ implode(', ', $pp->marital_status) }}</span></div>@endif
        @if($pp->diet)<div class="info-row"><span class="label">Diet:</span><span class="value">{{ implode(', ', $pp->diet) }}</span></div>@endif
    </div>
</div>
@endif

{{-- Footer --}}
<div class="footer">
    <p>{{ $siteName }} | {{ $profile->matri_id }} | Printed on {{ now()->format('d M Y, h:i A') }}</p>
    <p>This profile is confidential. Please do not share without permission.</p>
</div>

</body>
</html>
