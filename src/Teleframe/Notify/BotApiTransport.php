<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;

/**
 * BotApiTransport — the shipped default transport: the notification core
 * over the Bot API, through the engine's own `BotClient`.
 *
 * It is a thin adapter, on purpose. `BotClient::call(string, array): array`
 * already IS the contract's shape, so nothing is translated, nothing is
 * cached, and no failure is intercepted: the Bot API's own error becomes
 * the exception the rate-limit policy classifies (a 429 stays a 429, with
 * its "retry after N" description intact — the whole reason the policy
 * reads that string).
 *
 * The point of the class existing at all is symmetry: `UserScopeTransport`
 * is the other half of `NotificationSender`, and "which transport" is then
 * a constructor argument instead of a rewrite of the send process. It also
 * makes the bot path injectable in tests without a `BotClient` subclass
 * when a stub is easier than a fake client.
 */
final class BotApiTransport implements NotificationTransportContract
{
    public function __construct(
        private readonly BotClient $bot,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params = []): array
    {
        return $this->bot->call($method, $params);
    }

    /** Log-safe label — no token, ever. */
    public function describe(): string
    {
        return 'bot-api';
    }
}
