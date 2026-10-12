<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\AccountLifecycle;

/**
 * Account lifecycle event — an account entering the managed set.
 *
 * Plain DTO following the RoutingSettingsChanged pattern (public readonly
 * fields, a change-kind, plain-PHP constructible, dispatchable through any
 * Illuminate\Contracts\Events\Dispatcher). The kind is ON the payload —
 * 'account'|'bot' — so subscribers react without type-switching; BotAdded
 * is a typed alias that forces kind='bot'.
 *
 * change is the lifecycle change-kind the observer published it with:
 * 'added' (created) or 'changed' (updated — re-announced so a daemon can
 * re-read the row's enabled flag without a restart).
 */
class AccountAdded
{
    public const KIND_ACCOUNT = 'account';

    public const KIND_BOT = 'bot';

    public function __construct(
        public readonly int $accountId,
        public readonly string $kind = self::KIND_ACCOUNT,
        public readonly ?string $label = null,
        public readonly string $change = 'added',
    ) {}

    public function accountId(): int
    {
        return $this->accountId;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function isBot(): bool
    {
        return $this->kind === self::KIND_BOT;
    }
}
