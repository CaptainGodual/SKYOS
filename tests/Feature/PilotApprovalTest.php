<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilotApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_pending_account(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Jamie Pilot',
            'email' => 'jamie@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jamie@example.com',
            'approval_status' => 'pending',
        ]);
    }

    public function test_pending_pilot_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'jamie@example.com',
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'jamie@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rejected_pilot_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'jamie@example.com',
            'approval_status' => User::APPROVAL_REJECTED,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'jamie@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_approved_pilot_can_sign_in(): void
    {
        $user = User::factory()->create([
            'email' => 'jamie@example.com',
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'jamie@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('crew-center.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_approve_pending_pilot(): void
    {
        $admin = User::factory()->admin()->create();
        $pilot = User::factory()->create(['approval_status' => User::APPROVAL_PENDING]);

        $response = $this->actingAs($admin)->put(route('admin.users.approve', $pilot));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $pilot->id,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
    }

    public function test_admin_can_reject_pending_pilot(): void
    {
        $admin = User::factory()->admin()->create();
        $pilot = User::factory()->create(['approval_status' => User::APPROVAL_PENDING]);

        $response = $this->actingAs($admin)->put(route('admin.users.reject', $pilot));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $pilot->id,
            'approval_status' => User::APPROVAL_REJECTED,
        ]);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $pilot = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);

        $this->actingAs($pilot)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_pilot_accounts_page_lists_pending_pilots(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Pending Pilot', 'email' => 'pending@example.com', 'approval_status' => User::APPROVAL_PENDING]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Pending Pilot');
        $response->assertSee('Pending');
    }
}
