<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\AccountLifecycle;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Laravel\Models\TfAccount;

/**
 * Account lifecycle observer (Cycle-4 RFC, sub-item 2) — the "hot event"
 * half of the DB-backed account registry.
 *
 * Hooks TfAccount's Eloquent lifecycle (wired by the model's own booted(),
 * NOT the provider — see TfAccount): after create/update/delete it (1)
 * publishes a best-effort reload signal on StreamSchema::RELOAD_CHANNEL —
 * kind 'accounts', payload {account_id, change} — so listening daemons
 * re-read tf_accounts without a restart, and (2) dispatches the lifecycle
 * events AccountAdded / AccountRemoved (BotAdded alias when kind='bot').
 *
 * The bus redis is nullable-cache style (mirrors RoutingSettingsObserver):
 * a host with no working bus redis still gets every lifecycle event; only
 * the reload publish is skipped — and even with a live redis, a
 * RuntimeException on publish is swallowed so an account write is never a
 * hard failure. When no redis instance is injected, the CURRENT container's
 * RedisConnectionContract binding is resolved best-effort at publish time,
 * so a host rebinding the bus picks the change up without re-boot.
 */
final class AccountLifecycleObserver
{
    public const RELOAD_KIND = 'accounts';

    public const CHANGE_ADDED = 'added';

    public const CHANGE_CHANGED = 'changed';

    public const CHANGE_REMOVED = 'removed';

    /**
     * @param  mixed  $redis  An explicit bus double (tests). When null the
     *                        container-resolved RedisConnectionContract binding is used
     *                        best-effort at publish time — never required to construct.
     */
    public function __construct(
        private readonly mixed $redis = null,
        private readonly ?Dispatcher $events = null,
    ) {}

    public function created(TfAccount $account): void
    {
        $this->sync($account, self::CHANGE_ADDED);
    }

    public function updated(TfAccount $account): void
    {
        $this->sync($account, self::CHANGE_CHANGED);
    }

    public function deleted(TfAccount $account): void
    {
        $this->publish($account, self::CHANGE_REMOVED);
        $this->emit($account, self::CHANGE_REMOVED);
    }

    private function sync(TfAccount $account, string $change): void
    {
        $this->publish($account, $change);
        $this->emit($account, $change);
    }

    /**
     * Best-effort reload publish: never throws. A host without a working
     * bus redis simply skips the signal — events still fire.
     */
    private function publish(TfAccount $account, string $change): void
    {
        $redis = $this->redis instanceof RedisConnectionContract
            ? $this->redis
            : self::busRedis();

        if ($redis === null) {
            return;
        }

        try {
            $redis->publish(StreamSchema::RELOAD_CHANNEL, json_encode([
                'kind' => self::RELOAD_KIND,
                'account_id' => (int) $account->id,
                'change' => $change,
            ], JSON_THROW_ON_ERROR));
        } catch (\Throwable) {
            // Bus redis down mid-publish (predis ConnectionException, phpredis
            // RedisException, …) — the reload signal is best-effort; an account
            // write must never hard-fail on the bus (W-13).
        }
    }

    private function emit(TfAccount $account, string $change): void
    {
        if ($this->events === null) {
            return;
        }

        $accountId = (int) $account->id;
        $kind = (string) $account->kind;
        $label = (string) $account->label;

        if ($change === self::CHANGE_REMOVED) {
            $this->events->dispatch(new AccountRemoved($accountId, $kind, $label, $change));

            return;
        }

        $this->events->dispatch($kind === TfAccount::KIND_BOT
            ? new BotAdded($accountId, $label, $change)
            : new AccountAdded($accountId, TfAccount::KIND_ACCOUNT, $label, $change));
    }

    /**
     * Container-resolved bus redis, guarded: a host with no redis service
     * (or an unbound contract) resolves to null and publish() is skipped.
     */
    private static function busRedis(): ?RedisConnectionContract
    {
        try {
            return Container::getInstance()->make(RedisConnectionContract::class);
        } catch (\Throwable) {
            return null;
        }
    }
}
