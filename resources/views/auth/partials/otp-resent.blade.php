{{-- Shown after "Resend OTP" (App\Support\OtpResendNotice) so members know a new code went out --}}
@if (session('otp_resent'))
    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg" role="status">
        <p class="text-sm text-green-700">{{ session('otp_resent') }}</p>
    </div>
@endif
