<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'cost_cents' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'airport_ids' => ['nullable', 'array'],
            'airport_ids.*' => ['exists:airports,id'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cost_cents.min' => 'Cost cannot be negative.',
            'airport_ids.*.exists' => 'One or more selected airports do not exist.',
        ];
    }
}
