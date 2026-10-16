<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('app:create-admin')]
#[Description('Create a new administrator account securely']]
class CreateAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->ask('Admin email address');

        if ($this->confirm("Create administrator account for '{$email}'?", true)) {
            $password = $this->secret('Password');

            $user = User::create([
                'name' => $this->ask('Full name', 'Crew Administrator'),
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin' => true,
                'approval_status' => 'approved',
            ]);

            $this->info("Administrator account created for {$email}");
            $this->info('Please save your password securely - it will not be shown again.');
        }

        return 0;
    }
}
