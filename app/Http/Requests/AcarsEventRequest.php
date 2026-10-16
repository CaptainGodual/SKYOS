<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AcarsEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', 'in:Flight took off,Flight canceled,Flight completed'],
            'altitude' => ['nullable', 'integer', 'min:0', 'max:60000'],
            'ground_speed' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
