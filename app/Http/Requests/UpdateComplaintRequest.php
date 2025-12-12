<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'citizen';
    }

    public function rules(): array
    {
        return [
            'complaint_kind' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:20'],
            'location' => ['sometimes', 'string', 'max:500'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            'pdfs' => ['nullable', 'array', 'max:5'],
            'pdfs.*' => ['file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
