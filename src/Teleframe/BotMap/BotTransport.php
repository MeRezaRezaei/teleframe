<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\BotMap;

/**
 * Bot-on-bot / user-to-bot transport (Phase 5f): the thin seam between bot
 * worlds. A handler or another bot invokes a registered bot's methods through
 * here instead of reaching into the registry directly, so cross-bot traffic
 * flows through exactly one named indirection — resolvable, mappable, and
 * swappable in tests.
 */
final class BotTransport
{
    public function __construct(
        private BotMap $map,
    ) {
    }

    /**
     * Invoke $method on the named bot.
     *
     * @param string $bot registered bot name
     * @param string $method Bot API method (e.g. 'sendMessage')
     * @param array<string, mixed> $params
     * @return array<string, mixed> BotClient response
     */
    public function invoke(string $bot, string $method, array $params = []): array
    {
        return $this->map->for($bot)->command($method, $params);
    }

    /**
     * Resolve a bot for chained, multi-call cross-bot work.
     */
    public function for(string $bot): BotEntry
    {
        return $this->map->for($bot);
    }
}