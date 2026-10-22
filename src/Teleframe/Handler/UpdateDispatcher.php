<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Handler\Middleware\ReplayDedup;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * The one dispatch point both intake rows funnel through (friction I.1
 * dissolved): resolve the matched handler, run the middleware onion (echo
 * eliminator + replay dedup first by default), then invoke the handler with
 * the uprate and its matched ``%s`` arg.
 *
 * Handler invocation is DI-flavored minimism per Q4/Q18: parameters typed
 * ``Update``, ``ValidatedUpdate`` or ``TelegramContext`` receive the current
 * frame; any other class parameter is resolved from the PSR-11 delegate
 * container (null when unbound); a builtin ``string $arg`` parameter receives
 * the extracted sscanf token; untyped parameters receive the Update (closure
 * convenience). Native builtin parameters (``int``/``string``/``bool``/
 * ``float``/``array``/``mixed``/``?T``) are resolved from the update context
 * by name and coerced to the declared type; a value that cannot satisfy the
 * type (missing field, incompatible shape) skips the parameter instead of
 * fataling. v1 handlers return void (Q6); replies go through the facade, not
 * the return value.
 *
 * Forward discipline: the terminal (handler invocation) is deduped for the
 * LIFE OF THIS DISPATCHER INSTANCE — the same logical update is forwarded at
 * most once, so a retry or a re-delivered intake row cannot run a handler
 * twice. There is no run boundary and no clock: the registry is an in-process
 * set capped at ``FORWARD_CAP`` entries with FIFO eviction, and a host that
 * registers the dispatcher as a container singleton (the shipped wiring) sees
 * the window span the whole process. Identity: the mirrored root model for
 * event-path frames, the payload content hash for bus-path frames — see
 * ``forwardKey()`` for the exact key of each. The ReplayDedup middleware
 * still owns the time-bounded cross-run window; this guard is the
 * in-process guarantee on top of it, and it deliberately does NOT dedupe the
 * event path on the payload hash (the same mass-drop reasoning as
 * ``Middleware/ReplayDedup.php:38-53``). A frame that carries NO per-message
 * identity at all — a standalone ``TfUpdate``, whose real key is the shared
 * ``(account_id, 0, 0)`` slot every seq-less update of that constructor
 * overwrites — is forwarded WITHOUT being recorded, because a key that is
 * constant per (account, constructor) would starve the handler after the first
 * event. See ``forwardKey()`` for the full argument and the reproducing key
 * shapes.
 *
 * A handler/middleware failure is logged at error level (NullLogger by
 * default = silent) and ALWAYS rethrown; the optional ``$deadLetter`` seam
 * tags the trampled entry for the ``tg:stream:dead-letter`` stream with the
 * failure reason (same entry shape as IngestConsumer::deadLetter:
 * canonical fields + ``reason``/``error`` tags) — logging and dead-lettering
 * never swallow.
 */
final class UpdateDispatcher
{
    /** Failure reason tagged on a dead-lettered entry. */
    public const DEAD_LETTER_REASON = 'handler-failed';

    /** Cap on the per-instance forward registry (FIFO eviction, memory guard). */
    private const FORWARD_CAP = 1024;

    /** Count of updates dropped by the replay dedup middleware (spec 5a §3). */
    private int $seenReplays = 0;

    /** @var list<\Closure|EchoEliminator|ReplayDedup> */
    private array $middleware;

    private readonly LoggerInterface $logger;

    /** @var null|(\Closure(Update, string, string): void) update, reason, error */
    private readonly ?\Closure $deadLetter;

    /** @var array<string, true> per-run forward registry: identity => forwarded */
    private array $forwarded = [];

    private Deduplicator $deduplicator;

    /** Count of updates dropped by the cross-session deduplicator. */
    private int $seenDeduplications = 0;

