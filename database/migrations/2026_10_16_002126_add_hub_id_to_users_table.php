<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // A pilot's home hub is an airport. The foreign key itself is created
            // by the later `change_users_hub_id_to_airports_table` migration,
            // because MySQL cannot reference a table that does not exist yet.
            $table->foreignId('hub_id')->nullable()->after('approval_status');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hub_id');
        });
    }
};
