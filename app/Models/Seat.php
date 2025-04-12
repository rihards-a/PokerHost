<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = [
        'table_id',
        'player_id',
        'dealer_seat_id',
        'position',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function player()
    {
        return $this->belongsTo(User::class);
    }

    public function handsAsDealer()
    {
        return $this->hasMany(Hand::class, 'dealer_seat_id'); // the Seat
    }
}

$table->tinyInteger('position'); // 1-12 or maybe relative to the dealer
$table->foreignId('table_id')->constrained()->onDelete('cascade');
$table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // if the associated user leaves there is no reason to track who sat where 
$table->foreignId('dealer_seat_id')->nullable()->constrained('seats')->nullOnDelete();