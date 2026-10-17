<?php

namespace App\Http\Controllers;

use App\Models\CrewMessage;
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
            'messages' => CrewMessage::query()
                ->where('recipient_name', $user->name)
                ->latest()
                ->take(3)
                ->get(),
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
            'messages' => CrewMessage::query()
                ->where('recipient_name', $user->name)
                ->latest()
                ->take(3)
                ->get(),
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
            'messages' => CrewMessage::query()
                ->where('recipient_name', $user->name)
                ->latest()
                ->take(3)
                ->get(),
        ]);
    }
}
