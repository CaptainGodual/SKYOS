<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AcarsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_acars_login_issues_token(): void
    {
        $user = User::factory()->create([
            'email' => 'pilot@example.com',
            'password' => 'secret123',
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $response = $this->postJson('/api/acars/login', [
            'email' => 'pilot@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'pilot']);

        $plainToken = $response->json('token');
        $storedToken = ApiToken::query()->where('user_id', $user->id)->value('token');

        $this->assertNotNull($storedToken);
        $this->assertNotSame($plainToken, $storedToken, 'The plaintext token must never be stored.');
        $this->assertSame(ApiToken::hashToken($plainToken), $storedToken);
    }

    public function test_acars_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'pilot@example.com',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/acars/login', [
            'email' => 'pilot@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_acars_login_rejects_unapproved_pilot(): void
    {
        User::factory()->create([
            'email' => 'pilot@example.com',
            'password' => 'secret123',
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $this->postJson('/api/acars/login', [
            'email' => 'pilot@example.com',
            'password' => 'secret123',
        ])->assertStatus(403);
    }

    public function test_acars_flights_requires_token(): void
    {
        $this->getJson('/api/acars/flights')->assertStatus(401);
    }

    public function test_acars_can_list_flights_and_report_event(): void
    {
        config()->set('services.discord.webhook_url', 'https://discord.com/api/webhooks/123/token');
        Http::preventStrayRequests();
        Http::fake(['https://discord.com/api/webhooks/123/token' => Http::response([], 200)]);

        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        ApiToken::create([
            'user_id' => $user->id,
            'token' => ApiToken::hashToken('test-token-123'),
            'name' => 'Test Token',
            'expires_at' => now()->addHours(24),
        ]);

        $flight = Flight::factory()->create([
            'flight_number' => 'SKY501',
            'pilot_id' => $user->id,
            'pilot_name' => $user->name,
            'status' => 'Booked',
            'acars_status' => 'Scheduled',
        ]);

        // List flights
        $response = $this->withHeader('Authorization', 'Bearer test-token-123')
            ->getJson('/api/acars/flights');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'flights');
        $this->assertSame('SKY501', $response->json('flights.0.flight_number'));

        // Report takeoff
        $response = $this->withHeader('Authorization', 'Bearer test-token-123')
            ->postJson("/api/acars/flights/{$flight->id}/event", [
                'event_type' => 'Flight took off',
                'altitude' => 35000,
                'ground_speed' => 450,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('flights', [
            'id' => $flight->id,
            'acars_status' => 'En Route',
            'status' => 'Booked',
            'altitude' => 35000,
            'ground_speed' => 450,
        ]);
    }

    public function test_acars_cannot_access_another_pilots_flight(): void
    {
        $owner = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        ApiToken::create([
            'user_id' => $owner->id,
            'token' => ApiToken::hashToken('owner-token'),
            'name' => 'Owner Token',
            'expires_at' => now()->addHours(24),
        ]);

        $attacker = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        ApiToken::create([
            'user_id' => $attacker->id,
            'token' => ApiToken::hashToken('attacker-token'),
            'name' => 'Attacker Token',
            'expires_at' => now()->addHours(24),
        ]);

        $flight = Flight::factory()->create(['pilot_id' => $owner->id]);

        $this->withHeader('Authorization', 'Bearer attacker-token')
            ->getJson("/api/acars/flights/{$flight->id}")
            ->assertStatus(403);
    }

    public function test_acars_can_submit_pirep(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        ApiToken::create([
            'user_id' => $user->id,
            'token' => ApiToken::hashToken('pirep-token'),
            'name' => 'PIREP Token',
            'expires_at' => now()->addHours(24),
        ]);

        $flight = Flight::factory()->create([
            'flight_number' => 'SKY777',
            'pilot_id' => $user->id,
            'pilot_name' => $user->name,
            'payout_cents' => 15000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer pirep-token')
            ->postJson('/api/acars/pireps', [
                'flight_number' => 'SKY777',
                'departure' => 'KJFK',
                'arrival' => 'KBOS',
                'block_minutes' => 62,
                'landing_rate' => 150,
                'remarks' => 'ACARS auto-submitted.',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pireps', [
            'pilot_id' => $user->id,
            'flight_number' => 'SKY777',
            'status' => 'Pending review',
        ]);
    }

    public function test_acars_cannot_submit_two_pireps_for_the_same_flight(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        ApiToken::create([
            'user_id' => $user->id,
            'token' => ApiToken::hashToken('duplicate-token'),
            'name' => 'Duplicate Token',
            'expires_at' => now()->addHours(24),
        ]);

        Flight::factory()->create([
            'flight_number' => 'SKY888',
            'pilot_id' => $user->id,
            'pilot_name' => $user->name,
            'payout_cents' => 9000,
        ]);

        $payload = [
            'flight_number' => 'SKY888',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 62,
        ];

        $this->withHeader('Authorization', 'Bearer duplicate-token')
            ->postJson('/api/acars/pireps', $payload)
            ->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer duplicate-token')
            ->postJson('/api/acars/pireps', $payload)
            ->assertStatus(409);

        // Nothing is settled while the report is pending review.
        $this->assertDatabaseCount('pireps', 1);
        $this->assertDatabaseCount('financial_transactions', 0);
    }

    public function test_a_token_stops_working_when_the_pilot_is_rejected(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        ApiToken::create([
            'user_id' => $user->id,
            'token' => ApiToken::hashToken('revoked-token'),
            'name' => 'Revoked Token',
            'expires_at' => now()->addHours(24),
        ]);

        // The administrator rejects the account while the token is still valid.
        $user->update(['approval_status' => User::APPROVAL_REJECTED]);

        $this->withHeader('Authorization', 'Bearer revoked-token')
            ->getJson('/api/acars/flights')
            ->assertStatus(403);
    }
}
