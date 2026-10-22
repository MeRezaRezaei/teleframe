<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * MemoryPassportWire (Cycle 18 W3) — the OFFLINE half of the wire seam, the
 * CursorStoreContract memory-implementation precedent
 * (`MemoryCursorStore`, `MemoryNonceRegistry`, `MemoryBindingWriter`): the same
 * contract with scripted answers and a recorded dispatch log.
 *
 * A scripted answer may be a response array OR a Throwable. The Throwable case
 * is the point: it is how a test reproduces a REAL Telegram refusal
 * (`new RpcErrorException('PASSWORD_REQUIRED', 400)`) and pins that the
 * orchestrator routes it to the PasswordConfirmer rather than dropping it.
 *
 * Two loud, non-silent behaviours:
 * - an unscripted method throws (`script exhausted`) instead of returning a
 *   fabricated empty response, so a missing step fails the test visibly;
 * - a repeated method CONSUMES the next scripted step, so ordering bugs show.
 *
 * `dispatched()` is the assertion surface a test reads to pin that
 * `auth.importAuthorization` left with the OFFICIAL id (see
 * PassportWireContract::OFFICIAL_IDS) and with exactly the documented params.
 *
 * Plain PHP, no container. Zero-regex.
 */
final class MemoryPassportWire implements PassportWireContract
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    private array $dispatched = [];

    /** @var array<string, int> per-method script cursor — one sequence per method */
    private array $cursor = [];

    /**
     * A LIST per method, not a single answer: `call()` advances a per-method
     * cursor, so the second call to the same method consumes the next step and
     * an ordering bug shows instead of silently replaying step one.
     *
     * @param  array<string, list<array<string, mixed>|\Throwable>>  $answers  per-method scripted
     *                                                                         steps, in call order for that method; a Throwable step is thrown instead of returned
     */
    public function __construct(private readonly array $answers = []) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params = []): array
    {
        $this->dispatched[] = ['method' => $method, 'params' => $params];

        $scripted = $this->answers[$method] ?? null;
        if (! is_array($scripted)) {
            // Loud, never a fabricated empty response.
            throw new PassportLoginException(
                'MemoryPassportWire has no scripted answer for "'.$method.'"'
                .' (offline dispatch refused — no silent empty response)'
            );
        }

        $at = $this->cursor[$method] ?? 0;
        $this->cursor[$method] = $at + 1;

        $step = $scripted[$at] ?? null;

        if ($step instanceof \Throwable) {
            throw $step;
        }

        if (is_array($step)) {
            return $step;
        }

        throw new PassportLoginException(
            'MemoryPassportWire script exhausted for "'.$method.'" (offline dispatch refused)'
        );
    }

    /**
     * Every dispatch this seam saw, in order.
     *
     * @return list<array{method: string, params: array<string, mixed>}>
     */
    public function dispatched(): array
    {
        return $this->dispatched;
    }

    /**
     * The dotted method names dispatched so far, in order.
     *
     * @return list<string>
     */
    public function methodsCalled(): array
    {
        $methods = [];
        foreach ($this->dispatched as $call) {
            $methods[] = $call['method'];
        }

        return $methods;
    }

    public function dispatchCount(): int
    {
        return count($this->dispatched);
    }

    /**
     * The first dispatch of a method, or null when it never happened.
     *
     * @return array{method: string, params: array<string, mixed>}|null
     */
    public function firstDispatchOf(string $method): ?array
    {
        foreach ($this->dispatched as $call) {
            if ($call['method'] === $method) {
                return $call;
            }
        }

        return null;
    }
}
