<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTourRequest;
use App\Http\Requests\UpdateTourRequest;
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
        return view('admin.tours.create');
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

        return view('admin.tours.edit', [
            'tour' => $tour,
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
        $validated = request()->validate([
            'leg_number' => ['sometimes', 'integer', 'min:1'],
            'departure_airport_id' => ['required', 'exists:airports,id'],
            'arrival_airport_id' => ['required', 'exists:airports,id'],
            'distance_nm' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'estimated_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $lastLeg = $tour->legs()->max('leg_number') ?? 0;

        $tour->legs()->create([
            'leg_number' => $validated['leg_number'] ?? ($lastLeg + 1),
            'departure_airport_id' => $validated['departure_airport_id'],
            'arrival_airport_id' => $validated['arrival_airport_id'],
            'distance_nm' => $validated['distance_nm'],
            'estimated_minutes' => $validated['estimated_minutes'],
        ]);

        return back()->with('success', 'Tour leg added.');
    }

    public function removeLeg(Tour $tour, TourLeg $leg): RedirectResponse
    {
        $leg->delete();

        return back()->with('success', 'Tour leg removed.');
    }
}
