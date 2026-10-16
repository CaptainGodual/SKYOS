<?php

namespace App\Models;

use Database\Factories\TourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'origin_airport_id', 'destination_airport_id', 'total_distance_nm', 'estimated_duration_minutes', 'price_cents', 'is_active'])]
class Tour extends Model
{
    /** @use HasFactory<TourFactory> */
    use HasFactory;

    public function legs(): HasMany
    {
        return $this->hasMany(TourLeg::class, 'tour_id')->orderBy('leg_number', 'asc');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TourBooking::class, 'tour_id');
    }

    public function originAirport()
    {
        return $this->belongsTo(Airport::class, 'origin_airport_id');
    }

    public function destinationAirport()
    {
        return $this->belongsTo(Airport::class, 'destination_airport_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function label(): string
    {
        return $this->name;
    }
}
