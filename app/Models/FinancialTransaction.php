<?php

namespace App\Models;

use Database\Factories\FinancialTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'category', 'description', 'amount_cents', 'reference_type', 'reference_id'])]
class FinancialTransaction extends Model
{
    /** @use HasFactory<FinancialTransactionFactory> */
    use HasFactory;

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    protected $casts = [
        'amount_cents' => 'integer',
    ];

    /**
     * Scope a query to only include revenue transactions.
     */
    public function scopeRevenue($query)
    {
        return $query->where('type', self::TYPE_REVENUE);
    }

    /**
     * Scope a query to only include expense transactions.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    /**
     * Get the formatted amount.
     */
    public function getAmountDollarsAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    /**
     * Get the total revenue.
     */
    public static function totalRevenue(): float
    {
        return self::query()->where('type', self::TYPE_REVENUE)->sum('amount_cents') / 100;
    }

    /**
     * Get the total expenses.
     */
    public static function totalExpenses(): float
    {
        return abs(self::query()->where('type', self::TYPE_EXPENSE)->sum('amount_cents')) / 100;
    }

    /**
     * Get the net balance (revenue - expenses).
     */
    public static function netBalance(): float
    {
        return self::totalRevenue() - self::totalExpenses();
    }
}
