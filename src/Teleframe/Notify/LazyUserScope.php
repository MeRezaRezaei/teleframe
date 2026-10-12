<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Closure;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * LazyUserScope — the ONE memoized MTProto user-account scope the notify
 * module's user-scope seams share.
 *
 * `UserScopeTransport` needs an account scope for the send itself, and its
 * `uploader` seam needs one to upload the rendered report. Resolved twice
 * they would be two accounts' worth of bookkeeping for one notification, or
 * — worse — a second dial for a scope that is already live. So the scope
 * lives HERE, once, and both seams read it from here.
 *
 * The laziness is the whole point and it is enforced by construction: the
 * constructor takes a `callable(): object` and STORES it. It does not
 * invoke it, so binding a notifier can never dial Telegram from a
 * container factory, at boot, for a notifier that may never send. This is
 * the posture `RelayTerminal`, `AccountRelay` and `UserScopeTransport`
 * already hold; `resolved()` exists so it is assertable rather than
 * asserted-in-a-comment.
 *
 * The refusal is equally the point. Every failure — no resolver bound, a
 * resolver that throws, one that answers with something that is not an
 * object, an object with no `call(string, array)` — is a LOUD
 * `NotifyException` naming what was wrong, logged at error and rethrown.
 * The notify module exists so that a lost alert reaches a human, so a
 * broken scope seam is never a silent no-op: a caller that swallows this
 * exception has silently stopped notifying, which is the one outcome this
 * module is built to make impossible.
 *
 * Plain-PHP constructible: the constructor stores a closure and a logger
 * and touches nothing. No container, no network, no clock, no session
 * string — the resolver hands over an already-built scope, exactly as
 * `RelayFactory`/`RelaySessionResolver` do for the relay seam.
 *
 * HONEST LIMIT: the tests drive a recording double through this holder and
 * prove the laziness, the memoization and the refusals. No test here opens a
 * socket, and no fixture is a capture from a live account.
 */
final class LazyUserScope
{
    /** @var (Closure(): mixed)|null */
    private readonly ?Closure $resolver;

    private bool $resolved = false;

    private mixed $scope = null;

    private int $resolutions = 0;

    /**
     * @param  (callable(): mixed)|null  $scopeResolver  production: a host's lazily built
     *                                                   UserAccountScope (e.g. over the vault /
     *                                                   RelayFactory), injected so this holder
     *                                                   stays plain-PHP and never dials on
     *                                                   construction. null = unconfigured, and
     *                                                   the FIRST use refuses loudly — a nullable
     *                                                   parameter is what makes "unconfigured" a
     *                                                   state the class can actually hold rather
     *                                                   than a case a host has to fake.
     * @param  LoggerInterface|null  $logger  failures logged at error and ALWAYS rethrown
     */
    public function __construct(
        ?callable $scopeResolver = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->resolver = $scopeResolver === null ? null : Closure::fromCallable($scopeResolver);
    }

    /**
     * Whether the account scope has been resolved. False straight after
     * construction — the "never dials on construct" proof, assertable.
     */
    public function resolved(): bool
    {
        return $this->resolved;
    }

    /**
     * How many times the underlying resolver actually RAN. One after the
     * first use and one forever after, which is the memoization made
     * measurable: a host can assert that a second send did not dial again.
     */
    public function resolutions(): int
    {
        return $this->resolutions;
    }

    /**
     * The live account scope, resolved on FIRST USE and memoized for the
     * life of this holder, or a loud `NotifyException`. Construction never
     * resolves: a scope is a session-backed client and a notifier that has
     * not sent yet must not have dialled anything.
     *
     * @throws NotifyException when nothing can resolve a scope, when the resolver throws or answers with a non-object, or when the resolved object has no call(string, array)
     */
    public function scope(): object
    {
        if (! $this->resolved) {
            $this->scope = $this->resolve();

            $this->resolved = true;
        }

        /** @var object $scope */
        $scope = $this->scope;

        return $scope;
    }

    /**
     * One resolution attempt, with every failure turned into the loud,
     * named refusal the notify module's contract demands. A failed attempt
     * leaves the holder unresolved, so a host that fixes its wiring and
     * retries can resolve for real.
     *
     * @throws NotifyException
     */
    private function resolve(): object
    {
        if ($this->resolver === null) {
            $this->logger?->error('teleframe.notify: no MTProto user scope bound to the notify scope holder');

            throw new NotifyException(
                'teleframe.notify: no MTProto user scope bound — inject a resolver that returns a live UserAccountScope (e.g. fn () => app(TeleframeClient::class)->user()). Refusing rather than silently dropping the notification.',
            );
        }

        $this->resolutions++;

        try {
            $scope = ($this->resolver)();
        } catch (NotifyException $e) {
            $this->logger?->error('teleframe.notify: the MTProto user-scope resolver failed', ['exception' => $e]);

            throw $e;
        } catch (Throwable $e) {
            $this->logger?->error('teleframe.notify: the MTProto user-scope resolver failed', ['exception' => $e]);

            throw new NotifyException(
                'teleframe.notify: no MTProto user scope bound — the resolver threw '.$e::class.': '.$e->getMessage().'. Refusing rather than silently dropping the notification.',
                0,
                $e,
            );
        }

        if (! is_object($scope)) {
            $this->logger?->error('teleframe.notify: the MTProto user-scope resolver answered with a non-object', [
                'resolved' => get_debug_type($scope),
            ]);

            throw new NotifyException(sprintf(
                'teleframe.notify: the MTProto user-scope resolver returned %s, not an object — refusing the send.',
                get_debug_type($scope),
            ));
        }

        if (! method_exists($scope, 'call')) {
            $this->logger?->error('teleframe.notify: the bound MTProto user scope cannot carry a call', [
                'scope' => $scope::class,
            ]);

            throw new NotifyException(sprintf(
                'teleframe.notify: the bound MTProto user scope %s does not expose call(string, array) — refusing the send.',
                $scope::class,
            ));
        }

        return $scope;
    }
}
