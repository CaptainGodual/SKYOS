<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFlightRequest extends FormRequest
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
            'flight_number' => ['required', 'string', 'max:20', 'unique:flights'],
            'departure' => ['required', 'string', 'size:4', 'alpha'],
            'arrival' => ['required', 'string', 'size:4', 'alpha'],
            'route' => ['required', 'string', 'max:20'],
            'aircraft' => ['required', 'string', 'max:60'],
            'scheduled_at' => ['required', 'date'],
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'payout_cents' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:Open,Booked,Canceled,Completed'],
        ];
    }
}
