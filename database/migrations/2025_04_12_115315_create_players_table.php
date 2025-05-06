<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            // $table->enum('status', ['active', 'folded', 'busted', 'away', 'allin'])->default('active'); # moving this to SeatHand, since it is specific to the hand and player status changes separately
            $table->boolean('active')->default(true);
            $table->integer('balance')->default(0);
            $table->string('guest_name')->nullable(); // for unauthenticated users
            $table->string('guest_session')->nullable();
            $table->foreignId('user_id')->nullable()->constrained(); // for authenticated users
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
