<?php

namespace App\Http\Requests;

use App\Models\ProjectSubmission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [ProjectSubmission::class, $this->route('lessonProject')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'live_url' => ['nullable', 'url', 'max:2048'],
            'screenshots' => ['nullable', 'array', 'max:10'],
            'screenshots.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
