@component('mail::message')
# Verify Your Email Address

Hi {{ $user->first_name }} {{ $user->last_name }},

Thank you for registering with SmartStay. Please click the button below to verify your email address:

@component('mail::button', ['url' => url('/verify-email/' . $user->verification_token)])
Verify Email Address
@endcomponent

If you did not create an account, no further action is required.

Thanks,<br>
{{ config('app.name') }}
@endcomponent 