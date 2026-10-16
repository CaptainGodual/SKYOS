<?php

namespace App\Models;

use Database\Factories\HubFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'city', 'country', 'is_active'])]
class Hub extends Model
{
    /** @use HasFactory<HubFactory> */
    use HasFactory;

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
