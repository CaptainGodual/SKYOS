<?php

namespace App\Models;

use Database\Factories\PirepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pirep extends Model
{
    /** @use HasFactory<PirepFactory> */
    use HasFactory;

    protected $fillable = ['flight_id', 'pilot_name', 'flight_number', 'departure', 'arrival', 'block_minutes', 'landing_rate', 'payout_cents', 'remarks', 'status'];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }
}
