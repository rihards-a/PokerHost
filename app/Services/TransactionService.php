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
}
