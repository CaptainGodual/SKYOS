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

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
