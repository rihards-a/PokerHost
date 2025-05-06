<?php

namespace App\Services;

use App\Models\Transaction;

class TransactionService
{
    /**
     * Process a bet transaction for a player in the hand.
     */
    public function betTransaction($hand, $player, $amount) {
        $transaction = Transaction::create([
            'player_id' => $player->id,	
            'amount' => $amount,
            'type' => 'bet',
        ]);

        $player->decrement('balance', $amount);
        $hand->increment('pot_size', $amount);

        return $transaction;
    }

    /**
     * Distribute the pot among a sorted array of hand winners and create the appropriate transaction. If there is a remainder, distribute it to the first winners.
     */
    public function distributePot($pot, $winners) {
        $splitAmount = $pot / count($winners);
        $remainder = $pot % count($winners);
        foreach ($winners as $winner) {
            $amount = $splitAmount + ($remainder > 0 ? 1 : 0);
            $winner->increment('balance', $amount);
            $winner->transactions()->create([
                'amount' => $amount,
                'type' => 'win',
            ]);
            $remainder--;
        }
    }
}
