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
        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique()->comment('Airport identifier / ICAO code');
            $table->string('iata_code')->nullable()->comment('IATA code, when assigned');
            $table->string('icao_code')->nullable()->comment('ICAO code, when assigned');
            $table->string('name');
            $table->string('type')->comment('small_airport, medium_airport, large_airport, seaplane_base, heliport, closed');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('elevation')->nullable()->comment('Elevation in feet');
            $table->string('country_code', 2)->nullable()->comment('ISO 3166-1 alpha-2 country code');
            $table->string('region_code', 10)->nullable()->comment('ISO 3166-2 region code');
            $table->string('municipality')->nullable();
            $table->string('time_zone')->nullable();
            $table->timestamps();

            $table->index('country_code');
            $table->index('type');
            $table->index('iata_code');
            $table->index('icao_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};
