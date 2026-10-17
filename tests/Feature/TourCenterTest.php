<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\TourLeg;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_tours_index(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.tours.index'));

        $response->assertOk();
        $response->assertSee('Tours');
    }

    public function test_admin_can_create_tour(): void
    {
        $admin = User::factory()->admin()->create();
        $origin = Airport::factory()->create(['identifier' => 'KJFK']);
        $destination = Airport::factory()->create(['identifier' => 'KBOS']);

        $response = $this->actingAs($admin)->post(route('admin.tours.store'), [
            'name' => 'Transatlantic Crossing',
            'description' => 'A classic transatlantic tour.',
            'origin_airport_id' => $origin->id,
            'destination_airport_id' => $destination->id,
            'price_cents' => 15000,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tours', [
            'name' => 'Transatlantic Crossing',
            'origin_airport_id' => $origin->id,
            'destination_airport_id' => $destination->id,
            'price_cents' => 15000,
        ]);
    }

    public function test_admin_can_add_leg_to_tour(): void
    {
        $admin = User::factory()->admin()->create();
        $tour = Tour::factory()->create();
        $departure = Airport::factory()->create(['identifier' => 'KJFK']);
        $arrival = Airport::factory()->create(['identifier' => 'KBOS']);

        $response = $this->actingAs($admin)->post(route('admin.tours.legs.store', $tour), [
            'leg_number' => 1,
            'departure_airport_id' => $departure->id,
            'arrival_airport_id' => $arrival->id,
            'distance_nm' => 200,
            'estimated_minutes' => 90,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tour_legs', [
            'tour_id' => $tour->id,
            'leg_number' => 1,
            'departure_airport_id' => $departure->id,
            'arrival_airport_id' => $arrival->id,
        ]);
    }

    public function test_pilot_can_view_tour_center(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        Tour::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($user)->get(route('tours.index'));

        $response->assertOk();
        $response->assertSee('Available tours');
    }

    public function test_pilot_can_book_tour(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $tour = Tour::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('tours.book', $tour));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tour_bookings', [
            'tour_id' => $tour->id,
            'pilot_id' => $user->id,
            'status' => 'booked',
        ]);
    }

    public function test_pilot_cannot_book_twice(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $tour = Tour::factory()->create(['is_active' => true]);
        TourBooking::factory()->create(['tour_id' => $tour->id, 'pilot_id' => $user->id, 'status' => 'booked']);

        $response = $this->actingAs($user)->post(route('tours.book', $tour));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('tour_bookings', 1);
    }

    public function test_guest_is_redirected_from_tours(): void
    {
        $this->get(route('tours.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_admin_tours(): void
    {
        $pilot = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);

        $this->actingAs($pilot)->get(route('admin.tours.index'))->assertForbidden();
    }

    public function test_tour_show_displays_legs(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $tour = Tour::factory()->create(['is_active' => true]);
        TourLeg::factory()->count(2)->create(['tour_id' => $tour->id]);

        $response = $this->actingAs($user)->get(route('tours.show', $tour));

        $response->assertOk();
        $response->assertSee($tour->name);
    }

    public function test_pilot_can_view_their_booked_tours(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $tour = Tour::factory()->create(['is_active' => true, 'name' => 'Pacific Island Hopper']);
        TourBooking::factory()->create(['tour_id' => $tour->id, 'pilot_id' => $user->id, 'status' => 'booked']);

        $response = $this->actingAs($user)->get(route('tours.my'));

        $response->assertOk();
        $response->assertSee('Pacific Island Hopper');
    }

    public function test_admin_can_remove_a_leg_from_a_tour(): void
    {
        $admin = User::factory()->admin()->create();
        $tour = Tour::factory()->create();
        $leg = TourLeg::factory()->create(['tour_id' => $tour->id]);

        $this->actingAs($admin)
            ->delete(route('admin.tours.legs.destroy', [$tour, $leg]))
            ->assertRedirect();

        $this->assertDatabaseMissing('tour_legs', ['id' => $leg->id]);
    }

    public function test_a_leg_cannot_be_removed_through_another_tour(): void
    {
        $admin = User::factory()->admin()->create();
        $tour = Tour::factory()->create();
        $otherTour = Tour::factory()->create();
        $leg = TourLeg::factory()->create(['tour_id' => $otherTour->id]);

        $this->actingAs($admin)
            ->delete(route('admin.tours.legs.destroy', [$tour, $leg]))
            ->assertNotFound();

        $this->assertDatabaseHas('tour_legs', ['id' => $leg->id]);
    }

    public function test_a_pilot_can_rebook_a_tour_they_cancelled(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $tour = Tour::factory()->create(['is_active' => true]);

        // The table holds one row per pilot and tour, so re-booking has to
        // reuse the cancelled row instead of inserting a duplicate.
        TourBooking::factory()->create([
            'tour_id' => $tour->id,
            'pilot_id' => $user->id,
            'status' => TourBooking::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);

        $this->actingAs($user)->post(route('tours.book', $tour))->assertSessionHas('success');

        $this->assertDatabaseCount('tour_bookings', 1);
        $this->assertDatabaseHas('tour_bookings', [
            'tour_id' => $tour->id,
            'pilot_id' => $user->id,
            'status' => TourBooking::STATUS_BOOKED,
            'completed_at' => null,
        ]);
    }

    public function test_admin_can_deactivate_a_tour_from_the_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        $origin = Airport::factory()->create();
        $destination = Airport::factory()->create();
        $tour = Tour::factory()->create([
            'origin_airport_id' => $origin->id,
            'destination_airport_id' => $destination->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.tours.update', $tour), [
            'name' => $tour->name,
            'price_cents' => 15000,
            'origin_airport_id' => $origin->id,
            'destination_airport_id' => $destination->id,
            'is_active' => '0',
        ])->assertRedirect();

        $this->assertFalse((bool) $tour->fresh()->is_active);
    }
}
