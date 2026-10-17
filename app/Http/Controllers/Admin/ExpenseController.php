<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Airport;
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
        $airports = Airport::query()
            ->orderBy('identifier')
            ->get();

        return view('admin.expenses.create', [
            'airports' => $airports,
        ]);
    }

    /**
     * Store a newly created expense definition in storage.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = FlightExpense::query()->create($request->safe()->only(['name', 'category', 'description', 'cost_cents', 'is_active']));

        // Attach selected airports
        $airportIds = $request->validated('airport_ids') ?? [];
        $expense->airports()->attach($airportIds);

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
        $airports = Airport::query()
            ->orderBy('identifier')
            ->get();

        return view('admin.expenses.edit', [
            'expense' => $expense,
            'airports' => $airports,
        ]);
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(UpdateExpenseRequest $request, FlightExpense $expense): RedirectResponse
    {
        $expense->update($request->safe()->only(['name', 'category', 'description', 'cost_cents', 'is_active']));

        // Sync airports
        $airportIds = $request->validated('airport_ids') ?? [];
        $expense->airports()->sync($airportIds);

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
}
