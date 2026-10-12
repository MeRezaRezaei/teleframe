<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Auth;

use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use Throwable;

/**
 * Offline UserAccountScope double for the login-code tests: every call() is
 * recorded, and each method answers from a per-method script of results /
 * Throwables (the same shape as tests/Support/FakeUserScope, narrowed to the
 * auth methods). Nothing dials out and no container is booted.
 */
final class AuthScopeDouble extends UserAccountScope
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    /** @var array<string, list<array<string, mixed>|Throwable>> */
    private array $script;

    /** @var array<string, int> */
    private array $cursor = [];

    /**
     * @param  array<string, list<array<string, mixed>|Throwable>>  $script  per-method answers
     */
    public function __construct(array $script = [], private readonly int $userId = 42)
    {
        $this->script = $script;
        parent::__construct(
            new MTProtoClient(apiId: 1, apiHash: 'x', session: new SessionData(dcId: 2, authKey: 'k', userId: $userId)),
            new SessionData(dcId: 2, authKey: 'k', userId: $userId),
        );
    }

    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];

        $answers = $this->script[$method] ?? [];
        $i = $this->cursor[$method] ?? 0;
        $this->cursor[$method] = $i + 1;
        $answer = $answers[$i] ?? null;

        if ($answer instanceof Throwable) {
            throw $answer;
        }

        return is_array($answer) ? $answer : [];
    }

    /** How many times $method was invoked. */
    public function countCalls(string $method): int
    {
        $n = 0;
        foreach ($this->calls as $c) {
            if ($c['method'] === $method) {
                $n++;
            }
        }

        return $n;
    }

    /** @return array<string, mixed> params of the $nth call to $method */
    public function paramsOf(string $method, int $index = 0): array
    {
        foreach ($this->calls as $c) {
            if ($c['method'] === $method) {
                if ($index-- === 0) {
                    return $c['params'];
                }
            }
        }

        return [];
    }
}
