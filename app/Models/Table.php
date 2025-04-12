<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'name', 
        'max_seats', 
        'status',
        'game_type',
        'host',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function seats(): HasMany {
        return $this->hasMany(Seat::class);
    }

    public function hands(): HasMany {
        return $this->hasMany(Hand::class);
    }
}
