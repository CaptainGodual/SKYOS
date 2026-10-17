<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePirepRequest;
use App\Models\Pirep;
use App\PirepSettlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PirepController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $pireps = Pirep::query()
            ->with('pilot')
            // The review queue links to `?status=Pending review` and
            // `?status=Approved`, so the list has to honour that filter.
            ->when(in_array($status, Pirep::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.pireps.index', ['pireps' => $pireps, 'status' => $status]);
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
