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
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('flight_number')->unique();
            $table->string('departure', 4);
            $table->string('arrival', 4);
            $table->string('aircraft');
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('estimated_minutes');
            $table->unsignedInteger('payout_cents');
            $table->string('status')->default('Open');
            $table->string('pilot_name')->nullable();
            $table->string('acars_status')->default('Scheduled');
            $table->unsignedSmallInteger('altitude')->nullable();
            $table->unsignedSmallInteger('ground_speed')->nullable();
            $table->dateTime('departed_at')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
