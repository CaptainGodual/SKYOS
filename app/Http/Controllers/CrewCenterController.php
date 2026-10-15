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
    public function index(): View
    {
        return view('crew-center.index', [
            'downloads' => Download::query()->latest()->take(4)->get(),
            'flights' => Flight::query()->where('status', 'Open')->orderBy('scheduled_at')->take(5)->get(),
            'activeFlights' => Flight::query()->where('acars_status', 'En Route')->orderByDesc('departed_at')->take(4)->get(),
            'messages' => CrewMessage::query()->where('recipient_name', 'Captain Alex Morgan')->latest()->take(3)->get(),
            'pireps' => Pirep::query()->latest()->take(3)->get(),
            'webhookEvents' => WebhookEvent::query()->latest()->take(4)->get(),
        ]);
    }

    public function book(Flight $flight): RedirectResponse
    {
        if ($flight->status !== 'Open') {
            return back()->with('error', 'This flight is no longer available.');
        }

        $flight->update(['status' => 'Booked', 'pilot_name' => 'Captain Alex Morgan']);

        return back()->with('success', "{$flight->flight_number} has been added to your roster.");
    }

    public function storePirep(StorePirepRequest $request, FlightWebhookDispatcher $webhooks): RedirectResponse
    {
        $data = $request->validated();
        $flight = Flight::query()->where('flight_number', $data['flight_number'])->first();

        Pirep::create([
            ...$data,
            'flight_id' => $flight?->id,
            'pilot_name' => 'Captain Alex Morgan',
            'payout_cents' => $flight?->payout_cents ?? 0,
        ]);

        if ($flight !== null) {
            $flight->update(['status' => 'Completed', 'acars_status' => 'Completed', 'arrived_at' => now()]);
            $webhooks->dispatch($flight->fresh(), 'Flight completed');
        }

        return back()->with('success', 'Your manual PIREP has been submitted for review.');
    }

    public function storeMessage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        CrewMessage::create([...$data, 'sender_name' => 'Captain Alex Morgan']);

        return back()->with('success', 'Your message is on its way.');
    }

    public function dispatchFlightEvent(Request $request, Flight $flight, FlightWebhookDispatcher $webhooks): RedirectResponse
    {
        $data = $request->validate(['event_type' => ['required', 'in:Flight took off,Flight canceled,Flight completed']]);

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
