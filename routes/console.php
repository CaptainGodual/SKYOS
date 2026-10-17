<?php

use App\Models\ApiToken;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:reset-flight-routes')->hourly();

// ACARS tokens expire after 24 hours; without this they would pile up forever.
Schedule::call(fn () => ApiToken::query()->where('expires_at', '<', now())->delete())
    ->daily()
    ->description('Delete expired ACARS API tokens');
