<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\AccountLifecycle;

/**
 * Typed alias of AccountAdded for a 'bot' entering the managed set — the
 * same DTO with kind forced to 'bot' on the payload. Subscribers may listen
 * on either class; instanceof BotAdded also satisfies AccountAdded.
 */
final class BotAdded extends AccountAdded
{
    public function __construct(
        int $accountId,
        ?string $label = null,
        string $change = 'added',
    ) {
        parent::__construct($accountId, self::KIND_BOT, $label, $change);
    }
}
