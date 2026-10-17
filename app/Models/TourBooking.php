<?php

namespace App\Models;

use Database\Factories\TourBookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tour_id', 'pilot_id', 'status', 'booked_at', 'completed_at'])]
class TourBooking extends Model
{
    /** @use HasFactory<TourBookingFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'booked_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_BOOKED = 'booked';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The statuses that mean the pilot currently holds this tour.
     *
     * @var array<int, string>
     */
    public const ACTIVE_STATUSES = [self::STATUS_BOOKED, self::STATUS_IN_PROGRESS];

    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function pilot()
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }

    public function scopeBooked($query)
    {
        return $query->where('status', self::STATUS_BOOKED);
    }

    /**
     * Determine whether the pilot still holds this tour.
     */
    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /**
     * Scope a query to the bookings that still hold a tour for the pilot.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
