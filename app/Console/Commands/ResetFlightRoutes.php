<?php

namespace App\Console\Commands;

use App\Models\Flight;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:reset-flight-routes')]
#[Description('Reset completed and canceled flight routes that were never reported back to Open so they can be booked again.')]
class ResetFlightRoutes extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $reported = Flight::query()
            ->whereIn('status', ['Completed', 'Canceled'])
            ->whereHas('pireps')
            ->count();

        // A flight that already carries a PIREP is never reset. A second report
        // for the same flight is refused everywhere (see CrewCenterController
        // and AcarsController), so re-opening it would only put a route on the
        // board that no pilot can file for - and the report it already has is
        // what its payout was settled from.
        $resetCount = Flight::query()
            ->whereIn('status', ['Completed', 'Canceled'])
            ->whereDoesntHave('pireps')
            ->update([
                'status' => 'Open',
                'pilot_id' => null,
                'pilot_name' => null,
                'acars_status' => 'Scheduled',
                'departed_at' => null,
                'arrived_at' => null,
            ]);

        $this->info("Reset {$resetCount} flight route(s) back to Open status.");

        if ($reported > 0) {
            $this->warn(
                "Skipped {$reported} flight(s) that already have a PIREP. "
                .'Delete the report first if the route really should be re-flown.'
            );
        }

        return self::SUCCESS;
    }
}
