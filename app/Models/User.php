<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
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
     * How this crew member is introduced to other pilots.
     *
     * Surnames are never published in the crew centre: a pilot is shown as
     * their first name and the initial of their last name ("Piper A."), so a
     * roster or a message cannot be mined for the full names of the crew.
     */
    public function displayName(): string
    {
        return self::shortName($this->name);
    }

    /**
     * The single character used in this pilot's avatar.
     */
    public function initial(): string
    {
        $name = trim((string) $this->name);

        return $name === '' ? '' : mb_strtoupper(mb_substr($name, 0, 1));
    }

    /**
     * Reduce any name to a first name plus the initial of the last one.
     *
     * The name column is a single free-text field, so the awkward shapes are
     * all handled in one place rather than in each view:
     *
     *   "Piper Alpha"           -> "Piper A."
     *   "Piper van der Berg"    -> "Piper B."
     *   "Piper Alpha-Bravo"     -> "Piper A."
     *   "Madonna"               -> "Madonna"
     *   ""                      -> "Crew member"
     *
     * It is also applied to the names denormalised onto flights, reports and
     * messages, which were copied from the same field.
     */
    public static function shortName(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return 'Crew member';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        $surname = (string) end($parts);

        // Take the first *letter* of the surname, so "A." or "-Bravo" cannot
        // produce a stray full stop in the middle of the initial.
        preg_match('/\p{L}/u', $surname, $matches);

        $initial = $matches[0] ?? mb_substr($surname, 0, 1);

        return $parts[0].' '.mb_strtoupper($initial).'.';
    }

    /**
     * The crew members a pilot may address from the messaging forms.
     *
     * Pending and rejected applicants are left out: nothing about them should
     * be visible to the rest of the crew.
     *
     * @return Collection<int, self>
     */
    public static function messagingDirectory(int $exceptUserId): Collection
    {
        return static::query()
            ->approved()
            ->whereKeyNot($exceptUserId)
            ->with('homeHub')
            ->get(['id', 'name', 'hub_id'])
            ->sortBy(fn (self $pilot): string => $pilot->displayName())
            ->values();
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
     * Get the flight reports this pilot has filed.
     */
    public function pireps()
    {
        return $this->hasMany(Pirep::class, 'pilot_id');
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
