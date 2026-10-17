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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hub_id']);
            $table->foreign('hub_id')
                ->references('id')
                ->on('hubs')
                ->nullOnDelete();
        });
    }
};
