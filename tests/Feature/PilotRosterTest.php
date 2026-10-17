<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Pirep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilotRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('pilots.index'))->assertRedirect(route('login'));
    }

    public function test_pending_applicants_are_turned_away(): void
    {
        $applicant = User::factory()->create(['approval_status' => User::APPROVAL_PENDING]);

        $this->actingAs($applicant)->get(route('pilots.index'))->assertRedirect(route('login'));
    }

    public function test_it_lists_the_crew_with_their_flying_record(): void
    {
        $viewer = $this->pilot('Viewer Person');
        $heathrow = Airport::factory()->create(['identifier' => 'EGLL', 'name' => 'Heathrow', 'is_hub' => true]);
        $pilot = $this->pilot('Piper Alpha', $heathrow);
        $this->pilot('Dana Bravo', null, true);

        Pirep::factory()->create(['pilot_id' => $pilot->id, 'block_minutes' => 90, 'status' => 'Approved']);
        Pirep::factory()->create(['pilot_id' => $pilot->id, 'block_minutes' => 30, 'status' => 'Pending review']);

        $response = $this->actingAs($viewer)->get(route('pilots.index'));

        $response->assertOk();
        $response->assertSee('aria-current="page">Pilot Roster', false);
        $response->assertSee('Piper A.');
        $response->assertSee('EGLL');
        $response->assertSee('Heathrow');
        $response->assertSee('2.0');
        $response->assertSee('Staff');
        $response->assertSee(now()->format('M Y'));
    }

    public function test_the_roster_never_publishes_a_surname_or_an_email(): void
    {
        $viewer = $this->pilot('Viewer Person');
        $pilot = $this->pilot('Piper Alpha');
        $staff = $this->pilot('Dana Bravo', null, true);

        $response = $this->actingAs($viewer)->get(route('pilots.index'));

        $response->assertOk();
        $response->assertSee('Piper A.');
        $response->assertSee('Dana B.');
        $response->assertDontSee('Piper Alpha');
        $response->assertDontSee('Dana Bravo');
        $response->assertDontSee($pilot->email);
        $response->assertDontSee($staff->email);
    }

    public function test_applicants_and_rejected_accounts_are_left_off_the_roster(): void
    {
        $viewer = $this->pilot('Viewer Person');
        User::factory()->create(['name' => 'Ghost Applicant', 'approval_status' => User::APPROVAL_PENDING]);
        User::factory()->create(['name' => 'Rogue Rejected', 'approval_status' => User::APPROVAL_REJECTED]);

        $response = $this->actingAs($viewer)->get(route('pilots.index'));

        $response->assertOk();
        $response->assertDontSee('Ghost');
        $response->assertDontSee('Rogue');
    }

    public function test_the_roster_can_be_searched_by_name_and_by_hub(): void
    {
        $viewer = $this->pilot('Viewer Person');
        $this->pilot('Piper Alpha', Airport::factory()->create(['identifier' => 'EGLL']));
        $this->pilot('Dana Bravo', Airport::factory()->create(['identifier' => 'KORD']));

        $byName = $this->actingAs($viewer)->get(route('pilots.index', ['search' => 'Dana']));

        $byName->assertOk()->assertSee('Dana B.')->assertDontSee('Piper A.');

        $byHub = $this->actingAs($viewer)->get(route('pilots.index', ['search' => 'egll']));

        $byHub->assertOk()->assertSee('Piper A.')->assertDontSee('Dana B.');
    }

    public function test_a_wildcard_in_the_search_is_matched_literally(): void
    {
        $viewer = $this->pilot('Viewer Person');
        $this->pilot('Piper Alpha');

        $this->actingAs($viewer)->get(route('pilots.index', ['search' => '%']))
            ->assertOk()
            ->assertSee('No pilots match that search.');
    }

    /**
     * Create a crew member on the roster.
     */
    private function pilot(string $name, ?Airport $hub = null, bool $isAdmin = false): User
    {
        return User::factory()->create([
            'name' => $name,
            'hub_id' => $hub?->id,
            'is_admin' => $isAdmin,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
    }
}
