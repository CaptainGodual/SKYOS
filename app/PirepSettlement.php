<?php

namespace App;

use App\Models\FinancialTransaction;
use App\Models\Flight;
use App\Models\FlightExpense;
use App\Models\Pirep;

/**
 * Records and reverses the money a PIREP costs the airline.
 *
 * Nothing is written while a PIREP is awaiting review: the payout and the
 * airport expenses only reach the books once an administrator accepts the
 * report, so a pilot cannot move money on their own.
 */
class PirepSettlement
{
    /**
     * Put the cost of the PIREP's flight on the books.
     *
     * Safe to call more than once: a PIREP that is already settled is left
     * untouched, so re-approving it never charges the airline twice.
     */
    public function settle(Pirep $pirep): void
    {
        if ($this->isSettled($pirep)) {
            return;
        }

        $flight = $pirep->flight;

        if (! $flight instanceof Flight) {
            return;
        }

        if ($flight->payout_cents > 0) {
            $this->record(
                $flight,
                'Pilot Payout',
                "PIREP #{$flight->flight_number} - {$flight->departure} → {$flight->arrival}",
                -abs($flight->payout_cents),
            );
        }

        // Airport expenses for the departure and arrival airports.
        $expenses = FlightExpense::query()
            ->active()
            ->forAirports([$flight->departure, $flight->arrival])
            ->get();

        foreach ($expenses as $expense) {
            $this->record(
                $flight,
                $expense->category,
                "{$expense->name} - {$flight->flight_number} ({$flight->departure} → {$flight->arrival})",
                -abs($expense->cost_cents),
            );
        }
    }

    /**
     * Take the cost of the PIREP's flight back off the books.
     *
     * Used when an administrator rejects a PIREP, or removes it entirely. The
     * transactions are a projection of the report, so they go with it.
     */
    public function unsettle(Pirep $pirep): void
    {
        if ($pirep->flight_id === null) {
            return;
        }

        FinancialTransaction::query()
            ->where('reference_type', 'pirep')
            ->where('reference_id', $pirep->flight_id)
            ->delete();
    }

    /**
     * Determine whether the PIREP's cost is already on the books.
     */
    public function isSettled(Pirep $pirep): bool
    {
        if ($pirep->flight_id === null) {
            return false;
        }

        return FinancialTransaction::query()
            ->where('reference_type', 'pirep')
            ->where('reference_id', $pirep->flight_id)
            ->exists();
    }

    /**
     * Write a single expense to the ledger.
     */
    private function record(Flight $flight, string $category, string $description, int $amountCents): void
    {
        FinancialTransaction::create([
            'type' => 'expense',
            'category' => $category,
            'description' => $description,
            'amount_cents' => $amountCents,
            // Settlement is keyed on the flight: only one PIREP can exist per
            // flight, and every row written before this class did the same.
            'reference_type' => 'pirep',
            'reference_id' => $flight->id,
        ]);
    }
}
