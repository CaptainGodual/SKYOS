<?php

namespace App\Models;

use Database\Factories\AirportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['identifier', 'iata_code', 'icao_code', 'name', 'type', 'latitude', 'longitude', 'elevation', 'country_code', 'region_code', 'municipality', 'time_zone', 'is_hub'])]
class Airport extends Model
{
    /** @use HasFactory<AirportFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_hub' => 'boolean'];
    }

    /**
     * Get the pilots whose home hub is this airport.
     */
    public function pilots(): HasMany
    {
        return $this->hasMany(User::class, 'hub_id');
    }

    /**
     * Scope a query to only include airports marked as hubs.
     */
    public function scopeHub($query)
    {
        return $query->where('is_hub', true);
    }

    /**
     * Scope a query to only include airports of a given type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include airports in a given country.
     */
    public function scopeCountry($query, string $code)
    {
        return $query->where('country_code', $code);
    }

    /**
     * Get the display label for this airport (IATA or ICAO fallback).
     */
    public function label(): string
    {
        return $this->iata_code ?: $this->identifier;
    }
}
