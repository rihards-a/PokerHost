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
        Schema::create('hands', function (Blueprint $table) {
            $table->id();
            $table->json('community_cards')->nullable(); // Example: ["Ah", "Kd", "Qs", "Jc", "9h"] - possibly change to numbers for faster parsing
            $table->unsignedInteger('pot_size');
            $table->boolean('is_complete')->default(false);
            $table->foreignId('dealer_id')->nullable()->constrained('seats')->onDelete('set null');
            $table->foreignId('small_blind_id')->nullable()->constrained('seats')->onDelete('set null');
            $table->foreignId('big_blind_id')->nullable()->constrained('seats')->onDelete('set null');
            $table->foreignId('table_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hands');
    }
};
