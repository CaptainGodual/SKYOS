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

            // The `flight_expenses` foreign key is added by the next migration
            // (create_flight_expenses_table) once that table exists, because
            // MySQL cannot reference a table that does not exist yet.
            $table->foreignId('expense_id');
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
