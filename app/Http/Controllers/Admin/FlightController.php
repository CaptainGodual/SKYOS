<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlightRequest;
use App\Http\Requests\UpdateFlightRequest;
use App\Models\Flight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlightController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $flights = Flight::query()
            // The "Open" shortcut link on the index filters the list.
            ->when(in_array($status, Flight::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.flights.index', ['flights' => $flights, 'status' => $status]);
    }

    public function create(): View
    {
        return view('admin.flights.create');
    }

    public function store(StoreFlightRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['payout_cents'] = (int) round($data['payout_cents'] * 100);

        Flight::query()->create($data);

        return redirect()
            ->route('admin.flights.index')
            ->with('success', 'Flight route added to the schedule.');
    }

    public function edit(Flight $flight): View
    {
        return view('admin.flights.edit', ['flight' => $flight]);
    }

    public function update(UpdateFlightRequest $request, Flight $flight): RedirectResponse
    {
        try {
            $data = $request->validated();

            // The form collects dollars; the column stores cents.
            if (array_key_exists('payout_cents', $data)) {
                $data['payout_cents'] = (int) round($data['payout_cents'] * 100);
            }

            $flight->update($data);

            return redirect()
                ->route('admin.flights.index')
                ->with('success', 'Flight route updated.');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Failed to update the flight route. Please check the values and try again.');
        }
    }

    public function destroy(Flight $flight): RedirectResponse
    {
        // A PIREP's costs are tracked against its flight, so deleting the route
        // would strand ledger rows that can no longer be reversed. The reports
        // have to be dealt with first.
        if ($flight->pireps()->exists()) {
            return redirect()
                ->route('admin.flights.index')
                ->with('error', 'This route has PIREPs filed against it. Remove those reports before deleting the route.');
        }

        $flight->delete();

        return redirect()
            ->route('admin.flights.index')
            ->with('success', 'Flight route removed from the schedule.');
    }
}
