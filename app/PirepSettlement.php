<?php

namespace App;

use App\Models\FinancialTransaction;
use App\Models\Flight;
use App\Models\FlightExpense;
use App\Models\Pirep;

/**
 * Records and reverses the money a PIREP moves.
 *
 * Nothing is written while a PIREP is awaiting review: the payout, the ticket
 * revenue and the airport expenses only reach the books once an administrator
 * accepts the report, so a pilot cannot move money on their own.
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

        // The payout is the amount the report promised the pilot, not whatever
        // the flight route says today: editing a route afterwards must not
        // change what an already-filed PIREP pays out.
        if ($pirep->payout_cents > 0) {
            $this->record(
                $flight,
                FinancialTransaction::TYPE_EXPENSE,
                'Pilot Payout',
                "Payout for PIREP #{$pirep->id} ({$pirep->flight_number}) - {$pirep->departure} → {$pirep->arrival}",
                -abs($pirep->payout_cents),
            );

            // Ticket sales the flight brought in, derived from the payout so
            // every route contributes without a second number to maintain.
            $revenue = $this->revenueFor($pirep->payout_cents);

            if ($revenue > 0) {
                $this->record(
                    $flight,
                    FinancialTransaction::TYPE_REVENUE,
                    'Flight Revenue',
                    "Ticket revenue for PIREP #{$pirep->id} ({$pirep->flight_number}) - {$pirep->departure} → {$pirep->arrival}",
                    $revenue,
                );
            }
        }

        // Airport expenses for the departure and arrival airports.
        $expenses = FlightExpense::query()
            ->active()
            ->forAirports([$flight->departure, $flight->arrival])
            ->get();

        foreach ($expenses as $expense) {
            $this->record(
                $flight,
                FinancialTransaction::TYPE_EXPENSE,
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
     * Write a single transaction to the ledger.
     */
    private function record(Flight $flight, string $type, string $category, string $description, int $amountCents): void
    {
        FinancialTransaction::create([
            'type' => $type,
            'category' => $category,
            'description' => $description,
            'amount_cents' => $amountCents,
            // Settlement is keyed on the flight: only one PIREP can exist per
            // flight, and every row written before this class did the same.
            'reference_type' => 'pirep',
            'reference_id' => $flight->id,
        ]);
    }

    /**
     * Ticket revenue a flight generates, as a multiple of the pilot's payout.
     */
    private function revenueFor(int $payoutCents): int
    {
        $multiplier = (float) config('skyos.revenue_multiplier', 1.5);

        if ($multiplier <= 0) {
            return 0;
        }

        return (int) round($payoutCents * $multiplier);
    }
}
