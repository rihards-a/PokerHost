<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HandStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tableId;
    public $handId;
    public $data;

    /**
     * Create a new event instance.
     */
    public function __construct($tableId, $handId, $data)
    {
        $this->tableId = $tableId;
        $this->handId = $handId;
        $this->data = $data;
    }   /* $data = 
            'dealer'       => $hand->dealer_seat_id,
            'small_blind'  => $hand->small_blind_seat_id,
            'big_blind'    => $hand->big_blind_seat_id,
            'next_to_act'  => $nextToAct,
        ]); */

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hand.started';
    }
}
