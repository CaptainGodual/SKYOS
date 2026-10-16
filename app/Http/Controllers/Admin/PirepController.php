<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePirepRequest;
use App\Models\Pirep;
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

    public function update(UpdatePirepRequest $request, Pirep $pirep): RedirectResponse
    {
        $pirep->update($request->validated());

        return redirect()
            ->route('admin.pireps.index')
            ->with('success', "PIREP #{$pirep->id} status updated to {$pirep->status}.");
    }

    public function destroy(Pirep $pirep): RedirectResponse
    {
        $pirep->delete();

        return redirect()
            ->route('admin.pireps.index')
            ->with('success', 'PIREP removed.');
    }
}
