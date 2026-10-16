<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_renders_for_guests(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Create an account');
    }

    public function test_pilot_can_register_and_is_not_logged_in_until_approved(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Alex Mercer',
            'email' => 'alex@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'name' => 'Alex Mercer',
            'email' => 'alex@example.com',
            'is_admin' => false,
            'approval_status' => 'pending',
        ]);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post(route('register.store'), [
            'name' => 'Another Pilot',
            'email' => 'taken@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_logged_in_user_cannot_access_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('register'))->assertRedirect(route('crew-center.index'));
    }
}
