<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Testing;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * The Fake RunningMode surface (gap friction I.3): feed fixture updates
 * through the REAL handler pipeline — real registry, real 26-line onion,
 * real echo eliminator — with only transport (redis/network) faked, per
 * the D2 boundary-mocking seam. No Laravel boot required: plain-PHP
 * constructible (the standalone smoke constructs it the same way).
 *
 * ``run()`` returns ``{dispatched: list<Update>, sent: list<...>}``:
 * every fixture frame that reached the terminal (matched, not
 * echo-eliminated) lands in ``dispatched``; explicit replies a handler
 * makes via the facade's ``send()`` land in ``sent`` (Q6 explicit path).
 */
final class FakeDispatcher
{
    /** @var list<Update> */
    public array $dispatched = [];

    /** @var list<array{account:int, send:array<string, mixed>}> */
    public array $sent = [];

    /**
     * @param list<array<mixed>> $fixtures each {update: array, account_id: int, ...}
     */
    public function __construct(
        private readonly array $fixtures,
        private readonly HandlerRegistry $registry,
        private readonly ContainerInterface $container,
        private readonly CacheInterface $sends,
        private readonly ?Pipeline $pipeline = null,
    ) {
    }

    /** @return array{dispatched: list<Update>, sent: list<array{account:int, send:array<string, mixed>}>} */
    public function run(): array
    {
        $recorder = function (Update $frame, callable $next): mixed {
            $this->dispatched[] = $frame;

            return $next($frame);
        };

        $dispatcher = new UpdateDispatcher(
            $this->registry,
            $this->pipeline ?? new Pipeline(),
            $this->container,
            $this->sends,
            [$recorder],
        );

        foreach ($this->fixtures as $fixture) {
            $dispatcher->dispatch($this->frameFrom((array) $fixture));
        }

        return ['dispatched' => $this->dispatched, 'sent' => $this->sent];
    }

    /** @param array<mixed> $fixture */
    private function frameFrom(array $fixture): Update
    {
        $update = (array) ($fixture['update'] ?? []);
        $accountId = (int) ($fixture['account_id'] ?? 0);
        $ts = isset($fixture['ts']) ? (int) $fixture['ts'] : null;

        return Update::fromBus($update, $accountId, $ts);
    }

    /**
     * The facade send stub (Q6): handlers reply explicitly here; the smoke
     * and tests record replies instead of touching redis.
     *
     * @param array<string, mixed> $send
     */
    public function send(int $accountId, array $send): int
    {
        $this->sent[] = ['account' => $accountId, 'send' => $send];

        return count($this->sent);
    }
}