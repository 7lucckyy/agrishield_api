<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\User;

test('production provider resolution cannot select a fake farming provider', function () {
    $originalEnvironment = app()->environment();
    $originalProvider = config('farming.provider');
    app()->instance('env', 'production');
    config()->set('farming.provider', 'fake');

    try {
        expect(fn () => app(FarmingInsightsProvider::class))
            ->toThrow(InvalidArgumentException::class, 'production farming provider');
    } finally {
        app()->instance('env', $originalEnvironment);
        config()->set('farming.provider', $originalProvider);
    }
});

test('an existing token stops working when its user is suspended', function () {
    $user = User::factory()->create();
    $token = $user->createToken('field phone');
    $user->forceFill(['status' => UserStatus::Suspended])->save();

    $this->withToken($token->plainTextToken)
        ->getJson('/api/v1/me')
        ->assertForbidden()
        ->assertJsonPath('error_code', 'account_suspended');

    expect($user->tokens()->count())->toBe(0);
});

test('private media disk can switch to a private S3-compatible backend', function () {
    $previous = $_ENV['PRIVATE_FILESYSTEM_DRIVER'] ?? null;
    $_ENV['PRIVATE_FILESYSTEM_DRIVER'] = 's3';

    try {
        $filesystems = require config_path('filesystems.php');
        expect($filesystems['disks']['private']['driver'])->toBe('s3')
            ->and($filesystems['disks']['private']['visibility'])->toBe('private')
            ->and($filesystems['disks']['private']['throw'])->toBeTrue();
    } finally {
        if ($previous === null) {
            unset($_ENV['PRIVATE_FILESYSTEM_DRIVER']);
        } else {
            $_ENV['PRIVATE_FILESYSTEM_DRIVER'] = $previous;
        }
    }
});