    /**
     * @param  list<callable>  $middleware  extra middleware layered AFTER the
     *                                      echo eliminator and replay dedup (which stay first)
     * @param  LoggerInterface|null  $logger  PSR-3 log target; NullLogger by
     *                                        default (silent). Handler/middleware failures are logged at
     *                                        error level and ALWAYS rethrown — logging never swallows.
     * @param  (callable(Update, string, string): void)|null  $deadLetter
     *                                                                     optional seam that tags the update on ``tg:stream:dead-letter``
     *                                                                     with the failure reason (wire the host's Redis sink; entry shape
     *                                                                     = canonical ``{account_id, update, ts}`` fields + ``reason`` +
     *                                                                     ``error`` tags, matching IngestConsumer::deadLetter).
     */
    public function __construct(
        private readonly HandlerRegistry $registry,
        private readonly Pipeline $pipeline,
        private readonly ContainerInterface $container,
        private readonly CacheInterface $sends,
        array $middleware = [],
        int $dedupTtl = 60,
        ?LoggerInterface $logger = null,
        ?callable $deadLetter = null,
        ?CacheInterface $dedupCache = null,
        ?Deduplicator $deduplicator = null,
        ?Deduplicator $dedup = null,
        string $dedupScope = 'account',
        string $deduplicationScope = 'account',
        string $scope = 'account',
        ?int $deduplicationTtl = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->deadLetter = $deadLetter === null ? null : $deadLetter(...);
        $eliminator = new EchoEliminator($sends, new HandlerMatcher($registry));
        $replayDedup = new ReplayDedup($dedupCache ?? new InMemoryCache, $dedupTtl, function (): void {
            $this->seenReplays++;
        });
        $this->middleware = [$eliminator, $replayDedup, ...$middleware];

        $scopeAlias = $scope;
        $scope = $dedupScope;
        if ($dedupScope === 'account' && $deduplicationScope !== 'account') {
            $scope = $deduplicationScope;
        }
        if ($scope === '') {
            $scope = 'account';
        }
        if ($scopeAlias !== 'account' && $scopeAlias !== '') {
            $scope = $scopeAlias;
        }
        $resolvedDedup = $deduplicator ?? $dedup;
        $resolvedTtl = $dedupTtl;
        if ($deduplicationTtl !== null && $deduplicationTtl > 0) {
            $resolvedTtl = $deduplicationTtl;
        }
        if ($resolvedDedup === null) {
            $resolvedDedup = new Deduplicator($dedupCache ?? new InMemoryCache(), $resolvedTtl, $scope);
        }
        $this->deduplicator = $resolvedDedup;
    }

    public function seenReplays(): int
    {
        return $this->seenReplays;
    }

    public function seenDeduplications(): int
    {
        return $this->seenDeduplications;
    }

    public function getDeduplicator(): Deduplicator
    {
        return $this->deduplicator;
    }

    public function setDeduplicator(Deduplicator $deduplicator): void
    {
        $this->deduplicator = $deduplicator;
    }

