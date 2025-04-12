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
        Schema::create('hand_players', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['active', 'folded', 'busted'])->default('active');
            $table->foreignId('hand_id')->constrained()->onDelete('cascade');
            $table->foreignId('seat_id')->constrained('seats')->onDelete('cascade'); // the user gets associated via the seats table
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hand_players');
    }
};
