@component('mail::message')
# Hello {{ $user->first_name }},

We received a request to reset your password. If you made this request, please click the button below to reset your
password.

@component('mail::button', ['url' => url('reset-password/' . $token . '/' . $user->email)])
Reset Password
@endcomponent

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
@endcomponent