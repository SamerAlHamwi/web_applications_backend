<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only citizens can create complaints
        return auth()->check() && auth()->user()->role === 'citizen';
    }

    public function rules(): array
    {
        return [
            'entity_id' => ['required', 'integer', 'exists:entities,id'],
            'complaint_kind' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20'],
            'location' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:5120'], // 5MB max
            'pdfs' => ['nullable', 'array', 'max:5'],
            'pdfs.*' => ['file', 'mimes:pdf', 'max:10240'], // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'entity_id.required' => 'Please select an entity to submit your complaint to.',
            'entity_id.exists' => 'Selected entity does not exist.',
            'complaint_kind.required' => 'Please specify the kind of complaint.',
            'description.required' => 'Please provide a description of your complaint.',
            'description.min' => 'Description must be at least 20 characters.',
            'location.required' => 'Please provide your location.',
            'images.max' => 'You can upload a maximum of 5 images.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.max' => 'Each image must not exceed 5MB.',
            'pdfs.max' => 'You can upload a maximum of 5 PDF files.',
            'pdfs.*.mimes' => 'Each file must be a PDF.',
            'pdfs.*.max' => 'Each PDF must not exceed 10MB.',
        ];
    }
}