    /**
     * Route and dispatch one update through the real pipeline. Returns the
     * handler result (null when no handler matched, an echo was eliminated,
     * a replay was deduped, or the update was already forwarded this run) —
     * absent a match, nothing is dispatched.
     */
    public function dispatch(Update $update): mixed
    {
        $match = (new HandlerMatcher($this->registry))->match($update->constructor());
        if ($match === null) {
            return null;
        }

        $handler = $match->handler;
        $arg = $match->arg();

        $terminal = function (Update $frame) use ($handler, $arg): mixed {
            if ($this->deduplicator->isDuplicate($frame)) {
                $this->seenDeduplications++;

                return null;
            }

            $key = $this->forwardKey($frame);
            if ($key !== null && isset($this->forwarded[$key])) {
                return null; // already forwarded once this processing run
            }
            if ($key !== null) {
                $this->rememberForwarded($key);
            }

            $context = new TelegramContext($frame);
            $delegate = $this->resolve($handler->handler);

            return $this->invoke($delegate, $frame, $context, $arg);
        };

        $runner = $this->pipeline->then($this->middleware, $terminal);

        try {
            return $runner($update);
        } catch (Throwable $e) {
            // Log, never swallow: caller's behavior is byte-for-byte the
            // pre-seam contract, a failing handler simply also reaches a
            // PSR-3 logger (NullLogger → no-op).
            $this->logger->error('teleframe.dispatch.failed', [
                'constructor' => $update->constructor(),
                'handler' => $match->handler::class,
                'account' => $update->accountId,
                'source' => $update->source,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            // Optional dead-letter seam: tag the entry with the failure
            // reason for the DL stream (same shape as IngestConsumer). Still
            // rethrows — the seam is a tag, not a swallow.
            if ($this->deadLetter !== null) {
                ($this->deadLetter)($update, self::DEAD_LETTER_REASON, $e->getMessage());
            }

            throw $e;
        }
    }

    /** Resolve a PSR-11-addressable handler to an invocable callable. */
    private function resolve(string|array|\Closure $handler): callable
    {
        if ($handler instanceof \Closure) {
            return $handler;
        }

        if (is_array($handler)) {
            [$class, $method] = $handler;
            /** @var object $instance */
            $instance = $this->container->get($class);

            return [$instance, $method];
        }

        /** @var object|string|array<mixed> $service */
        $service = $this->container->get($handler);

        if (is_array($service) || is_string($service)) {
            return [$service, '__invoke'];
        }

        return $service instanceof \Closure ? $service : [$service, '__invoke'];
    }

    private function invoke(callable $handler, Update $update, TelegramContext $context, ?string $arg = null): mixed
    {
        $args = [];

        foreach ($this->reflect($handler)->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $type instanceof \ReflectionNamedType && ! $type->isBuiltin()
                ? $type->getName()
                : null;

            if ($name === Update::class) {
                $args[] = $update;

                continue;
            }

            if ($name === ValidatedUpdate::class) {
                $args[] = new ValidatedUpdate($update);

                continue;
            }

            if ($name === TelegramContext::class) {
                $args[] = $context;

                continue;
            }

            if ($name !== null && $this->container->has($name)) {
                $args[] = $this->container->get($name);

                continue;
            }

            // Native builtin parameter (int/string/bool/float/array/mixed/?T,
            // incl. unions of builtins): resolve a matching update-context
            // value with scalar coercion; skip (never fatal) when the value
            // cannot satisfy the type.
            if ($this->isBuiltinParameter($type)) {
                $resolved = $this->builtinValue($parameter, $update, $arg);
                if ($resolved['ok']) {
                    $args[] = $resolved['value'];
                }

                continue;
            }

            if ($name === null && $parameter->getName() === 'arg' && $arg !== null) {
                $args[] = $arg;

                continue;
            }

            $args[] = $update;
        }

        return $handler(...$args);
    }

    /**
     * True when the declared type is (or a union of) builtin scalar/array
     * types — the parameters the dispatcher resolves from the update context
     * with coercion. Untyped parameters and class types are not builtin.
     */
    private function isBuiltinParameter(?\ReflectionType $type): bool
    {
        return $this->builtinMembers($type) !== [];
    }

    /**
     * The captured ``%s`` token, a matching raw update field, or the frame's
     * account/ts facts — coerced to the declared builtin type. ``ok`` is
     * false when no value can satisfy the type (the caller skips the
     * parameter rather than fataling).
     *
     * @return array{ok: bool, value: mixed}
     */
    private function builtinValue(\ReflectionParameter $parameter, Update $update, ?string $arg): array
    {
        $type = $parameter->getType();
        $coercions = [];
        foreach ($this->builtinMembers($type) as $member) {
            $coercions[] = $member;
        }
        if ($coercions === []) {
            return ['ok' => false, 'value' => null];
        }

        $value = $parameter->getName() === 'arg' && $arg !== null
            ? $arg
            : $this->fieldValue($parameter->getName(), $update);

        if ($value === null && $this->builtinsAllowsNull($type)) {
            return ['ok' => true, 'value' => null];
        }
        if ($value === null) {
            return ['ok' => false, 'value' => null];
        }

        foreach ($coercions as $member) {
            $coerced = $this->coerceBuiltin($value, $member->getName());
            if ($coerced['ok']) {
                return $coerced;
            }
        }

        return ['ok' => false, 'value' => null];
    }

    /**
     * @return list<\ReflectionNamedType> the declared builtin members (a
     *                                    named builtin type, or the builtin members of a union type)
     */
    private function builtinMembers(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            return $type->isBuiltin() ? [$type] : [];
        }

        if ($type instanceof \ReflectionUnionType) {
            $members = [];
            foreach ($type->getTypes() as $member) {
                if ($member instanceof \ReflectionNamedType && $member->isBuiltin()) {
                    $members[] = $member;
                }
            }

            return $members;
        }

        return [];
    }

    private function builtinsAllowsNull(?\ReflectionType $type): bool
    {
        return $type !== null && $type->allowsNull();
    }

    /**
     * Coerce one scalar/array value to a builtin type. ``ok`` is false when
     * the value's shape cannot satisfy the type (an array/object/resource
     * never coerces to a scalar; a non-array never coerces to ``array``).
     *
     * @return array{ok: bool, value: mixed}
     */
    private function coerceBuiltin(mixed $value, string $declared): array
    {
        if ($declared === 'mixed') {
            return ['ok' => true, 'value' => $value];
        }

        if ($declared === 'array') {
            return is_array($value)
                ? ['ok' => true, 'value' => $value]
                : ['ok' => false, 'value' => null];
        }

        if (is_array($value) || is_object($value) || is_resource($value)) {
            return ['ok' => false, 'value' => null];
        }

        return match ($declared) {
            'int' => ['ok' => true, 'value' => (int) $value],
            'float' => ['ok' => true, 'value' => (float) $value],
            'string' => ['ok' => true, 'value' => (string) $value],
            'bool' => ['ok' => true, 'value' => (bool) $value],
            default => ['ok' => false, 'value' => null],
        };
    }

    /**
     * The matching update-context value for one parameter name: the frame's
     * account/ts facts, or a raw update field under the exact name, the
     * snake_case form (``$userId`` → ``user_id``), or the lowercased form.
     * Returns null when no field matches.
     */
    private function fieldValue(string $name, Update $update): mixed
    {
        if ($name === 'account_id' || $name === 'accountId') {
            return $update->accountId;
        }
        if ($name === 'ts') {
            return $update->ts;
        }

        foreach ([$name, self::snakeCase($name), strtolower($name)] as $key) {
            if (array_key_exists($key, $update->array)) {
                return $update->array[$key];
            }
        }

        return null;
    }

    /** Zero-regex camelCase → snake_case (``userId`` → ``user_id``). */
    private static function snakeCase(string $name): string
    {
        $out = '';
        foreach (str_split($name) as $index => $char) {
            $out .= ($index > 0 && ctype_upper($char))
                ? '_'.strtolower($char)
                : strtolower($char);
        }

        return $out;
    }

    /**
     * Stable per-instance identity of one update.
     *
     * The event path (``Update::fromMirror``, ``source === 'event'``) is keyed
     * on the MIRRORED ROOT MODEL, never on ``updateId()``: since the
     * 2026-09-26 reachability fix the event path carries a non-empty
     * ``['_'] => ctor`` identity row (``Update::fromMirror()``), and no
     * mirror table has a ``created_at`` column, so ``updateId()`` collapses to
     * a per-account+constructor CONSTANT — keying on it forwarded the first
     * message of every account/constructor per process and silently dropped
     * every later one. The model key is the real per-message identity
     * (``tf_messages`` is keyed ``(account_id, id)``).
     *
     * This is the same collision ReplayDedup already refused to dedup on,
     * one layer up — see ``Middleware/ReplayDedup.php:38-53``, which skips
     * the event path for exactly this reason. The two must move together: a
     * guard added here without that one (or the reverse) re-arms the
     * mass-drop. They are not shareable — ReplayDedup is a TIME-BOUNDED
     * cross-run PSR-16 window keyed on the transport payload, this is an
     * in-process per-row guard keyed on stored truth — so the shape is
     * mirrored and cross-referenced rather than merged.
     *
     * A row's ATTRIBUTE hash is carried alongside the primary key so that a
     * legitimate EDIT of the same message (a second ``UpdateStored`` for the
     * same ``(account_id, id)`` with different content) is still forwarded;
     * only a re-delivery of the identical row collapses. The bus path keeps
     * its payload-hash identity, and a modelless frame falls back to a
     * serialization of the payload.
     *
     * A NULL RETURN MEANS "THIS ROW HAS NO PER-MESSAGE IDENTITY" and the guard
     * deliberately does not dedupe it (see the dispatcher's terminal). This is
     * the second half of the same defect, and it is NOT hypothetical: asking
     * the model for its real composite key instead of the Eloquent stand-in is
     * NECESSARY BUT NOT SUFFICIENT. ``TfUpdate`` overrides
     * ``$primaryKey = 'account_id'`` because composite keys have no Eloquent
     * equivalent (TfUpdate.php:32, and its own docblock at :14-17), and its
     * real key ``childKey()`` is ``(account_id, seq, position)``. But the
     * standalone (non-container) write path calls
     * ``UpdateIngestor::writeUpdate($payload, $accountId, $ctor)`` with the
     * default ``$seq = 0, $position = 0`` (UpdateIngestor.php:532, called at
     * :319), so EVERY standalone update of a given (account, constructor) is
     * stored at the SAME key ``(account_id, 0, 0)``. Verified by execution:
     * two distinct ``updateUserStatus`` events both yield ``7-0-0``. The
     * distinguishing facts (user_id, status) live in CHILD tables, which
     * ``getAttributes()`` never sees, so the attribute hash is identical too
     * and the escape hatch above cannot help either. The container path
     * (``writeUpdateContainer``) DOES thread a real seq/position per member,
     * which is exactly why 1ee8738d's tests pass — they exercise the
     * container path and never the standalone one.
     *
     * So the rule is: dedupe the event path only when the row's real key
     * actually distinguishes rows, and forward WITHOUT deduping when it does
     * not. Over-forwarding is the recoverable direction (ReplayDedup and the
     * ingestor's own upsert still stand between a genuine duplicate and a
     * double handler run); a constant key is the direction that silently
     * starves a handler forever. ``TfMessage`` has a real per-message id and
     * still collapses on re-delivery, which is asserted both ways in
     * ``tests/Handler/UpdateDispatcherEventIdentityTest.php``.
     *
     * The NULL-identity branch below is also why the modelless event frame is
     * NOT allowed to fall through to ``'u:'.updateId()``: over the same
     * ``['_'] => ctor`` row that key is the constant that was just removed
     * here, so the code SHAPE would re-arm this exact bug on any refactor. It
     * is currently unreachable (``Update::fromMirror`` always sets the model;
     * the only other ``new Update(...)`` on an event frame is
     * ``MenuRouter::frame()``, whose dispatch returns null earlier because the
     * array carries no ``callback_query``) — so returning null changes no
     * reachable behavior, it only removes the landmine.
     *
     * @return null|string the forward identity, or null when the frame
     *                     carries none and must not be deduped
     */
    private function forwardKey(Update $update): ?string
    {
        $model = $update->model;
        if ($update->source === 'event') {
            // Modelless event frame: see above — never key it on updateId().
            if ($model === null) {
                return null;
            }

            $key = $this->eventRowKey($model);

            return $key === null
                ? null
                : 'm:'.$update->accountId.':'.$model::class.'#'.$key
                    .':'.sha1(serialize($model->getAttributes()));
        }

        if ($update->array !== []) {
            try {
                return 'u:'.$update->updateId();
            } catch (\JsonException) {
                return 's:'.sha1($update->accountId."\0".($update->ts ?? '')."\0".serialize($update->array));
            }
        }

        if ($model !== null) {
            $key = $model->getKey();
            if ($key !== null) {
                return 'm:'.$update->accountId.':'.$model::class.'#'.$key;
            }

            return 'm:'.$update->accountId.':'.$model::class.'#'.sha1(serialize($model->getAttributes()));
        }

        return 'e:'.$update->accountId.':'.$update->source.':'.($update->ts ?? '');
    }

    /**
     * The real identity of a mirrored root row, or null when the row does not
     * have one.
     *
     * Asks the model for its COMPOSITE key where it publishes one
     * (``TfUpdate::childKey()`` → ``(account_id, seq, position)``), because the
     * Eloquent scalar ``getKey()`` is only a stand-in on those models. A
     * composite key is only accepted when it actually DISCRIMINATES: a key
     * whose every non-scope component is zero (``(account_id, 0, 0)``) is the
     * shared standalone slot that every seq-less update of that constructor
     * overwrites, so it identifies the SLOT and not the update, and returning
     * it would mass-drop exactly as the old constant did.
     *
     * A model with no ``childKey()`` (``TfMessage``) falls back to its scalar
     * key, which IS per-row there. A model with neither is a legacy Tl* row
     * and is keyed on its attribute hash, which is constant per constructor
     * for an update-shaped frame — so that case also declines to dedupe.
     *
     * @param  Model  $model  the mirrored root, always an Eloquent model
     */
    private function eventRowKey(Model $model): ?string
    {
        // childKey() is not on TfMirrorModel — it is published per model that
        // owns child fact tables (TfUpdate, TfUser, …), so this is a genuine
        // capability check, not a redundant one.
        if (method_exists($model, 'childKey')) {
            $parts = $model->childKey();
            $discriminating = false;
            foreach ($parts as $column => $value) {
                // The account scope is not a discriminator: it is already a
                // component of the enclosing key, and it is never zero for a
                // real row.
                if ((string) $column === 'account_id') {
                    continue;
                }
                if ((int) $value !== 0) {
                    $discriminating = true;
                }
            }

            return $discriminating ? implode('-', array_map('strval', $parts)) : null;
        }

        // No childKey(): the scalar key is the real identity (tf_messages is
        // keyed (account_id, id)). A zero key is no identity at all.
        $key = $model->getKey();
        if ($key !== null && (int) $key !== 0) {
            return (string) $key;
        }

        // No key at all: an unsaved/legacy row. Hashing the attributes would be
        // a per-constructor constant on the update path, which is the defect,
        // so decline to dedupe rather than invent one.
        return null;
    }

    /** Record a forwarded identity, evicting the oldest entry past the cap. */
    private function rememberForwarded(string $key): void
    {
        if (count($this->forwarded) >= self::FORWARD_CAP && ! isset($this->forwarded[$key])) {
            unset($this->forwarded[(string) array_key_first($this->forwarded)]);
        }
        $this->forwarded[$key] = true;
    }

    private function reflect(callable $handler): \ReflectionFunctionAbstract
    {
        if (is_array($handler)) {
            return new \ReflectionMethod($handler[0], $handler[1]);
        }

        if (is_string($handler) && str_contains($handler, '::')) {
            [$class, $method] = explode('::', $handler, 2);

            return new \ReflectionMethod($class, $method);
        }

        if (is_object($handler) && ! $handler instanceof \Closure) {
            return new \ReflectionMethod($handler, '__invoke');
        }

        return new \ReflectionFunction($handler);
    }
}
