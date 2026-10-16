<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_for_authenticated_pilot(): void
    {
        $user = User::factory()->create([
            'approval_status' => User::APPROVAL_APPROVED,
            'name' => 'Alex Mercer',
            'email' => 'alex@example.com',
            'country' => 'US',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('Your profile');
        $response->assertSee('Alex Mercer');
        $response->assertSee('alex@example.com');
        $response->assertSee('United States');
    }

    public function test_guest_is_redirected_from_profile(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_pilot_can_update_profile(): void
    {
        $user = User::factory()->create([
            'approval_status' => User::APPROVAL_APPROVED,
            'name' => 'Alex Mercer',
            'email' => 'alex@example.com',
            'country' => 'US',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Alex J. Mercer',
            'email' => 'alex.mercer@example.com',
            'country' => 'GB',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Alex J. Mercer',
            'email' => 'alex.mercer@example.com',
            'country' => 'GB',
        ]);
    }

    public function test_email_must_be_unique(): void
    {
        $user = User::factory()->create([
            'approval_status' => User::APPROVAL_APPROVED,
            'email' => 'alex@example.com',
        ]);
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Alex Mercer',
            'email' => 'taken@example.com',
            'country' => 'US',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_profile_requires_valid_country(): void
    {
        $user = User::factory()->create([
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Alex Mercer',
            'email' => 'alex@example.com',
            'country' => 'ZZ',
        ])->assertSessionHasErrors('country');
    }

    public function test_profile_shows_home_hub_dropdown(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        Airport::factory()->create(['identifier' => 'KJFK', 'is_hub' => true]);
        Airport::factory()->create(['identifier' => 'EGLL', 'is_hub' => true]);
        Airport::factory()->create(['identifier' => 'KLAX', 'is_hub' => false]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('Home Hub');
        $response->assertSee('KJFK');
        $response->assertSee('EGLL');
        $response->assertDontSee('KLAX');
    }

    public function test_pilot_can_select_home_hub(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $hub = Airport::factory()->create(['identifier' => 'KJFK', 'is_hub' => true]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Alex Mercer',
            'email' => 'alex@example.com',
            'country' => 'US',
            'hub_id' => $hub->id,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'hub_id' => $hub->id,
        ]);
    }

    public function test_home_hub_must_be_a_valid_airport(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Alex Mercer',
            'email' => 'alex@example.com',
            'country' => 'US',
            'hub_id' => 99999,
        ])->assertSessionHasErrors('hub_id');
    }
}
