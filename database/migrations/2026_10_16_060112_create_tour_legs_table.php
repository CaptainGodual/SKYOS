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
        Schema::create('tour_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->integer('leg_number')->comment('Sequential leg number within the tour');
            $table->foreignId('departure_airport_id')->constrained('airports')->nullOnDelete();
            $table->foreignId('arrival_airport_id')->constrained('airports')->nullOnDelete();
            $table->integer('distance_nm')->nullable()->comment('Leg distance in nautical miles');
            $table->integer('estimated_minutes')->nullable()->comment('Estimated flight time for this leg');
            $table->timestamps();

            $table->index('tour_id');
            $table->index('leg_number');
            $table->unique(['tour_id', 'leg_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_legs');
    }
};
