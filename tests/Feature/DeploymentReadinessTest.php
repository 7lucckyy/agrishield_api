<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\ProductionProviderGuard;
use LogicException;

test('production rejects fake agricultural providers', function () {
    $guard = new ProductionProviderGuard;

    expect(fn () => $guard->ensureSafe('production', [
        'farming' => 'fake',
        'diagnosis' => 'openai',
        'voice_assistance' => 'n_atlas',
    ]))->toThrow(LogicException::class, 'farming');
});

test('non-production environments may opt into fake providers', function () {
    $guard = new ProductionProviderGuard;

    $guard->ensureSafe('testing', [
        'farming' => 'fake',
        'diagnosis' => 'fake',
        'voice_assistance' => 'fake',
    ]);

    expect(true)->toBeTrue();
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
