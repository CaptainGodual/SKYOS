<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\FlightExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_expense_with_airports(): void
    {
        $admin = User::factory()->admin()->create();
        $airports = Airport::factory()->count(2)->create();

        $response = $this->actingAs($admin)->post(route('admin.expenses.store'), [
            'name' => 'Ground Handling',
            'category' => 'Handling',
            'description' => 'Standard turnaround handling fee.',
            'cost_cents' => '450.00',
            'is_active' => '1',
            'airport_ids' => $airports->pluck('id')->all(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.expenses.index'));

        $expense = FlightExpense::query()->where('name', 'Ground Handling')->first();

        $this->assertNotNull($expense);
        $this->assertSame('Handling', $expense->category);
        $this->assertSame(45000, $expense->cost_cents, 'The dollar amount entered by the admin must be stored as cents.');
        $this->assertTrue($expense->is_active);
        $this->assertCount(2, $expense->airports);
    }

    public function test_admin_can_update_an_expense(): void
    {
        $admin = User::factory()->admin()->create();
        $expense = FlightExpense::factory()->create([
            'name' => 'Landing Fee',
            'category' => 'Landing',
            'cost_cents' => 10000,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.expenses.update', $expense), [
            'name' => 'Landing Fee (Peak)',
            'category' => 'Landing',
            'cost_cents' => '250',
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.expenses.index'));

        $expense->refresh();

        $this->assertSame('Landing Fee (Peak)', $expense->name);
        $this->assertSame(25000, $expense->cost_cents);
    }

    public function test_admin_can_deactivate_an_expense(): void
    {
        $admin = User::factory()->admin()->create();
        $expense = FlightExpense::factory()->create(['is_active' => true]);

        // The form pairs the checkbox with a hidden `is_active=0` input.
        $this->actingAs($admin)->put(route('admin.expenses.update', $expense), [
            'name' => $expense->name,
            'category' => $expense->category,
            'cost_cents' => '10',
            'is_active' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($expense->refresh()->is_active);
    }

    public function test_admin_can_clear_the_airports_an_expense_applies_to(): void
    {
        $admin = User::factory()->admin()->create();
        $expense = FlightExpense::factory()->create();
        $expense->airports()->attach(Airport::factory()->create());

        $this->assertCount(1, $expense->airports);

        $this->actingAs($admin)->put(route('admin.expenses.update', $expense), [
            'name' => $expense->name,
            'category' => $expense->category,
            'cost_cents' => '10',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertCount(0, $expense->refresh()->airports);
    }

    public function test_non_admin_cannot_create_an_expense(): void
    {
        $pilot = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);

        $this->actingAs($pilot)->post(route('admin.expenses.store'), [
            'name' => 'Sneaky Fee',
            'category' => 'Handling',
            'cost_cents' => 1,
        ])->assertForbidden();

        $this->assertDatabaseMissing('flight_expenses', ['name' => 'Sneaky Fee']);
    }

    public function test_non_admin_cannot_view_the_expense_pages(): void
    {
        $pilot = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);

        $this->actingAs($pilot)->get(route('admin.expenses.index'))->assertForbidden();
        $this->actingAs($pilot)->get(route('admin.expenses.create'))->assertForbidden();
    }

    public function test_expense_requires_a_name_and_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.expenses.store'), [
            'cost_cents' => 100,
        ])->assertSessionHasErrors(['name', 'category']);
    }

    public function test_expense_cost_cannot_be_negative(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.expenses.store'), [
            'name' => 'Refund',
            'category' => 'Handling',
            'cost_cents' => '-10',
            'is_active' => '1',
        ])->assertSessionHasErrors('cost_cents');

        $this->assertDatabaseMissing('flight_expenses', ['name' => 'Refund']);
    }

    public function test_expense_can_be_created_when_no_airports_are_selected(): void
    {
        $admin = User::factory()->admin()->create();

        // An empty picker leaves the `airport_ids[]` field blank, which must be
        // treated as "fleet-wide" rather than as a missing airport.
        $response = $this->actingAs($admin)->post(route('admin.expenses.store'), [
            'name' => 'Fuel Surcharge',
            'category' => 'Fuel',
            'cost_cents' => '50',
            'is_active' => '1',
            'airport_ids' => [''],
        ]);

        $response->assertSessionHasNoErrors();

        $expense = FlightExpense::query()->where('name', 'Fuel Surcharge')->first();

        $this->assertNotNull($expense);
        $this->assertSame(5000, $expense->cost_cents);
        $this->assertCount(0, $expense->airports);
    }

    public function test_create_form_renders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.expenses.create'))->assertOk();
    }

    public function test_edit_form_renders_with_the_selected_airports(): void
    {
        $admin = User::factory()->admin()->create();
        $expense = FlightExpense::factory()->create();
        $expense->airports()->attach(Airport::factory()->create(['identifier' => 'KJFK']));

        $this->actingAs($admin)
            ->get(route('admin.expenses.edit', $expense))
            ->assertOk()
            ->assertSee('KJFK');
    }
}
