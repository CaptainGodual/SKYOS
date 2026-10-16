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
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('origin_airport_id')->constrained('airports')->nullOnDelete();
            $table->foreignId('destination_airport_id')->constrained('airports')->nullOnDelete();
            $table->integer('total_distance_nm')->nullable()->comment('Total tour distance in nautical miles');
            $table->integer('estimated_duration_minutes')->nullable()->comment('Total estimated flight time');
            $table->integer('price_cents')->default(0)->comment('Tour price in cents');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('origin_airport_id');
            $table->index('destination_airport_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
