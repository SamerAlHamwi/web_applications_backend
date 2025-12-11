// resources/views/emails/verify-email-text.blade.php

Hello {{ $userName }},

Thank you for registering with {{ config('app.name') }}!

Your email verification code is:

{{ $code }}

This code will expire in {{ $expiresInMinutes }} minutes.

Please enter this code in the verification page to complete your registration.

If you did not create an account, please ignore this email.

Regards,
{{ config('app.name') }}
