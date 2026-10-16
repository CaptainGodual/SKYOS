<?php

namespace App\Http\Controllers;

use App\FlightWebhookDispatcher;
use App\Http\Requests\StorePirepRequest;
use App\Models\CrewMessage;
use App\Models\Download;
use App\Models\FinancialTransaction;
use App\Models\Flight;
use App\Models\FlightExpense;
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

        $pireps = Pirep::query()->where('pilot_id', $user->id)->latest()->get();

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
                ->where('pilot_id', $user->id)
                ->where('acars_status', 'En Route')
                ->orderByDesc('departed_at')
                ->take(4)
                ->get(),
            'messages' => CrewMessage::query()
                ->where('recipient_name', $user->name)
                ->latest()
                ->take(3)
                ->get(),
            'pireps' => $pireps->take(3),
            'webhookEvents' => WebhookEvent::query()->latest()->take(4)->get(),
            'stats' => [
                'hours' => round($pireps->sum('block_minutes') / 60, 1),
                'income_cents' => $pireps
                    ->whereIn('status', ['Approved', 'Paid'])
                    ->sum('payout_cents'),
                'pending' => $pireps->where('status', 'Pending review')->count(),
                'pirep_count' => $pireps->count(),
                'unread' => CrewMessage::query()
                    ->where('recipient_name', $user->name)
                    ->whereNull('read_at')
                    ->count(),
            ],
        ]);
    }

    public function book(Request $request, Flight $flight): RedirectResponse
    {
        if ($flight->status !== 'Open' || $flight->pilot_id !== null) {
            return back()->with('error', 'This flight is no longer available.');
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

        Pirep::create([
            ...$data,
            'flight_id' => $flight?->id,
            'pilot_id' => $user->id,
            'pilot_name' => $user->name,
            'payout_cents' => $flight?->payout_cents ?? 0,
        ]);

        if ($flight !== null) {
            $flight->update([
                'status' => 'Completed',
                'acars_status' => 'Completed',
                'arrived_at' => now(),
            ]);
            $webhooks->dispatch($flight->fresh(), 'Flight completed');

            // Record revenue from flight payout
            if ($flight->payout_cents > 0) {
                FinancialTransaction::create([
                    'type' => 'revenue',
                    'category' => 'Flight Revenue',
                    'description' => "PIREP #{$flight->flight_number} - {$flight->departure} → {$flight->arrival}",
                    'amount_cents' => $flight->payout_cents,
                    'reference_type' => 'pirep',
                    'reference_id' => $flight->id,
                ]);
            }

            // Record airport expenses for departure and arrival airports
            $airportIds = [$flight->departure, $flight->arrival];
            $expenses = FlightExpense::query()
                ->where('is_active', true)
                ->whereHas('airports', function ($q) use ($airportIds) {
                    $q->whereIn('identifier', $airportIds);
                })
                ->get();

            foreach ($expenses as $expense) {
                FinancialTransaction::create([
                    'type' => 'expense',
                    'category' => $expense->category,
                    'description' => "{$expense->name} - {$flight->flight_number} ({$flight->departure} → {$flight->arrival})",
                    'amount_cents' => -abs($expense->cost_cents),
                    'reference_type' => 'pirep',
                    'reference_id' => $flight->id,
                ]);
            }
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

        CrewMessage::create([...$data, 'sender_name' => $request->user()->name]);

        return back()->with('success', 'Your message is on its way.');
    }

    public function dispatchFlightEvent(Request $request, Flight $flight, FlightWebhookDispatcher $webhooks): RedirectResponse
    {
        $data = $request->validate([
            'event_type' => ['required', 'in:Flight took off,Flight canceled,Flight completed'],
        ]);

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
