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
        Schema::table('flights', function (Blueprint $table) {
            // Indexed (not a hard FK) so an account deletion never cascades away
            // flight history; add the constraint manually if your policy demands it.
            $table->foreignId('pilot_id')->nullable()->after('status')->index();
        });

        Schema::table('pireps', function (Blueprint $table) {
            $table->foreignId('pilot_id')->nullable()->after('flight_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn('pilot_id');
        });

        Schema::table('pireps', function (Blueprint $table) {
            $table->dropColumn('pilot_id');
        });
    }
};
