<?php

namespace App\Models;

use Database\Factories\FlightExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'category', 'description', 'cost_cents', 'is_active'])]
class FlightExpense extends Model
{
    /** @use HasFactory<FlightExpenseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Get the airports where this expense applies.
     */
    public function airports(): BelongsToMany
    {
        return $this->belongsToMany(Airport::class, 'expense_airport', 'expense_id', 'airport_id');
    }

    /**
     * Scope a query to only include active expenses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to the expenses that apply to the given airport identifiers.
     *
     * An expense with no airports assigned is a fleet-wide cost and applies to
     * every flight, which is what the admin form promises.
     *
     * @param  array<int, string>  $identifiers
     */
    public function scopeForAirports($query, array $identifiers)
    {
        return $query->where(function ($query) use ($identifiers): void {
            $query->whereDoesntHave('airports')
                ->orWhereHas('airports', fn ($query) => $query->whereIn('identifier', $identifiers));
        });
    }

    /**
     * Get the formatted cost.
     */
    public function getCostDollarsAttribute(): float
    {
        return $this->cost_cents / 100;
    }
}
