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
        // Only drop the old constraint if this migration is running against a
        // database that still has the original `hubs` foreign key.
        $hasHubForeignKey = collect(Schema::getForeignKeys('users'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['hub_id']);

        Schema::table('users', function (Blueprint $table) use ($hasHubForeignKey) {
            if ($hasHubForeignKey) {
                $table->dropForeign(['hub_id']);
            }

            $table->foreign('hub_id')
                ->references('id')
                ->on('airports')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the airports foreign key again. This used to re-create the old
        // `hubs` foreign key, but no forward migration ever created that
        // constraint and `hubs` is a dead table (users.hub_id points at
        // airports). MySQL refuses to drop a table that a foreign key still
        // references, so restoring it would break the rollback of
        // create_hubs_table for no benefit.
        $hasForeignKey = collect(Schema::getForeignKeys('users'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['hub_id']);

        if (! $hasForeignKey) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hub_id']);
        });
    }
};
