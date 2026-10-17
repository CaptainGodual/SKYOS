<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\TourBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TourCenterController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $tours = Tour::query()
            ->active()
            ->withCount('legs')
            ->latest()
            ->paginate(12);

        $bookedTourIds = TourBooking::query()
            ->where('pilot_id', $user->id)
            ->active()
            ->pluck('tour_id');

        return view('crew-center.tours.index', [
            'user' => $user,
            'tours' => $tours,
            'bookedTourIds' => $bookedTourIds,
        ]);
    }

    public function show(Tour $tour): View
    {
        $user = auth()->user();
        $tour->load('legs.departureAirport', 'legs.arrivalAirport');

        $alreadyBooked = TourBooking::query()
            ->where('pilot_id', $user->id)
            ->where('tour_id', $tour->id)
            ->active()
            ->exists();

        return view('crew-center.tours.show', [
            'user' => $user,
            'tour' => $tour,
            'alreadyBooked' => $alreadyBooked,
        ]);
    }

    public function book(Tour $tour): RedirectResponse
    {
        if (! $tour->is_active) {
            return back()->with('error', 'This tour is not available for booking.');
        }

        $pilotId = auth()->id();

        $booking = TourBooking::query()
            ->where('pilot_id', $pilotId)
            ->where('tour_id', $tour->id)
            ->first();

        if ($booking && in_array($booking->status, TourBooking::ACTIVE_STATUSES, true)) {
            return back()->with('error', 'You have already booked this tour.');
        }

        // A pilot may re-book a tour they previously completed or cancelled.
        // The table allows one row per pilot and tour, so the old row is
        // reused instead of inserting a duplicate.
        TourBooking::query()->updateOrCreate(
            ['tour_id' => $tour->id, 'pilot_id' => $pilotId],
            [
                'status' => TourBooking::STATUS_BOOKED,
                'booked_at' => now(),
                'completed_at' => null,
            ],
        );

        return back()->with('success', "You've booked the {$tour->name} tour.");
    }

    public function myTours(): View
    {
        $user = auth()->user();
        $bookings = TourBooking::query()
            ->where('pilot_id', $user->id)
            ->with('tour.legs')
            ->latest()
            ->paginate(15);

        return view('crew-center.tours.my-tours', [
            'user' => $user,
            'bookings' => $bookings,
        ]);
    }

    /**
     * Release a tour the pilot no longer wants to fly.
     */
    public function cancel(TourBooking $booking): RedirectResponse
    {
        if ($booking->pilot_id !== auth()->id()) {
            return back()->with('error', 'That booking is not yours.');
        }

        if (! $booking->isActive()) {
            return back()->with('error', 'Only a tour you are still holding can be cancelled.');
        }

        // The row is kept and marked cancelled rather than deleted: the tour
        // centre allows one booking per pilot and tour, so this row is what a
        // later re-booking reuses.
        $booking->update([
            'status' => TourBooking::STATUS_CANCELLED,
            'completed_at' => null,
        ]);

        return back()->with('success', "You've cancelled your {$booking->tour->name} booking.");
    }
}
