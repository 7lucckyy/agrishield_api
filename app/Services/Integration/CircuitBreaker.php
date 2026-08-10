<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Enums\CircuitState;
use App\Models\IntegrationAccount;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LogicException;

final class CircuitBreaker
{
    public function state(string $provider): CircuitState
    {
        $cachedState = Cache::store($this->cacheStore())->get($this->stateKey($provider));
        if (is_string($cachedState) && CircuitState::tryFrom($cachedState) !== null) {
            return CircuitState::from($cachedState);
        }

        $state = IntegrationAccount::query()
            ->where('provider', $provider)
            ->value('circuit_state');
        $resolved = $state instanceof CircuitState
            ? $state
            : (is_string($state) ? CircuitState::tryFrom($state) : null) ?? CircuitState::Closed;

        $this->cacheState($provider, $resolved);

        return $resolved;
    }

    public function allowsRequest(string $provider): bool
    {
        $state = $this->state($provider);
        if ($state === CircuitState::Closed) {
            return true;
        }

        if ($state === CircuitState::HalfOpen) {
            return false;
        }

        $openedAt = Cache::store($this->cacheStore())->get($this->openedAtKey($provider));
        if (! is_int($openedAt)) {
            $openedAt = IntegrationAccount::query()
                ->where('provider', $provider)
                ->value('circuit_opened_at')?->getTimestamp();
        }

        if (! is_int($openedAt) || now()->getTimestamp() < $openedAt + $this->cooldownSeconds()) {
            return false;
        }

        $probeLock = $this->lock(
            $this->probeKey($provider),
            $this->cooldownSeconds(),
        );

        if (! $probeLock->get()) {
            return false;
        }

        $this->mirrorState($provider, CircuitState::HalfOpen, preserveOpenedAt: true);
        $this->cacheState($provider, CircuitState::HalfOpen);

        return true;
    }

    public function recordFailure(string $provider): CircuitState
    {
        return DB::transaction(function () use ($provider): CircuitState {
            $account = $this->lockedAccount($provider);
            $account->consecutive_failures++;
            $account->last_failure_at = now();

            $nextState = $account->circuit_state === CircuitState::HalfOpen
                || $account->consecutive_failures >= $this->failureThreshold()
                    ? CircuitState::Open
                    : CircuitState::Closed;

            $account->circuit_state = $nextState;
            if ($nextState === CircuitState::Open) {
                $account->circuit_opened_at = now();
            }
            $account->save();

            $this->cacheState($provider, $nextState);
            Cache::store($this->cacheStore())->put(
                $this->failureKey($provider),
                $account->consecutive_failures,
                now()->addDay(),
            );
            if ($nextState === CircuitState::Open) {
                Cache::store($this->cacheStore())->put(
                    $this->openedAtKey($provider),
                    $account->circuit_opened_at?->getTimestamp(),
                    now()->addDay(),
                );
                $this->lock($this->probeKey($provider))->forceRelease();
            }

            return $nextState;
        });
    }

    public function recordSuccess(string $provider): void
    {
        DB::transaction(function () use ($provider): void {
            $account = $this->lockedAccount($provider);
            $account->circuit_state = CircuitState::Closed;
            $account->circuit_opened_at = null;
            $account->consecutive_failures = 0;
            $account->last_success_at = now();
            $account->save();
        });

        $this->cacheState($provider, CircuitState::Closed);
        Cache::store($this->cacheStore())->forget($this->failureKey($provider));
        Cache::store($this->cacheStore())->forget($this->openedAtKey($provider));
        $this->lock($this->probeKey($provider))->forceRelease();
    }

    public function open(string $provider): void
    {
        DB::transaction(function () use ($provider): void {
            $account = $this->lockedAccount($provider);
            $account->circuit_state = CircuitState::Open;
            $account->circuit_opened_at = now();
            $account->consecutive_failures = max($account->consecutive_failures, $this->failureThreshold());
            $account->save();
        });

        $this->cacheState($provider, CircuitState::Open);
        Cache::store($this->cacheStore())->put($this->openedAtKey($provider), now()->getTimestamp(), now()->addDay());
    }

    public function reset(string $provider): void
    {
        $this->recordSuccess($provider);
    }

    private function lockedAccount(string $provider): IntegrationAccount
    {
        $account = IntegrationAccount::query()
            ->where('provider', $provider)
            ->lockForUpdate()
            ->first();

        if ($account === null) {
            throw new LogicException('No integration account is configured for provider '.$provider.'.');
        }

        return $account;
    }

    private function mirrorState(string $provider, CircuitState $state, bool $preserveOpenedAt = false): void
    {
        $attributes = ['circuit_state' => $state];
        if (! $preserveOpenedAt) {
            $attributes['circuit_opened_at'] = $state === CircuitState::Open ? now() : null;
        }

        IntegrationAccount::query()->where('provider', $provider)->update($attributes);
    }

    private function cacheState(string $provider, CircuitState $state): void
    {
        Cache::store($this->cacheStore())->put($this->stateKey($provider), $state->value, now()->addDay());
    }

    private function lock(string $name, int $seconds = 0): Lock
    {
        $store = Cache::store($this->cacheStore())->getStore();
        if (! $store instanceof LockProvider) {
            throw new LogicException('The farming circuit cache store must support atomic locks.');
        }

        return $store->lock($name, $seconds);
    }

    private function stateKey(string $provider): string
    {
        return 'circuit:'.$provider.':state';
    }

    private function failureKey(string $provider): string
    {
        return 'circuit:'.$provider.':failures';
    }

    private function openedAtKey(string $provider): string
    {
        return 'circuit:'.$provider.':opened-at';
    }

    private function probeKey(string $provider): string
    {
        return 'circuit:'.$provider.':probe';
    }

    private function cacheStore(): string
    {
        return (string) config('farming.circuit.cache_store', 'redis');
    }

    private function failureThreshold(): int
    {
        return max(1, (int) config('farming.circuit.failure_threshold', 5));
    }

    private function cooldownSeconds(): int
    {
        return max(1, (int) config('farming.circuit.cooldown_seconds', 60));
    }
}
