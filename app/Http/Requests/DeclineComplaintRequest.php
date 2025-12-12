<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeclineComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'employee';
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:20'],
        ];
    }
}
