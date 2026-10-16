<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlightRequest;
use App\Http\Requests\UpdateFlightRequest;
use App\Models\Flight;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FlightController extends Controller
{
    public function index(): View
    {
        $flights = Flight::query()->latest()->paginate(15);

        return view('admin.flights.index', ['flights' => $flights]);
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
            $data['payout_cents'] = (int) round($data['payout_cents'] * 100);

            $flight->update($data);

            return redirect()
                ->route('admin.flights.index')
                ->with('success', 'Flight route updated.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update flight route: '.$e->getMessage());
        }
    }

    public function destroy(Flight $flight): RedirectResponse
    {
        $flight->delete();

        return redirect()
            ->route('admin.flights.index')
            ->with('success', 'Flight route removed from the schedule.');
    }
}
