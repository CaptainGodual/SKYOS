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
            ->whereIn('status', ['booked', 'in_progress'])
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
            ->whereIn('status', ['booked', 'in_progress'])
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

        $alreadyBooked = TourBooking::query()
            ->where('pilot_id', auth()->id())
            ->where('tour_id', $tour->id)
            ->whereIn('status', ['booked', 'in_progress'])
            ->exists();

        if ($alreadyBooked) {
            return back()->with('error', 'You have already booked this tour.');
        }

        TourBooking::query()->create([
            'tour_id' => $tour->id,
            'pilot_id' => auth()->id(),
            'status' => TourBooking::STATUS_BOOKED,
            'booked_at' => now(),
        ]);

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
