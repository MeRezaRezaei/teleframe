<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

use Closure;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * The control-layer update stream for one vault account (the "control what
 * data is passing to the client" seam): pulls ONE committed update IDENTITY
 * from the injected reader — production: the bus consumer reading
 * tg:stream:updates / group teleclient — resolves it to the committed row
 * through the injected hydrator, wraps the row as an INBOUND RelayMessage,
 * runs it through the same RelayFilter onion as AccountRelay (snoop / rewrite
 * / drop), then hands the post-filter message to the publisher callable.
 *
 * THE READER RETURNS A KEY, NOT A DECODED UPDATE (D-044). It used to be
 * typed `Closure(): array<string, mixed>` and its return value was handed
 * straight into `new RelayMessage(INBOUND, 'update', $payload)`. That
 * contract cannot be satisfied by the bus: `tf_updates` stores ZERO JSON
 * (`2026_09_14_200030:16-18` — "Optional flag-gated fields become 1:1 child
 * tables (zero nullable, zero JSON)"), so the decoded update array is not
 * reconstructible from the committed row and a reader that returned one would
 * be returning data the system of record does not hold. It is verified
 * unwired today (every construction is in tests), which is exactly why the
 * trap was cheap to fix and expensive to discover: the first host to wire it
 * would get a silently empty relay.
 *
 * THE IDENTITY ROW IS THE PAYLOAD (D-044). `$hydrate` resolves the key to the
 * committed model and the RelayMessage carries that row's attributes, not the
 * raw wire payload. `raw:` stays null for the same reason it always was — this
 * leg was designed for a non-payload message. A host that needs raw payload
 * FIELDS (message text, peer, media) waits for S2's horizontal relations,
 * which do not exist yet; the type change is still correct either way because
 * it turns that silent empty relay into a loud error at the host's seam.
 *
 * Lifecycle mirrors a relay session: start() opens the presence record
 * (relay id "stream"), every live pulse refreshes the heartbeat via
 * registryTouch, stop() closes it. Plain PHP, no facades — reader, hydrator,
 * publisher and registry hooks are callables with no-op defaults, so the
 * class constructs in isolation and tests inject fakes; the live bus is
 * never dialed here.
 */
final class AccountUpdateStream
{
    /**
     * Pull ONE committed row's identity — the v2 bus entry's
     * `{account_id, seq, position}` key. null = stream drained / reader empty.
     *
     * @var (Closure(): (array<string, mixed>|null))|null
     */
    private readonly ?Closure $reader;

    /**
     * Resolve one committed key to its row (the host's own lookup — the
     * hydrated model is whatever this account's schema calls the update row;
     * this class requires only that it is an Eloquent Model).
     *
     * @var (Closure(array<string, mixed>): ?Model)|null
     */
    private readonly ?Closure $hydrate;

    /** @var (Closure(RelayMessage): void)|null sees the post-filter message */
    private readonly ?Closure $publisher;

    /** @var (Closure(int, string): void)|null RelaySessionRegistry::start seam */
    private readonly ?Closure $registryStart;

    /** @var (Closure(int): void)|null RelaySessionRegistry::stop seam */
    private readonly ?Closure $registryStop;

    /** @var (Closure(int): void)|null RelaySessionRegistry::touch seam (live-pulse heartbeat) */
    private readonly ?Closure $registryTouch;

    /**
     * @param  (callable(): (array<string, mixed>|null))|null  $reader  yields ONE committed key, never a decoded update
     * @param  (callable(array<string, mixed>): ?Model)|null  $hydrate  key ⇒ the committed row (null when there is no row)
     * @param  (callable(RelayMessage): void)|null  $publisher
     * @param  (callable(int, string): void)|null  $registryStart
     * @param  (callable(int): void)|null  $registryStop
     * @param  (callable(int): void)|null  $registryTouch
     * @param  list<RelayFilter>  $filters  control-layer onion, same shape as AccountRelay
     */
    public function __construct(
        public readonly int $accountId,
        ?callable $reader = null,
        ?callable $hydrate = null,
        ?callable $publisher = null,
        ?callable $registryStart = null,
        ?callable $registryStop = null,
        ?callable $registryTouch = null,
        private RelayPipeline $pipeline = new RelayPipeline,
        private array $filters = [],
    ) {
        $this->reader = $reader === null ? null : $reader(...);
        $this->hydrate = $hydrate === null ? null : $hydrate(...);
        $this->publisher = $publisher === null ? null : $publisher(...);
        $this->registryStart = $registryStart === null ? null : $registryStart(...);
        $this->registryStop = $registryStop === null ? null : $registryStop(...);
        $this->registryTouch = $registryTouch === null ? null : $registryTouch(...);
    }

    public function addFilter(RelayFilter $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /** Open the relay session for this account (relay id "stream"). */
    public function start(): void
    {
        if ($this->registryStart !== null) {
            ($this->registryStart)($this->accountId, 'stream');
        }
    }

    /**
     * Pull ONE committed key from the reader, resolve it to the committed row
     * through the hydrator, push that row through the filter onion and publish
     * the post-filter message.
     *
     * NULL SEMANTICS — unchanged from the pre-D-044 shape, and each case is a
     * DIFFERENT condition that must not be collapsed:
     *
     *  - no reader wired            → null (the no-op default: nothing to pull)
     *  - reader returns null        → null (stream drained / reader empty)
     *  - hydrator returns null      → null (the key names no row: the row was
     *                                 pruned, or the key came from a stream
     *                                 this account cannot see. Degrade to null
     *                                 rather than throw — a missing row is a
     *                                 fact about the world, not a wiring error,
     *                                 and throwing here would kill a relay over
     *                                 one vanished update.)
     *  - reader wired, NO hydrator  → LogicException. This is the only loud
     *                                 path, and it is deliberate: it is the
     *                                 mis-wiring D-044 exists to catch. Under
     *                                 the old contract the reader's array went
     *                                 straight into the RelayMessage, so a host
     *                                 that wired a reader and forgot the
     *                                 hydrator got a silently EMPTY relay —
     *                                 no error, no log line, just no data. The
     *                                 refusal names the account and the seam.
     *
     * Every live pulse — a key read off the reader, whether or not the row is
     * still there — refreshes the session heartbeat, exactly as before.
     */
    public function next(): ?RelayMessage
    {
        if ($this->reader === null) {
            return null;
        }

        $key = ($this->reader)();
        if ($key === null) {
            return null;
        }

        if ($this->registryTouch !== null) {
            ($this->registryTouch)($this->accountId);
        }

        if ($this->hydrate === null) {
            throw new LogicException(sprintf(
                'AccountUpdateStream for account %d was given a reader with no hydrator. The reader yields '
                .'the IDENTITY of a committed tf_updates row ({account_id, seq, position}) — never a decoded '
                .'update array, because tf_updates stores zero JSON and the raw array is not reconstructible '
                .'from the row. Wire the $hydrate callable to resolve that key to its committed model. Under '
                .'the pre-D-044 contract this mis-wiring produced a silently empty relay.',
                $this->accountId,
            ));
        }

        $row = ($this->hydrate)($key);
        if ($row === null) {
            return null;
        }

        // The key is the FLOOR and the committed row is the truth: a row
        // attribute wins, and the key only fills a column the host's hydrate
        // did not surface (a partial projection must still be addressable by
        // the identity the bus published).
        $params = array_merge($key, $row->attributesToArray());

        $message = $this->pipeline->then(
            $this->filters,
            static fn (RelayMessage $message): RelayMessage => $message,
        )(new RelayMessage(RelayMessage::INBOUND, 'update', $params, raw: null));

        if ($this->publisher !== null) {
            ($this->publisher)($message);
        }

        return $message;
    }

    /** Close the relay session for this account. */
    public function stop(): void
    {
        if ($this->registryStop !== null) {
            ($this->registryStop)($this->accountId);
        }
    }
}
