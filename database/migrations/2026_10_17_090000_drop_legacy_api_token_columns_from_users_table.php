<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ACARS tokens live in their own table and are stored only as a hash, so the
     * plaintext token that used to sit on the pilot row has been unused since.
     * A dead column that once held credentials is a liability, so it goes.
     */
    public function up(): void
    {
        $columns = array_values(array_filter(
            ['api_token', 'token_expires_at'],
            fn (string $column): bool => Schema::hasColumn('users', $column),
        ));

        if ($columns === []) {
            return;
        }

        // The unique index has to go before the column does: SQLite refuses to
        // drop a column that is still indexed. Dropping it first is harmless on
        // MySQL, which would have removed it with the column anyway.
        $hasIndex = collect(Schema::getIndexes('users'))
            ->contains(fn (array $index): bool => $index['name'] === 'users_api_token_unique');

        Schema::table('users', function (Blueprint $table) use ($columns, $hasIndex): void {
            if ($hasIndex) {
                $table->dropUnique('users_api_token_unique');
            }

            $table->dropColumn($columns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Two statements rather than one: `token_expires_at` sits after
        // `api_token`, and a column added in the same ALTER is not a safe thing
        // to point AFTER at on every engine.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'api_token')) {
                $table->string('api_token', 80)->nullable()->unique()->after('password');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'token_expires_at')) {
                $table->timestamp('token_expires_at')->nullable()->after('api_token');
            }
        });
    }
};
