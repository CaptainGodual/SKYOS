<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ticket revenue only started being booked when a PIREP is accepted, so
     * reports that were accepted before that carry a payout on the books with
     * no revenue to go with it. This fills in the missing half for those
     * flights and leaves everything already booked alone, so it is safe to run
     * against a live ledger (and to run twice).
     */
    public function up(): void
    {
        $multiplier = (float) config('skyos.revenue_multiplier', 1.5);

        if ($multiplier <= 0) {
            return;
        }

        DB::table('pireps')
            ->whereIn('status', ['Approved', 'Paid'])
            ->whereNotNull('flight_id')
            ->where('payout_cents', '>', 0)
            ->chunkById(200, function ($pireps) use ($multiplier): void {
                foreach ($pireps as $pirep) {
                    // Only reports whose payout actually reached the books are
                    // revenue candidates: a report still waiting for review, or
                    // one whose rows were reversed, has nothing to match.
                    $payoutCents = abs((int) DB::table('financial_transactions')
                        ->where('reference_type', 'pirep')
                        ->where('reference_id', $pirep->flight_id)
                        ->where('category', 'Pilot Payout')
                        ->value('amount_cents'));

                    if ($payoutCents === 0) {
                        continue;
                    }

                    $alreadyBooked = DB::table('financial_transactions')
                        ->where('reference_type', 'pirep')
                        ->where('reference_id', $pirep->flight_id)
                        ->where('category', 'Flight Revenue')
                        ->exists();

                    if ($alreadyBooked) {
                        continue;
                    }

                    DB::table('financial_transactions')->insert([
                        'type' => 'revenue',
                        'category' => 'Flight Revenue',
                        'description' => "Ticket revenue for PIREP #{$pirep->id} ({$pirep->flight_number}) - {$pirep->departure} → {$pirep->arrival}",
                        'amount_cents' => (int) round($payoutCents * $multiplier),
                        'reference_type' => 'pirep',
                        'reference_id' => $pirep->flight_id,
                        // Dated when the report was accepted rather than when
                        // this ran, so the ledger reads in the right order.
                        'created_at' => $pirep->updated_at,
                        'updated_at' => $pirep->updated_at,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * Deliberately empty. These rows are ledger history and are indistinguishable
     * from revenue booked by an approval, so there is nothing safe to remove.
     */
    public function down(): void
    {
        //
    }
};
