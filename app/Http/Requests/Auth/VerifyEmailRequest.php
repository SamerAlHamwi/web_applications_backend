<?php
// app/Http/Requests/Auth/VerifyEmailRequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.size' => 'Verification code must be exactly 6 digits.',
            'code.regex' => 'Verification code must contain only numbers.',
        ];
    }
}
