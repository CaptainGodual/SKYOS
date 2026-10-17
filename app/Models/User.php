<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'country', 'hub_id', 'password', 'is_admin', 'approval_status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    /**
     * Determine if the user is a crew administrator.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Determine if the user's account is approved for crew center access.
     */
    public function isApproved(): bool
    {
        return $this->isAdmin() || $this->approval_status === self::APPROVAL_APPROVED;
    }

    /**
     * Determine if the user's account is pending admin approval.
     */
    public function isPendingApproval(): bool
    {
        return ! $this->isAdmin() && $this->approval_status === self::APPROVAL_PENDING;
    }

    /**
     * Determine if the user's account was rejected by an administrator.
     */
    public function isRejected(): bool
    {
        return ! $this->isAdmin() && $this->approval_status === self::APPROVAL_REJECTED;
    }

    /**
     * Scope a query to only include pending approval accounts.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('is_admin', false)->where('approval_status', self::APPROVAL_PENDING);
    }

    /**
     * Scope a query to only include approved accounts.
     */
    public function scopeApproved($query)
    {
        return $query->where(function ($query) {
            $query->where('is_admin', true)
                ->orWhere('approval_status', self::APPROVAL_APPROVED);
        });
    }

    /**
     * Get the pilot's home hub airport.
     */
    public function homeHub()
    {
        return $this->belongsTo(Airport::class, 'hub_id');
    }

    /**
     * Get the pilot's API tokens.
     */
    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class, 'user_id');
    }

    /**
     * Get the pilot's financial transactions.
     */
    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
