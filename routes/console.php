<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('farming:retry-failed-registrations')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

foreach (['weather', 'crop_health', 'water_stress', 'soil_moisture', 'irrigation_advisory'] as $type) {
    Schedule::command("farming:sync {$type}")
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->onOneServer();
}

Schedule::command('farming:sync pest_forewarning')->weekly()->withoutOverlapping()->onOneServer();
Schedule::command('farming:sync soil_health')->dailyAt('03:00')->withoutOverlapping()->onOneServer();
Schedule::command('farming:purge-expired-artifacts')->dailyAt('04:00')->withoutOverlapping()->onOneServer();
