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
        // The foreign key itself belongs to a later migration, so a rollback may
        // reach here with one in place or without one, depending on how far it
        // has gone. Drop it only when it is actually there.
        $hasForeignKey = collect(Schema::getForeignKeys('users'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['hub_id']);

        Schema::table('users', function (Blueprint $table) use ($hasForeignKey) {
            if ($hasForeignKey) {
                $table->dropForeign(['hub_id']);
            }

            $table->dropColumn('hub_id');
        });
    }
};
