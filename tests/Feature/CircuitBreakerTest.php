<?php

declare(strict_types=1);

use App\Enums\CircuitState;
use App\Models\IntegrationAccount;
use App\Services\Integration\CircuitBreaker;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::store('array')->flush();
});

test('the circuit opens after five consecutive provider failures', function () {
    $account = IntegrationAccount::factory()->fakeProvider()->create();
    $breaker = app(CircuitBreaker::class);

    foreach (range(1, 4) as $attempt) {
        expect($breaker->recordFailure('fake'))->toBe(CircuitState::Closed);
    }

    expect($breaker->recordFailure('fake'))->toBe(CircuitState::Open)
        ->and($breaker->state('fake'))->toBe(CircuitState::Open)
        ->and($breaker->allowsRequest('fake'))->toBeFalse()
        ->and($account->refresh()->consecutive_failures)->toBe(5)
        ->and($account->circuit_state)->toBe(CircuitState::Open);
});

test('an open circuit half opens after cooldown and closes after a successful probe', function () {
    $account = IntegrationAccount::factory()->fakeProvider()->create();
    $breaker = app(CircuitBreaker::class);
    $breaker->open('fake');

    $this->travel(61)->seconds();

    expect($breaker->allowsRequest('fake'))->toBeTrue()
        ->and($breaker->state('fake'))->toBe(CircuitState::HalfOpen)
        ->and($breaker->allowsRequest('fake'))->toBeFalse();

    $breaker->recordSuccess('fake');

    expect($breaker->state('fake'))->toBe(CircuitState::Closed)
        ->and($breaker->allowsRequest('fake'))->toBeTrue()
        ->and($account->refresh()->consecutive_failures)->toBe(0)
        ->and($account->circuit_opened_at)->toBeNull();
});

test('a failed half open probe reopens the circuit and restarts cooldown', function () {
    $account = IntegrationAccount::factory()->fakeProvider()->create();
    $breaker = app(CircuitBreaker::class);
    $breaker->open('fake');
    $this->travel(61)->seconds();
    expect($breaker->allowsRequest('fake'))->toBeTrue();

    $breaker->recordFailure('fake');

    expect($breaker->state('fake'))->toBe(CircuitState::Open)
        ->and($breaker->allowsRequest('fake'))->toBeFalse()
        ->and($account->refresh()->circuit_state)->toBe(CircuitState::Open);
});
