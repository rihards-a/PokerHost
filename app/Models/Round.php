<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $fillable = [
        'hand_id',
        'type',
        'is_complete',
    ];

    protected $dates = ['started_at', 'ended_at'];

    public function hand()
    {
        return $this->belongsTo(Hand::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }
}
