<?php

namespace App\Models;

use Database\Factories\CrewMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewMessage extends Model
{
    /** @use HasFactory<CrewMessageFactory> */
    use HasFactory;

    protected $fillable = ['sender_name', 'recipient_name', 'subject', 'body', 'read_at'];
}
