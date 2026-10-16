<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTourRequest;
use App\Http\Requests\UpdateTourRequest;
use App\Models\Airport;
use App\Models\Tour;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TourController extends Controller
{
    public function index(): View
    {
        $tours = Tour::query()
            ->withCount('legs')
            ->withCount('bookings')
            ->latest()
            ->paginate(15);

        return view('admin.tours.index', ['tours' => $tours]);
    }

    public function create(): View
    {
        $airports = Airport::query()->orderBy('identifier')->get();

        return view('admin.tours.create', ['airports' => $airports]);
    }

    public function store(StoreTourRequest $request): RedirectResponse
    {
        $tour = Tour::query()->create($request->validated());

        return redirect()
            ->route('admin.tours.edit', $tour)
            ->with('success', 'Tour created. Add legs to complete the itinerary.');
    }

    public function edit(Tour $tour): View
    {
        $tour->load('legs.departureAirport', 'legs.arrivalAirport');
        $airports = Airport::query()->orderBy('identifier')->get();

        return view('admin.tours.edit', [
            'tour' => $tour,
            'airports' => $airports,
        ]);
    }

    public function update(UpdateTourRequest $request, Tour $tour): RedirectResponse
    {
        $tour->update($request->validated());

        return redirect()
            ->route('admin.tours.edit', $tour)
            ->with('success', 'Tour updated.');
    }

    public function destroy(Tour $tour): RedirectResponse
    {
        $tour->delete();

        return redirect()
            ->route('admin.tours.index')
            ->with('success', 'Tour removed.');
    }

    public function addLeg(Tour $tour): RedirectResponse
    {
        $lastLeg = $tour->legs()->max('leg_number') ?? 0;

        $tour->legs()->create([
            'leg_number' => $lastLeg + 1,
            'departure_airport_id' => request('departure_airport_id'),
            'arrival_airport_id' => request('arrival_airport_id'),
            'distance_nm' => request('distance_nm'),
            'estimated_minutes' => request('estimated_minutes'),
        ]);

        return back()->with('success', 'Tour leg added.');
    }

    public function removeLeg(Tour $tour, TourLeg $leg): RedirectResponse
    {
        $leg->delete();

        return back()->with('success', 'Tour leg removed.');
    }
}
