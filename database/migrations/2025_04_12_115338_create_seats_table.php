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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_dealer')->default(false);
            $table->tinyInteger('position'); // 1-12 or maybe relative to the dealer
            $table->foreignId('table_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // if the associated user leaves there is no reason to track who sat where 
            // $table->foreignId('dealer_seat_id')->nullable()->constrained('seats')->nullOnDelete(); // possibly use this table for dealer tracking
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
