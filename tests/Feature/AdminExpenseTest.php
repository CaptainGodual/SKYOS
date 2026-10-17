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
            'cost_cents' => 45000,
            'is_active' => true,
            'airport_ids' => $airports->pluck('id')->all(),
        ]);

        $response->assertRedirect(route('admin.expenses.index'));

        $expense = FlightExpense::query()->where('name', 'Ground Handling')->first();

        $this->assertNotNull($expense);
        $this->assertSame('Handling', $expense->category);
        $this->assertSame(45000, $expense->cost_cents);
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
            'cost_cents' => 25000,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.expenses.index'));

        $expense->refresh();

        $this->assertSame('Landing Fee (Peak)', $expense->name);
        $this->assertSame(25000, $expense->cost_cents);
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

    public function test_expense_requires_a_name_and_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.expenses.store'), [
            'cost_cents' => 100,
        ])->assertSessionHasErrors(['name', 'category']);
    }
}
