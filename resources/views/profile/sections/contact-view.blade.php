@php $c = $profile->contactInfo; @endphp
@if(!$c)
    <p class="text-sm text-gray-400">No contact information added yet.</p>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
    <div><p class="text-xs text-gray-500">Primary Mobile</p><p class="text-sm font-medium text-gray-900">{{ $user->phone ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">WhatsApp Number</p><p class="text-sm font-medium text-gray-900">{{ $c->whatsapp_number ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Secondary Phone</p><p class="text-sm font-medium text-gray-900">{{ $c->secondary_phone ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Residential Phone</p><p class="text-sm font-medium text-gray-900">{{ $c->residential_phone_number ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Preferred Call Time</p><p class="text-sm font-medium text-gray-900">{{ $c->preferred_call_time ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Email</p><p class="text-sm font-medium text-gray-900">{{ $user->email ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Alternate Email</p><p class="text-sm font-medium text-gray-900">{{ $c->alternate_email ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Custodian / Contact Person</p><p class="text-sm font-medium text-gray-900">{{ $c->contact_person ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Relationship</p><p class="text-sm font-medium text-gray-900">{{ $c->contact_relationship ?? 'Not Mentioned' }}</p></div>
</div>

@if($c->communication_address)
    <div class="mt-4 pt-3 border-t border-gray-100">
        <p class="text-xs text-gray-500 mb-1">Communication Address</p>
        <p class="text-sm text-gray-700">{{ $c->communication_address }}{{ $c->pincode ? ', ' . $c->pincode : '' }}</p>
    </div>
@endif
@if($c->reference_name)
    <div class="mt-3 pt-3 border-t border-gray-100">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Reference Person</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-8 gap-y-2">
            <div><p class="text-xs text-gray-500">Name</p><p class="text-sm font-medium text-gray-900">{{ $c->reference_name }}</p></div>
            <div><p class="text-xs text-gray-500">Relationship</p><p class="text-sm font-medium text-gray-900">{{ $c->reference_relationship ?? '-' }}</p></div>
            <div><p class="text-xs text-gray-500">Mobile</p><p class="text-sm font-medium text-gray-900">{{ $c->reference_mobile ?? '-' }}</p></div>
        </div>
    </div>
@endif
@endif
