<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('crew_messages', function (Blueprint $table) {
            // Messages used to be addressed by pilot name alone, which meant two
            // pilots with the same name could read each other's mail and a
            // rename lost the lot. The ids are the real address now; the names
            // stay as a label for display and for rows from before this change.
            $table->foreignId('sender_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->after('sender_id')->constrained('users')->nullOnDelete();
        });

        // Point existing messages at the accounts they were addressed to. A name
        // that matches nobody simply stays null.
        DB::table('crew_messages')->whereNull('recipient_id')->update([
            'recipient_id' => DB::raw('(select id from users where users.name = crew_messages.recipient_name limit 1)'),
        ]);

        DB::table('crew_messages')->whereNull('sender_id')->update([
            'sender_id' => DB::raw('(select id from users where users.name = crew_messages.sender_name limit 1)'),
        ]);
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('crew_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sender_id');
            $table->dropConstrainedForeignId('recipient_id');
        });
    }
};
