<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AirportImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_airports_index(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.airports.index'));

        $response->assertOk();
        $response->assertSee('Airports');
    }

    public function test_admin_can_view_import_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.airports.import'));

        $response->assertOk();
        $response->assertSee('Import Airports');
    }

    public function test_guest_is_redirected_from_airports(): void
    {
        $this->get(route('admin.airports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.airports.import'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_denied_airports_access(): void
    {
        $pilot = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);

        $this->actingAs($pilot)->get(route('admin.airports.index'))->assertForbidden();
        $this->actingAs($pilot)->get(route('admin.airports.import'))->assertForbidden();
        $this->actingAs($pilot)->post(route('admin.airports.import.store'))->assertForbidden();
    }

    public function test_admin_can_import_airports_from_csv(): void
    {
        $admin = User::factory()->admin()->create();

        $csv = "identifier,iata_code,icao_code,name,type,latitude,longitude,elevation,country_code,region_code,municipality,time_zone\n"
            ."KJFK,JFK,KJFK,John F. Kennedy International Airport,large_airport,40.6413,-73.7781,13,US,US-NY,New York,America/New_York\n"
            ."EGLL,,EGLL,London Heathrow Airport,large_airport,51.4700,-0.4543,83,GB,GB-ENG,London,Europe/London\n"
            ."KLAX,LAX,KLAX,Los Angeles International Airport,large_airport,33.9425,-118.4081,126,US,US-CA,Los Angeles,America/Los_Angeles\n";

        $path = tempnam(sys_get_temp_dir(), 'airport_');
        file_put_contents($path, $csv);

        $uploaded = new UploadedFile($path, 'airports.csv', 'text/csv', null, true);

        $response = $this->actingAs($admin)->post(route('admin.airports.import.store'), [
            'file' => $uploaded,
        ]);

        $response->assertRedirect(route('admin.airports.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('airports', 3);
        $this->assertDatabaseHas('airports', [
            'identifier' => 'KJFK',
            'name' => 'John F. Kennedy International Airport',
            'country_code' => 'US',
            'type' => 'large_airport',
        ]);
        $this->assertDatabaseHas('airports', [
            'identifier' => 'EGLL',
            'iata_code' => null,
            'country_code' => 'GB',
        ]);
    }

    public function test_re_import_updates_existing_airports(): void
    {
        $admin = User::factory()->admin()->create();

        Airport::query()->create([
            'identifier' => 'KJFK',
            'name' => 'Old Name',
            'type' => 'small_airport',
            'country_code' => 'US',
        ]);

        $csv = "identifier,iata_code,icao_code,name,type,latitude,longitude,elevation,country_code,region_code,municipality,time_zone\n"
            ."KJFK,JFK,KJFK,John F. Kennedy International Airport,large_airport,40.6413,-73.7781,13,US,US-NY,New York,America/New_York\n";

        $path = tempnam(sys_get_temp_dir(), 'airport_');
        file_put_contents($path, $csv);

        $uploaded = new UploadedFile($path, 'airports.csv', 'text/csv', null, true);

        $this->actingAs($admin)->post(route('admin.airports.import.store'), [
            'file' => $uploaded,
        ]);

        $this->assertDatabaseCount('airports', 1);
        $this->assertDatabaseHas('airports', [
            'identifier' => 'KJFK',
            'name' => 'John F. Kennedy International Airport',
            'type' => 'large_airport',
        ]);
    }

    public function test_import_requires_a_file(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.airports.import.store'), [])
            ->assertSessionHasErrors('file');
    }

    public function test_import_trims_values_to_the_column_limits(): void
    {
        $admin = User::factory()->admin()->create();

        // MySQL enforces these VARCHAR limits in strict mode; SQLite does not.
        $csv = "identifier,iata_code,icao_code,name,type,latitude,longitude,elevation,country_code,region_code,municipality,time_zone\n"
            ."KJFK,JFK,KJFK,John F. Kennedy International Airport,large_airport,40.6413,-73.7781,13,USA,US-NY-EXTENDED,New York,America/New_York\n";

        $path = tempnam(sys_get_temp_dir(), 'airport_');
        file_put_contents($path, $csv);

        $uploaded = new UploadedFile($path, 'airports.csv', 'text/csv', null, true);

        $this->actingAs($admin)->post(route('admin.airports.import.store'), [
            'file' => $uploaded,
        ])->assertRedirect(route('admin.airports.index'));

        $airport = Airport::query()->where('identifier', 'KJFK')->firstOrFail();

        $this->assertSame('US', $airport->country_code);
        $this->assertSame('US-NY-EXTE', $airport->region_code);
    }

    public function test_admin_can_toggle_airport_hub_status(): void
    {
        $admin = User::factory()->admin()->create();
        $airport = Airport::factory()->create(['is_hub' => false]);

        $response = $this->actingAs($admin)->put(route('admin.airports.toggle-hub', $airport));

        $response->assertRedirect();
        $this->assertTrue((bool) $airport->fresh()->is_hub);

        $this->actingAs($admin)->put(route('admin.airports.toggle-hub', $airport));
        $this->assertFalse((bool) $airport->fresh()->is_hub);
    }
}
