<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Airport;
use App\Models\CrewMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form for the authenticated pilot.
     */
    public function edit(): View
    {
        $user = auth()->user();

        return view('profile.edit', [
            'user' => $user,
            'messages' => CrewMessage::query()
                ->where('recipient_name', $user->name)
                ->latest()
                ->take(3)
                ->get(),
            'countries' => (new UpdateProfileRequest)->countries(),
            'hubAirports' => Airport::query()->hub()->orderBy('identifier')->get(),
        ]);
    }

    /**
     * Update the authenticated pilot's profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Your profile has been updated.');
    }
}
