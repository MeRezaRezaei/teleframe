<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\AccountLifecycle;

/**
 * Account lifecycle event — an account/bot leaving the managed set.
 *
 * Plain DTO mirroring AccountAdded; carries the same read-only fields plus
 * the kind on the payload, so a 'bot' removal is distinguishable without a
 * second event class. change is 'removed'.
 */
final class AccountRemoved
{
    public const KIND_ACCOUNT = AccountAdded::KIND_ACCOUNT;

    public const KIND_BOT = AccountAdded::KIND_BOT;

    public function __construct(
        public readonly int $accountId,
        public readonly string $kind = self::KIND_ACCOUNT,
        public readonly ?string $label = null,
        public readonly string $change = 'removed',
    ) {}

    public function accountId(): int
    {
        return $this->accountId;
    }

    public function kind(): string
    {
        return $this->kind;
    }
}
