<?php

namespace App\Console\Commands;

use App\Models\Flight;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:reset-flight-routes')]
#[Description('Reset completed and canceled flight routes back to Open status so they can be booked again.')]
class ResetFlightRoutes extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $resetCount = Flight::query()
            ->whereIn('status', ['Completed', 'Canceled'])
            ->update([
                'status' => 'Open',
                'pilot_id' => null,
                'pilot_name' => null,
                'acars_status' => 'Scheduled',
                'departed_at' => null,
                'arrived_at' => null,
            ]);

        $this->info("Reset {$resetCount} flight route(s) back to Open status.");

        return self::SUCCESS;
    }
}
