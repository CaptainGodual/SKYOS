<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\FlightExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the expense definitions.
     */
    public function index(): View
    {
        $expenses = FlightExpense::query()
            ->with('airports')
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.expenses.index', [
            'expenses' => $expenses,
        ]);
    }

    /**
     * Show the form for creating a new expense definition.
     */
    public function create(): View
    {
        return view('admin.expenses.create', [
            'selectedAirports' => collect(),
        ]);
    }

    /**
     * Store a newly created expense definition in storage.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = FlightExpense::query()->create($this->attributes($request));

        // Attach the airports this expense applies to.
        $expense->airports()->attach($this->airportIds($request->validated('airport_ids')));

        return redirect()
            ->route('admin.expenses.index')
            ->with('success', 'Expense created successfully.');
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(FlightExpense $expense): View
    {
        $expense->load('airports');

        return view('admin.expenses.edit', [
            'expense' => $expense,
            'selectedAirports' => $expense->airports,
        ]);
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(UpdateExpenseRequest $request, FlightExpense $expense): RedirectResponse
    {
        $expense->update($this->attributes($request));

        // Sync the airports this expense applies to.
        $expense->airports()->sync($this->airportIds($request->validated('airport_ids')));

        return redirect()
            ->route('admin.expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(FlightExpense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()
            ->route('admin.expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }

    /**
     * Build the model attributes from the validated expense form input.
     *
     * The admin enters the cost in dollars; it is always stored in whole cents.
     *
     * @return array<string, mixed>
     */
    private function attributes(StoreExpenseRequest|UpdateExpenseRequest $request): array
    {
        $attributes = $request->safe()->only(['name', 'category', 'description']);

        if ($request->has('is_active')) {
            $attributes['is_active'] = $request->boolean('is_active');
        }

        if ($request->has('cost_cents')) {
            $attributes['cost_cents'] = (int) round(((float) $request->validated('cost_cents')) * 100);
        }

        return $attributes;
    }

    /**
     * Normalise the submitted airport ids, dropping blank entries and duplicates.
     *
     * @param  array<int, mixed>|null  $airportIds
     * @return array<int, int>
     */
    private function airportIds(?array $airportIds): array
    {
        return collect($airportIds ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
