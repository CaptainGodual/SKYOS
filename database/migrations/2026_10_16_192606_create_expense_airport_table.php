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
        Schema::create('expense_airport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('flight_expenses')->onDelete('cascade');
            $table->foreignId('airport_id')->constrained('airports')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['expense_id', 'airport_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_airport');
    }
};
