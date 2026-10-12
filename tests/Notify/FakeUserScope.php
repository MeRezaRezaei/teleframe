<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use Throwable;

/**
 * Offline stand-in for a live `UserAccountScope` (MTProto, user account).
 *
 * Records every `call(string, array)` the user-scope transport makes —
 * method name, params, and the resulting order — so a test can assert the
 * TRANSLATION (Bot-API `chat_id` → MTProto `peer`, `text` → `message`, …)
 * without a socket, a session string or a credential. `failWith()` arms
 * the next N calls to throw, which is how the rate-limit path is driven:
 * the tests hand the policy a real `FloodWaitException` / a real Bot-API
 * 429 `TelegramException` and assert on what the POLICY did with it, not
 * on what a fixture claims Telegram would send.
 *
 * Never dials anything and never holds a session — the seam the transport
 * is given, exactly as `RelayTerminal` receives `AccountRelay::scope()`.
 */
final class FakeUserScope
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    /** How many times the resolver that produced this scope was invoked. */
    public int $resolutions = 0;

    /** @var list<Throwable> thrown, one per armed call, in order */
    private array $armed = [];

    /** @var list<array<string, mixed>> the reply each call returns, in order */
    private array $replies = [];

    /**
     * Arm the next $count calls to throw $throwable (re-arming the same
     * instance is fine — a rate limit is the same error raised again).
     */
    public function failWith(Throwable $throwable, int $count = 1): self
    {
        for ($i = 0; $i < $count; $i++) {
            $this->armed[] = $throwable;
        }

        return $this;
    }

    /** Queue the reply the next call returns. */
    public function replyWith(array $reply): self
    {
        $this->replies[] = $reply;

        return $this;
    }

    /** The resolver a transport can be given: hands over this scope, counting invocations. */
    public function resolver(): \Closure
    {
        return function (): self {
            $this->resolutions++;

            return $this;
        };
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];

        if ($this->armed !== []) {
            throw array_shift($this->armed);
        }

        return array_shift($this->replies) ?? ['ok' => true, '_' => 'updates'];
    }

    /** The methods this scope was asked to call, in order. */
    public function methods(): array
    {
        return array_map(static fn (array $call): string => $call['method'], $this->calls);
    }

    /** The params of the single call this scope received. */
    public function onlyParams(): array
    {
        if (count($this->calls) !== 1) {
            throw new \RuntimeException('expected exactly one call, got '.count($this->calls));
        }

        return $this->calls[0]['params'];
    }
}
