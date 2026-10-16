<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Production-safe: this seeder creates NO demo content. Its only job is to
     * ensure a first administrator account exists on a fresh deployment, taking
     * the credentials from environment variables so no secrets live in the repo.
     *
     * It is idempotent — running it repeatedly never duplicates anything.
     */
    public function run(): void
    {
        $email = env('SKYOS_ADMIN_EMAIL');

        if (blank($email)) {
            $this->command?->warn(
                'SKYOS_ADMIN_EMAIL is not set — skipping admin bootstrap. No demo data was seeded.'
            );

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info("Account {$email} already exists — nothing to do.");

            return;
        }

        $password = env('SKYOS_ADMIN_PASSWORD');

        if (blank($password)) {
            $this->command?->error(
                'SKYOS_ADMIN_PASSWORD must be set to create the first administrator account.'
            );

            return;
        }

        // The User model casts 'password' => 'hashed', so the plain value is
        // hashed on assignment. Never pre-hash it here.
        User::query()->create([
            'name' => env('SKYOS_ADMIN_NAME', 'Crew Administrator'),
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $this->command?->info("Created administrator account for {$email}.");
    }
}
