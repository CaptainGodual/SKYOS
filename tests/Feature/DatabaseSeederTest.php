<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_no_demo_data_and_points_to_the_admin_command(): void
    {
        Artisan::call('db:seed');

        $output = Artisan::output();

        $this->assertStringContainsString('nothing to seed', $output);
        $this->assertStringContainsString('app:create-admin', $output);
        $this->assertDatabaseCount('users', 0);
    }
}
