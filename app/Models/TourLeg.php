<?php

namespace App\Models;

use Database\Factories\TourLegFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tour_id', 'leg_number', 'departure_airport_id', 'arrival_airport_id', 'distance_nm', 'estimated_minutes'])]
class TourLeg extends Model
{
    /** @use HasFactory<TourLegFactory> */
    use HasFactory;

    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function departureAirport()
    {
        return $this->belongsTo(Airport::class, 'departure_airport_id');
    }

    public function arrivalAirport()
    {
        return $this->belongsTo(Airport::class, 'arrival_airport_id');
    }

    public function label(): string
    {
        return sprintf(
            'Leg %d: %s → %s',
            $this->leg_number,
            $this->departureAirport?->identifier ?? $this->departure_airport_id,
            $this->arrivalAirport?->identifier ?? $this->arrival_airport_id
        );
    }
}
