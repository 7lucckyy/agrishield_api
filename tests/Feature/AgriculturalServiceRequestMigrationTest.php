<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

it('can be applied when the agricultural service requests table already exists', function () {
    Schema::shouldReceive('hasTable')
        ->once()
        ->with('agricultural_service_requests')
        ->andReturnTrue();
    Schema::shouldReceive('create')->never();

    /** @var Migration $migration */
    $migration = require database_path('migrations/2026_10_11_161527_create_agricultural_service_requests_table.php');

    $migration->up();
});
