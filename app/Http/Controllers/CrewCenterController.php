<?php

namespace App\Http\Controllers;

use App\FlightWebhookDispatcher;
use App\Http\Requests\StorePirepRequest;
use App\Models\CrewMessage;
use App\Models\Download;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;
use App\Models\WebhookEvent;
use App\RouteMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrewCenterController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $pilotId = $user->id;

        // Aggregated in SQL: a long-serving pilot can hold thousands of reports
        // and the dashboard only ever renders the totals.
        $flightHours = Pirep::query()->where('pilot_id', $pilotId)->sum('block_minutes');
        $incomeCents = Pirep::query()
            ->where('pilot_id', $pilotId)
            ->whereIn('status', Pirep::ACCEPTED_STATUSES)
            ->sum('payout_cents');
        $pendingPireps = Pirep::query()->where('pilot_id', $pilotId)->where('status', 'Pending review')->count();
        $pirepCount = Pirep::query()->where('pilot_id', $pilotId)->count();
        $unreadMessages = CrewMessage::query()
            ->where('recipient_id', $pilotId)
            ->whereNull('read_at')
            ->count();

        return view('crew-center.index', [
            'user' => $user,
            'downloads' => Download::query()->latest()->take(4)->get(),
            // Only a taste of the board: the full list lives on its own page
            // next to the route map.
            'flights' => Flight::query()
                ->where('status', 'Open')
                ->whereNull('pilot_id')
                ->orderBy('scheduled_at')
                ->take(3)
                ->get(),
            'activeFlights' => Flight::query()
                ->where('pilot_id', $pilotId)
                ->where('acars_status', 'En Route')
                ->orderByDesc('departed_at')
                ->take(4)
                ->get(),
            'messages' => CrewMessage::query()
                ->where('recipient_id', $pilotId)
                ->latest()
                ->take(3)
                ->get(),
            'webhookEvents' => WebhookEvent::query()->latest()->take(4)->get(),
            'crew' => User::messagingDirectory($pilotId),
            'stats' => [
                'hours' => round($flightHours / 60, 1),
                'income_cents' => $incomeCents,
                'pending' => $pendingPireps,
                'pirep_count' => $pirepCount,
                'unread' => $unreadMessages,
                'open_flights' => Flight::query()->where('status', 'Open')->whereNull('pilot_id')->count(),
            ],
        ]);
    }

    /**
     * The open flight board, with the map of every route in the network.
     */
    public function flights(Request $request, RouteMap $routeMap): View
    {
        return view('crew-center.flights.index', [
            'user' => $request->user(),
            'flights' => Flight::query()
                ->where('status', 'Open')
                ->whereNull('pilot_id')
                ->orderBy('scheduled_at')
                ->paginate(20),
            'map' => $routeMap->build(),
        ]);
    }

    public function book(Request $request, Flight $flight): RedirectResponse
    {
        if ($flight->status !== 'Open' || $flight->pilot_id !== null) {
            return back()->with('error', 'This flight is no longer available.');
        }

        // Bound how much a single pilot can settle into the books at once.
        // `0` disables the limit.
        $limit = (int) config('skyos.max_booked_flights');

        if ($limit > 0) {
            $booked = Flight::query()
                ->where('pilot_id', $request->user()->id)
                ->where('status', 'Booked')
                ->count();

            if ($booked >= $limit) {
                return back()->with('error', "You already have {$limit} flights on your roster. Complete or release one before booking another.");
            }
        }

        $flight->update([
            'status' => 'Booked',
            'pilot_id' => $request->user()->id,
            'pilot_name' => $request->user()->name,
        ]);

        return back()->with('success', "{$flight->flight_number} has been added to your roster.");
    }

    public function storePirep(StorePirepRequest $request, FlightWebhookDispatcher $webhooks): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $flight = Flight::query()->where('flight_number', $data['flight_number'])->first();

        if (! $flight || $flight->pilot_id !== $user->id) {
            return back()->with('error', 'That flight is not assigned to you. Book it from the crew centre first.');
        }

        if (Pirep::query()->where('flight_id', $flight->id)->exists()) {
            return back()->with('error', 'A PIREP has already been filed for this flight.');
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

        return back()->with('success', 'Your manual PIREP has been submitted for review.');
    }

    public function dispatchFlightEvent(Request $request, Flight $flight, FlightWebhookDispatcher $webhooks): RedirectResponse
    {
        if ($flight->pilot_id !== $request->user()->id) {
            return back()->with('error', 'That flight is not assigned to you.');
        }

        $data = $request->validate([
            'event_type' => ['required', 'in:Flight took off,Flight canceled,Flight completed'],
        ]);

        if ($data['event_type'] === 'Flight completed' && $flight->status === 'Completed') {
            return back()->with('error', 'This flight has already been completed.');
        }

        $status = match ($data['event_type']) {
            'Flight took off' => ['status' => 'Booked', 'acars_status' => 'En Route', 'departed_at' => now()],
            'Flight canceled' => ['status' => 'Canceled', 'acars_status' => 'Canceled'],
            default => ['status' => 'Completed', 'acars_status' => 'Completed', 'arrived_at' => now()],
        };

        $flight->update($status);
        $webhooks->dispatch($flight->fresh(), $data['event_type']);

        return back()->with('success', 'Flight event posted to the webhook queue.');
    }
}
