<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'flight_number' => ['sometimes', 'required', 'string', 'max:20', 'unique:flights,flight_number,'.$this->route('flight')->id],
            'departure' => ['sometimes', 'required', 'string', 'size:4', 'alpha'],
            'arrival' => ['sometimes', 'required', 'string', 'size:4', 'alpha'],
            'aircraft' => ['sometimes', 'required', 'string', 'max:60'],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'estimated_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:1440'],
            'payout_cents' => ['sometimes', 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', 'string', 'in:Open,Booked,Canceled,Completed'],
        ];
    }
}
