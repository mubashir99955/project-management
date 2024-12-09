@component('mail::message')
# Hello, {{ $user->first_name }}!

Please use the OTP below to verify your account or complete your login process. This OTP is valid for a limited time.

@component('mail::panel')
**{{ $otp }}**
@endcomponent

If you did not request this OTP, please ignore this email.

Thank you for choosing {{ config('app.name') }}!

{{ config('app.name') }}
@endcomponent
