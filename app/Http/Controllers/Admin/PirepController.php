<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePirepRequest;
use App\Models\Pirep;
use App\PirepSettlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PirepController extends Controller
{
    public function index(): View
    {
        $pireps = Pirep::query()
            ->with('pilot')
            ->latest()
            ->paginate(15);

        return view('admin.pireps.index', ['pireps' => $pireps]);
    }

    public function show(Pirep $pirep): View
    {
        $pirep->load('pilot', 'flight');

        return view('admin.pireps.show', ['pirep' => $pirep]);
    }

    public function update(UpdatePirepRequest $request, Pirep $pirep, PirepSettlement $settlement): RedirectResponse
    {
        $wasAccepted = $pirep->isAccepted();

        $pirep->update($request->validated());

        // The airline only pays out once the report has been accepted, and the
        // money is taken back off the books if it is later rejected.
        if ($pirep->isAccepted()) {
            $settlement->settle($pirep);
        } elseif ($wasAccepted) {
            $settlement->unsettle($pirep);
        }

        return redirect()
            ->route('admin.pireps.index')
            ->with('success', "PIREP #{$pirep->id} status updated to {$pirep->status}.");
    }

    public function destroy(Pirep $pirep, PirepSettlement $settlement): RedirectResponse
    {
        // Removing the report removes the costs it put on the books.
        $settlement->unsettle($pirep);

        $pirep->delete();

        return redirect()
            ->route('admin.pireps.index')
            ->with('success', 'PIREP removed.');
    }
}
