<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'employee';
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:10'],
        ];
    }
}
