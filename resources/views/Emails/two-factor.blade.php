@component('mail::message')
# Two-Factor Authentication Code

Hi {{ $user->first_name }} {{ $user->last_name }},

Your two-factor authentication code is:

# {{ $user->two_factor_code }}

This code will expire in 2 minutes. Do not share this code with anyone.

If you did not attempt to log in, please secure your account immediately by changing your password.

Thanks,<br>
{{ config('app.name') }}
@endcomponent 