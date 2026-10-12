<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use Throwable;

/**
 * Recording stand-in for a live MTProto user-account scope, for the notify
 * module's shared lazy scope holder and the MTProto document uploader.
 *
 * The difference from `FakeUserScope` is the shape of the question it lets a
 * test ask: `LazyUserScope` is about WHEN a scope comes into being and how
 * many times it was dialled for, so this double counts resolver invocations
 * on top of the calls themselves, and answers with a scope whose `call()`
 * surface is deliberately narrow. `replyWith()` / `failWith()` are the same
 * two levers `FakeUserScope` offers — queue the reply, arm the throwable —
 * because a part-upload failure must be driven with a real error, not with
 * a fixture's claim about what Telegram would do.
 *
 * Never dials anything and never holds a session: this is the seam
 * `MtProtoDocumentUploader` is handed, exactly as `UserScopeTransport`
 * receives `AccountRelay::scope()`.
 */
final class FakeScopeDialer
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

    /** The resolver `LazyUserScope` can be given: hands over this scope, counting invocations. */
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

        return array_shift($this->replies) ?? ['ok' => true, '_' => 'Bool'];
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
