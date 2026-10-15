<?php

namespace App\Console\Commands;

use App\Models\CrewMessage;
use App\Models\Download;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeDemoData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'skyos:purge-demo {--force : Skip the confirmation prompt}';

    /**
     * @var string
     */
    protected $description = 'Remove the demo records that shipped with the SKYOS scaffolding.';

    /**
     * The demo flight numbers seeded by the original DatabaseSeeder.
     */
    private const DEMO_FLIGHTS = ['SKY101', 'SKY284', 'SKY412', 'SKY067'];

    /**
     * The demo download titles seeded by the original DatabaseSeeder.
     */
    private const DEMO_DOWNLOADS = [
        'SKYOS Operations Manual',
        'Fleet Livery Pack',
        'ACARS Desktop Client',
        'Pilot Quick Reference',
    ];

    /**
     * The demo crew message subjects seeded by the original DatabaseSeeder.
     */
    private const DEMO_MESSAGE_SUBJECTS = [
        'October schedule published',
        'Welcome aboard',
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Delete the seeded demo records from this database?', false)) {
            $this->info('Aborted — nothing was deleted.');

            return self::SUCCESS;
        }

        $removed = DB::transaction(function (): array {
            $flightIds = Flight::query()
                ->whereIn('flight_number', self::DEMO_FLIGHTS)
                ->pluck('id');

            return [
                'webhook events' => WebhookEvent::query()->whereIn('flight_id', $flightIds)->delete(),
                'PIREPs' => Pirep::query()->whereIn('flight_id', $flightIds)->delete(),
                'flights' => Flight::query()->whereIn('id', $flightIds)->delete(),
                'downloads' => Download::query()->whereIn('name', self::DEMO_DOWNLOADS)->delete(),
                'crew messages' => CrewMessage::query()->whereIn('subject', self::DEMO_MESSAGE_SUBJECTS)->delete(),
            ];
        });

        foreach ($removed as $label => $count) {
            $this->line(sprintf('  Removed %d demo %s', $count, $label));
        }

        $this->info('Demo data purge complete.');

        return self::SUCCESS;
    }
}
