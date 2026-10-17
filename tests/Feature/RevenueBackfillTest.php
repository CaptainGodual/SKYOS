<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the migration that fills in ticket revenue for reports accepted
 * before revenue was booked at all.
 */
class RevenueBackfillTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_10_17_090100_backfill_flight_revenue_for_settled_pireps';

    public function test_it_books_the_revenue_missing_from_an_already_settled_flight(): void
    {
        config()->set('skyos.revenue_multiplier', 2.0);
        $pirep = $this->settledReport(10000);

        // The old settlement: the payout, and nothing for the tickets.
        $this->assertDatabaseCount('financial_transactions', 1);

        $this->runBackfill();

        $this->assertDatabaseHas(FinancialTransaction::class, [
            'type' => 'revenue',
            'category' => 'Flight Revenue',
            'amount_cents' => 20000,
            'reference_type' => 'pirep',
            'reference_id' => $pirep->flight_id,
        ]);
    }

    public function test_it_does_not_book_revenue_a_second_time(): void
    {
        config()->set('skyos.revenue_multiplier', 2.0);
        $pirep = $this->settledReport(10000);

        FinancialTransaction::create([
            'type' => 'revenue',
            'category' => 'Flight Revenue',
            'description' => 'Booked when the report was accepted.',
            'amount_cents' => 20000,
            'reference_type' => 'pirep',
            'reference_id' => $pirep->flight_id,
        ]);

        $this->runBackfill();

        $this->assertSame(1, FinancialTransaction::query()->where('category', 'Flight Revenue')->count());
        $this->assertDatabaseCount('financial_transactions', 2);
    }

    public function test_it_leaves_reports_that_never_reached_the_books_alone(): void
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create(['pilot_id' => $user->id, 'payout_cents' => 10000]);

        // Accepted, but nothing was ever settled for it.
        Pirep::factory()->create([
            'flight_id' => $flight->id,
            'pilot_id' => $user->id,
            'status' => 'Approved',
            'payout_cents' => 10000,
        ]);

        $this->runBackfill();

        $this->assertDatabaseCount('financial_transactions', 0);
    }

    public function test_it_books_nothing_when_revenue_is_switched_off(): void
    {
        config()->set('skyos.revenue_multiplier', 0);
        $this->settledReport(10000);

        $this->runBackfill();

        $this->assertDatabaseMissing('financial_transactions', ['type' => 'revenue']);
    }

    /**
     * A flight whose payout is already on the books, in the shape the old
     * settlement left behind.
     */
    private function settledReport(int $payoutCents): Pirep
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create([
            'flight_number' => 'SKY900',
            'pilot_id' => $user->id,
            'payout_cents' => $payoutCents,
        ]);

        $pirep = Pirep::factory()->create([
            'flight_id' => $flight->id,
            'pilot_id' => $user->id,
            'flight_number' => 'SKY900',
            'status' => 'Approved',
            'payout_cents' => $payoutCents,
        ]);

        FinancialTransaction::create([
            'type' => 'expense',
            'category' => 'Pilot Payout',
            'description' => 'Payout',
            'amount_cents' => -$payoutCents,
            'reference_type' => 'pirep',
            'reference_id' => $flight->id,
        ]);

        return $pirep;
    }

    /**
     * Run the backfill the way a deploy would: it has already been recorded, so
     * the record is removed and the migrator is asked to catch up.
     */
    private function runBackfill(): void
    {
        $this->assertSame(1, DB::table('migrations')->where('migration', self::MIGRATION)->delete());

        Artisan::call('migrate', ['--force' => true]);

        $this->assertDatabaseHas('migrations', ['migration' => self::MIGRATION]);
    }
}
