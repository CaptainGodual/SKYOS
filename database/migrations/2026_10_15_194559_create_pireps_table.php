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
        Schema::create('pireps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pilot_name');
            $table->string('flight_number');
            $table->string('departure', 4);
            $table->string('arrival', 4);
            $table->unsignedSmallInteger('block_minutes');
            $table->unsignedInteger('landing_rate')->nullable();
            $table->unsignedInteger('payout_cents')->default(0);
            $table->text('remarks')->nullable();
            $table->string('status')->default('Pending review');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pireps');
    }
};
