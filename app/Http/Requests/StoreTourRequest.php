<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'origin_airport_id' => ['required', 'exists:airports,id'],
            'destination_airport_id' => ['required', 'exists:airports,id'],
            'total_distance_nm' => ['nullable', 'integer', 'min:1'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
