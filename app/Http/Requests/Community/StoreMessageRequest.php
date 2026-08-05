<?php

namespace App\Http\Requests\Community;

use App\Enums\MessageType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('channel'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(MessageType::class)],
            'body' => ['nullable', 'string', 'required_if:type,text'],
            'meta' => ['nullable', 'array'],
            'meta.title' => ['required_if:type,task_card', 'string', 'max:255'],
            'meta.accent_color' => ['nullable', 'string', 'max:32'],
            'meta.icon' => ['nullable', 'string', 'max:64'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
