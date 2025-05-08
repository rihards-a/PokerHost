<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerCardsDealt implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tableId;
    public $seatId;
    public $cards;

    /**
     * Create a new event instance.
     */
    public function __construct($tableId, $seatId, $cards)
    {
        $this->tableId = $tableId;
        $this->seatId = $seatId;
        $this->cards = $cards;
    }

    /* $cards = [
        'card1' => 'As', 
        'card2' => 'Ks'*/

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("table.{$this->tableId}.seat.{$this->seatId}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'cards.dealt';
    }
}
