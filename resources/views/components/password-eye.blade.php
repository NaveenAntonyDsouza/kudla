@props(['top' => '12px'])
{{--
    Eye button that shows / hides the password typed in the box next to it (like Chavara, owner 20 Nov 2026).
    The parent needs x-data="{ show: false }", position: relative, and the input :type="show ? 'text' : 'password'"
    with room on the right (padding-right: 2.75rem). Without JavaScript the box simply stays a password box.
--}}
<button type="button" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'" aria-label="Show password"
        class="text-gray-400 hover:text-gray-600" data-password-eye
        style="position:absolute;right:12px;top:{{ $top }};{{ $top === '50%' ? 'transform:translateY(-50%);' : '' }}line-height:0;background:none;border:0;padding:0;cursor:pointer">
    <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
    </svg>
    <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
</button>
