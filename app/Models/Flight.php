<?php

namespace App\Models;

use Database\Factories\FlightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flight extends Model
{
    /** @use HasFactory<FlightFactory> */
    use HasFactory;

    protected $fillable = ['flight_number', 'departure', 'arrival', 'route', 'aircraft', 'scheduled_at', 'estimated_minutes', 'payout_cents', 'status', 'pilot_id', 'pilot_name', 'acars_status', 'altitude', 'ground_speed', 'departed_at', 'arrived_at'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'departed_at' => 'datetime', 'arrived_at' => 'datetime'];
    }

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }

    public function pireps(): HasMany
    {
        return $this->hasMany(Pirep::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }
}
