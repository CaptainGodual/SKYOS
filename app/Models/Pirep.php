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

    /**
     * The statuses that mean an administrator has accepted the report.
     *
     * @var array<int, string>
     */
    public const ACCEPTED_STATUSES = ['Approved', 'Paid'];

    /**
     * Every status a report can hold.
     *
     * @var array<int, string>
     */
    public const STATUSES = ['Pending review', 'Approved', 'Rejected', 'Paid'];

    protected $fillable = ['flight_id', 'pilot_id', 'pilot_name', 'flight_number', 'departure', 'arrival', 'block_minutes', 'landing_rate', 'payout_cents', 'remarks', 'status'];

    /**
     * Determine whether an administrator has accepted this report.
     */
    public function isAccepted(): bool
    {
        return in_array($this->status, self::ACCEPTED_STATUSES, true);
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }
}
