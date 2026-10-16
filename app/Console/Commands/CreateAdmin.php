<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:create-admin
                            {--name= : The administrator\'s full name}
                            {--email= : The administrator\'s email address}
                            {--password= : The administrator\'s password (you will be prompted securely if omitted)}';

    /**
     * @var string
     */
    protected $description = 'Create (or promote) a SKYOS administrator account securely.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Full name', 'Crew Administrator');
        $email = $this->option('email') ?: $this->ask('Admin email address');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?? '');

        if (blank($password)) {
            $password = (string) $this->secret('Password');
            $confirmation = (string) $this->secret('Confirm password');

            if ($password !== $confirmation) {
                $this->error('The passwords do not match.');

                return self::FAILURE;
            }
        }

        if (mb_strlen($password) < 8) {
            $this->error('The password must be at least 8 characters long.');

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing && ! $this->confirm("An account for {$email} already exists. Promote it to administrator and reset its password?", true)) {
            $this->info('Aborted — nothing was changed.');

            return self::SUCCESS;
        }

        $user = $existing ?? new User;

        // The `password` cast on the User model hashes the value with bcrypt,
        // so the plain-text password is never stored anywhere.
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
            'approval_status' => User::APPROVAL_APPROVED,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->info("Administrator account ready for {$user->email}.");
        $this->info('The password was hashed with bcrypt before storage — keep it somewhere safe.');

        return self::SUCCESS;
    }
}
