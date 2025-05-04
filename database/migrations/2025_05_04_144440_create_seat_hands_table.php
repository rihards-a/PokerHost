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
        Schema::create('seat_hands', function (Blueprint $table) {
            $table->id();
            $table->string('card1', 5)->nullable(); // maybe use different formatting
            $table->string('card2', 5)->nullable();
            $table->foreignId('seat_id')->constrained()->onDelete('cascade');
            $table->foreignId('hand_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_hands');
    }
};
