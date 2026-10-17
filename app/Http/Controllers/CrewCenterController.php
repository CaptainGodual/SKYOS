<?php

namespace App\Http\Controllers;

use App\FlightWebhookDispatcher;
use App\Http\Requests\StorePirepRequest;
use App\Models\CrewMessage;
use App\Models\Download;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\WebhookEvent;
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
            ->where('recipient_name', $user->name)
            ->whereNull('read_at')
            ->count();

        return view('crew-center.index', [
            'user' => $user,
            'downloads' => Download::query()->latest()->take(4)->get(),
            'flights' => Flight::query()
                ->where('status', 'Open')
                ->whereNull('pilot_id')
                ->orderBy('scheduled_at')
                ->take(5)
                ->get(),
            'activeFlights' => Flight::query()
                ->where('pilot_id', $pilotId)
                ->where('acars_status', 'En Route')
                ->orderByDesc('departed_at')
                ->take(4)
                ->get(),
            'messages' => CrewMessage::query()
                ->where('recipient_name', $user->name)
                ->latest()
                ->take(3)
                ->get(),
            'webhookEvents' => WebhookEvent::query()->latest()->take(4)->get(),
            'stats' => [
                'hours' => round($flightHours / 60, 1),
                'income_cents' => $incomeCents,
                'pending' => $pendingPireps,
                'pirep_count' => $pirepCount,
                'unread' => $unreadMessages,
            ],
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

    public function storeMessage(Request $request): RedirectResponse
    {
        // Crew messages are addressed by pilot name (see the crew_messages
        // table), so the recipient must be a real crew member rather than any
        // name the sender happens to type.
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:100', 'exists:users,name'],
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'recipient_name.exists' => 'No crew member with that name was found.',
        ]);

        CrewMessage::create([...$data, 'sender_name' => $request->user()->name]);

        return back()->with('success', 'Your message is on its way.');
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
