<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_administrator_with_a_hashed_password(): void
    {
        $this->artisan('app:create-admin', [
            '--name' => 'Ops Boss',
            '--email' => 'boss@example.com',
            '--password' => 'super-secret-1',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'boss@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->is_admin);
        $this->assertSame(User::APPROVAL_APPROVED, $user->approval_status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotSame('super-secret-1', $user->password);
        $this->assertTrue(Hash::check('super-secret-1', $user->password));
    }

    public function test_it_promotes_an_existing_account_to_administrator(): void
    {
        $pilot = User::factory()->create([
            'email' => 'pilot@example.com',
            'is_admin' => false,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $this->artisan('app:create-admin', [
            '--name' => 'Pilot Name',
            '--email' => 'pilot@example.com',
            '--password' => 'brand-new-secret',
        ])
            ->expectsConfirmation('An account for pilot@example.com already exists. Promote it to administrator and reset its password?', 'yes')
            ->assertSuccessful();

        $pilot->refresh();

        $this->assertTrue($pilot->is_admin);
        $this->assertTrue(Hash::check('brand-new-secret', $pilot->password));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_rejects_a_short_password(): void
    {
        $this->artisan('app:create-admin', [
            '--name' => 'Short Pass',
            '--email' => 'short@example.com',
            '--password' => 'short',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'short@example.com']);
    }

    public function test_it_rejects_an_invalid_email_address(): void
    {
        $this->artisan('app:create-admin', [
            '--name' => 'Bad Email',
            '--email' => 'not-an-email',
            '--password' => 'long-enough-secret',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['name' => 'Bad Email']);
    }
}
