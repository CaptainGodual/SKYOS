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
            $table->string('api_token', 80)->unique()->nullable()->after('password');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the unique index before the column: SQLite rebuilds the
            // table when dropping a column and chokes on a leftover index.
            $table->dropUnique(['api_token']);
            $table->dropColumn('api_token');
        });
    }
};
