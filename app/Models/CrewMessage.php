<?php

namespace App\Models;

use Database\Factories\CrewMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewMessage extends Model
{
    /** @use HasFactory<CrewMessageFactory> */
    use HasFactory;

    protected $fillable = ['sender_id', 'sender_name', 'recipient_id', 'recipient_name', 'subject', 'body', 'read_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /**
     * Get the crew member who sent the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the crew member the message was addressed to.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * The sender as the recipient sees them.
     */
    public function senderDisplayName(): string
    {
        return User::shortName($this->sender_name);
    }

    /**
     * The recipient as the sender sees them.
     */
    public function recipientDisplayName(): string
    {
        return User::shortName($this->recipient_name);
    }

    /**
     * Determine whether the recipient has opened the message.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
