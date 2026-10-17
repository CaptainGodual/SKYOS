<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Production-safe: this seeder creates NO demo content.
     *
     * To create your first administrator account, run:
     *   php artisan app:create-admin
     *
     * It is idempotent — running it repeatedly never duplicates anything.
     */
    public function run(): void
    {
        $this->command?->info('SKYOS ships no demo data — there is nothing to seed.');

        if (! Schema::hasTable('users')) {
            $this->command?->warn('This database has no tables yet. Run the migrations first:');
            $this->command?->line('  php artisan migrate --force');

            return;
        }

        if (! User::query()->where('is_admin', true)->exists()) {
            $this->command?->warn('No administrator account exists yet. Create one with:');
            $this->command?->line('  php artisan app:create-admin');
        }
    }
}
