<?php

namespace App\Http\Controllers\Api;

use App\FlightWebhookDispatcher;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcarsEventRequest;
use App\Http\Requests\AcarsLoginRequest;
use App\Http\Requests\AcarsPirepRequest;
use App\Models\ApiToken;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AcarsController extends Controller
{
    /**
     * A throwaway bcrypt hash used when the submitted email does not exist.
     *
     * Verifying against it costs the same as verifying a real password, so the
     * response time does not reveal whether an account exists. It is the hash
     * of a random string that no pilot can guess.
     */
    private const TIMING_GUARD_HASH = '$2y$12$BtVYyW5jM1PU.olvvcszQOR0f9qA5sa2CnalkYwIMKe2jAJMdPq.G';

    /**
     * Authenticate a pilot and issue an ACARS API token.
     */
    public function login(AcarsLoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $user = User::query()->where('email', $credentials['email'])->first();

        // Unknown emails are still put through a full hash comparison so that
        // both branches take the same time.
        if (! $user) {
            Hash::check($credentials['password'], self::TIMING_GUARD_HASH);

            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if (! $user->isApproved()) {
            return response()->json([
                'message' => 'Your account is not approved for ACARS access.',
            ], 403);
        }

        // Create a new API token without invalidating existing ones.
        // Each login generates a unique token with its own 24-hour expiry.
        // Only the hash is persisted, so a database leak cannot be replayed.
        $token = bin2hex(random_bytes(24));
        $expiresAt = now()->addHours(24);

        ApiToken::create([
            'user_id' => $user->id,
            'token' => ApiToken::hashToken($token),
            'name' => 'ACARS Client',
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'token' => $token,
            'expires_at' => $expiresAt,
            'pilot' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'pilot_id' => 'SKY-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            ],
        ]);
    }

    /**
     * List the authenticated pilot's assigned/booked flights.
     */
    public function flights(Request $request): JsonResponse
    {
        $user = $request->attributes->get('acars_user');

        $flights = Flight::query()
            ->where('pilot_id', $user->id)
            ->orderBy('scheduled_at')
            ->get(['id', 'flight_number', 'departure', 'arrival', 'aircraft',
                'scheduled_at', 'estimated_minutes', 'payout_cents', 'status',
                'acars_status', 'departed_at', 'arrived_at']);

        return response()->json(['flights' => $flights]);
    }

    /**
     * Get a single flight's details.
     */
    public function flight(Request $request, Flight $flight): JsonResponse
    {
        $user = $request->attributes->get('acars_user');

        if ($flight->pilot_id !== $user->id) {
            return response()->json(['message' => 'Flight not assigned to you.'], 403);
        }

        return response()->json(['flight' => $flight]);
    }

    /**
     * Report an ACARS event (takeoff / position / landing).
     */
    public function event(AcarsEventRequest $request, Flight $flight, FlightWebhookDispatcher $webhooks): JsonResponse
    {
        $user = $request->attributes->get('acars_user');

        if ($flight->pilot_id !== $user->id) {
            return response()->json(['message' => 'Flight not assigned to you.'], 403);
        }

        $eventType = $request->validated('event_type');
        $updates = [
            'acars_status' => match ($eventType) {
                'Flight took off' => 'En Route',
                'Flight canceled' => 'Canceled',
                default => 'Completed',
            },
        ];

        if ($eventType === 'Flight took off') {
            $updates['status'] = 'Booked';
            $updates['departed_at'] = now();
        } elseif ($eventType === 'Flight canceled') {
            $updates['status'] = 'Canceled';
        } else {
            $updates['status'] = 'Completed';
            $updates['arrived_at'] = now();
        }

        // Optional telemetry from the client
        if ($request->has('altitude')) {
            $updates['altitude'] = $request->integer('altitude');
        }
        if ($request->has('ground_speed')) {
            $updates['ground_speed'] = $request->integer('ground_speed');
        }

        $flight->update($updates);
        $webhooks->dispatch($flight->fresh(), $eventType);

        return response()->json([
            'message' => 'ACARS event recorded.',
            'flight' => $flight->fresh(),
        ]);
    }

    /**
     * Submit a PIREP from the ACARS client.
     */
    public function storePirep(AcarsPirepRequest $request, FlightWebhookDispatcher $webhooks): JsonResponse
    {
        $user = $request->attributes->get('acars_user');
        $data = $request->validated();

        $flight = Flight::query()->where('flight_number', $data['flight_number'])->first();

        // Verify flight ownership
        if (! $flight || $flight->pilot_id !== $user->id) {
            return response()->json(['message' => 'Flight not assigned to you.'], 403);
        }

        if (Pirep::query()->where('flight_id', $flight->id)->exists()) {
            return response()->json(['message' => 'A PIREP has already been filed for this flight.'], 409);
        }

        Pirep::create([
            ...$data,
            'flight_id' => $flight->id,
            'pilot_id' => $user->id,
            'pilot_name' => $user->name,
            'payout_cents' => $flight->payout_cents,
        ]);

        $flight->update([
            'status' => 'Completed',
            'acars_status' => 'Completed',
            'arrived_at' => now(),
        ]);
        $webhooks->dispatch($flight->fresh(), 'Flight completed');

        // No money moves yet: the payout and the airport expenses are booked
        // once an administrator approves the report (see PirepSettlement).

        return response()->json([
            'message' => 'PIREP submitted for review.',
        ], 201);
    }
}
