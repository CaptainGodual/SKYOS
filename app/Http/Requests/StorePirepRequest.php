<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePirepRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'flight_number' => ['required', 'string', 'max:20'],
            'departure' => ['required', 'string', 'size:4', 'alpha'],
            'arrival' => ['required', 'string', 'size:4', 'alpha'],
            'block_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'landing_rate' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
