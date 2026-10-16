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
        Schema::create('flight_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('e.g. Ground Handling, Landing Fee, Fuel, Catering');
            $table->string('category')->comment('e.g. Handling, Landing, Fuel, Catering, De-icing');
            $table->text('description')->nullable();
            $table->integer('cost_cents')->default(0)->comment('Cost in cents');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_expenses');
    }
};
