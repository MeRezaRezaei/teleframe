<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

use Illuminate\Database\Eloquent\Model;

/**
 * The uprate — one immutable value object both intake rows (Laravel
 * ``UpdateStored`` event and Redis bus entry) collapse into (friction I.1
 * dissolved: one payload, two transport rows, one pipeline).
 *
 * Two-stage route/model split (Q4): the raw constructor name is read
 * eagerly (``constructor()``); the mirrored Eloquent model is NOT hydrated
 * here — handlers hydrate it via the dispatcher's DI in the handler body.
 */
final class Update
{
    public function __construct(
        public readonly array $array,
        public readonly int $accountId,
        public readonly string $source = 'event',
        public readonly ?int $ts = null,
        public readonly bool $selfOriginated = false,
        public readonly ?Model $model = null,
    ) {}

    /** Raw constructor name — the router's match key. */
    public function constructor(): string
    {
        return (string) ($this->array['_'] ?? '');
    }

    /**
     * Deterministic content-hash identity (spec 5a §3) — stable across
     * deploys and processes: ``sha1(accountId . "\0" . ts . "\0" . raw_json)``.
     * Derived from the ENTRY (array + account + ts), not the handler result,
     * so a re-ingested payload dedups regardless of handler mutability.
     */
    public function updateId(): string
    {
        $raw = json_encode(
            $this->array,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
        $ts = $this->ts !== null ? (string) $this->ts : '';

        return sha1($this->accountId."\0".$ts."\0".$raw);
    }

    /** A fresh frame with the self-originated verdict set. */
    public function withSelfOriginated(bool $selfOriginated): self
    {
        return new self(
            $this->array,
            $this->accountId,
            $this->source,
            $this->ts,
            $selfOriginated,
            $this->model,
        );
    }

    /**
     * Attach the mirrored root model once the handler asks for it (lazy
     * hydration — the two-stage second half).
     */
    public function withModel(?Model $model): self
    {
        return new self(
            $this->array,
            $this->accountId,
            $this->source,
            $this->ts,
            $this->selfOriginated,
            $model,
        );
    }

    /** @param array<string, mixed> $update */
    public static function fromBus(array $update, int $accountId, ?int $ts = null): self
    {
        return new self($update, $accountId, 'bus', $ts);
    }

    /**
     * Build the uprate from a stored root model (the event path).
     *
     * THE CONSTRUCTOR IS RECOVERABLE (2026-09-26 audit fix, BREAK 2). The old
     * docblock claimed it was not — "type and constructor both studly-
     * capitalize in the class name, ambiguous" — and shipped
     * `new self([], …)`, so `constructor()` was always '' and only the `*`
     * catch-all matcher could fire. An `AppTrigger::on('updateNewMessage', …)`
     * was structurally unreachable on this path: the app framework could be
     * declared, registered, listed, and never run once.
     *
     * The ambiguity was never in the class name: the NF5 mirror writes the
     * raw TL constructor into a `constructor` column on BOTH mirror roots
     * (tf_updates.constructor, tf_messages.constructor — see
     * UpdateIngestor::writeUpdate/writeMessage). That column is the same
     * string the bus path carries in `['_']`, so the two intake rows now
     * route identically.
     *
     * The array stays a MINIMAL identity row, never the raw payload: the
     * event path is a stored-truth projection and the mirrored model is the
     * payload. A row with no `constructor` attribute at all (the legacy Tl*
     * models) degrades to '' and keeps matching the `*` catch-all — nothing
     * regresses for the old shape.
     *
     * THE ROW ALSO CARRIES THE TWO SEND IDS (2026-09-30, echo-eliminator
     * defect). The row used to be `['_'] => ctor` and nothing else, so
     * `EchoEliminator` — which reads its evidence ONLY from this array — found
     * `random_id` and `msg_id` both `''`, `lookup()` returned null, and the
     * pipeline's single route-level loop guard fell through to `$next()` on
     * EVERY event frame. The guard was blind on the SHIPPED default intake
     * (`UpdateStoredHandler`, registered by `TeleframeServiceProvider:965`).
     * Ingest's `SelfOriginatedClassifier` papers over the common case via the
     * `out` flag, but its own documented escape hatch
     * (`SelfOriginatedClassifier.php:66-70`) lets a peer rule `act_on` an own
     * send through as an ordinary input — and into a feedback loop.
     *
     * They are read from the model's ALREADY-HYDRATED attributes
     * (`getAttributes()`, never `getAttribute()`), so this is not a query:
     * a missing key is simply absent, which is honest — most rows genuinely
     * carry no `random_id`, because MTProto only ever hands one to the sender
     * and the mirror only stores it on the `updateActionMessage` child
     * (`TfUpdateAction`), which is a different constructor from the message
     * echo being eliminated. `msg_id` falls back to the root's own `id`,
     * which for a curated message root IS the Telegram message id: the mirror
     * keys `tf_messages` on `(account_id, peer_type, peer_id, id)` (see
     * `TfMessage`'s own docblock) and the send path already folds an `id`
     * result into `msg_id` (`AppRuntime::rememberSend`, :404-411), so the two
     * sides of the registry agree by construction.
     *
     * WHY THIS AND NOT A FALLBACK INSIDE THE MIDDLEWARE: `updateId()` DOES
     * change for event frames now, and that is safe precisely because its
     * event-path consumers refuse to use it — `ReplayDedup:38-53` returns
     * before the `updateId()` key, and `UpdateDispatcher::forwardKey()` keys
     * the event path on the mirrored model and returns before the
     * `$array !== []` branch that calls it. Neither is silently re-armed,
     * because both carry the dated comment saying so. `constructor()` is
     * untouched, `HandlerMatcher` reads only the constructor string, and the
     * extra keys are inert to `ValidatedUpdate` (path-specific rules) and to
     * `fieldValue()`, where a handler declaring a `msg_id` argument now simply
     * receives it on this path too.
     */
    public static function fromMirror(Model $model, int $accountId, ?int $ts = null): self
    {
        if ($ts === null) {
            $created = $model->getAttribute('created_at');
            if ($created instanceof \DateTimeInterface) {
                $ts = $created->getTimestamp();
            }
        }

        // Raw hydrated attributes — never a relation, never a query.
        //
        // The type is declared `array<string, mixed>`, so PHPStan is RIGHT that
        // this is an array. It is kept anyway, and that is a deliberate
        // disagreement rather than an oversight: Eloquent's contract is a
        // PHPDoc promise that a MOCK is free to violate, and two existing tests
        // do exactly that — `UpdateValueObjectsTest` stubs `TlAnchorModel` and
        // its `getAttributes()` yields null. Dropping the guard makes
        // `fromMirror` fatal on such a model instead of degrading to an empty
        // row, so the honest form of this code is to coerce and say why.
        /** @var mixed $rawAttributes */
        $rawAttributes = $model->getAttributes();
        $attributes = is_array($rawAttributes) ? $rawAttributes : [];

        $constructor = (string) ($attributes['constructor'] ?? '');
        $row = $constructor === '' ? [] : ['_' => $constructor];

        $randomId = self::identityScalar($attributes, 'random_id');
        if ($randomId !== '') {
            $row['random_id'] = $randomId;
        }

        $msgId = self::identityScalar($attributes, 'msg_id');
        if ($msgId === '') {
            $msgId = self::identityScalar($attributes, 'id');
        }

        if ($msgId !== '') {
            $row['msg_id'] = $msgId;
        }

        return new self(
            $row,
            $accountId,
            'event',
            $ts,
            false,
            $model,
        );
    }

    /**
     * One identity value as a non-empty string, or '' when the hydrated row
     * does not carry it. `0` is kept — it is a legal MTProto id, and dropping
     * it would silently narrow the registry rather than widen the risk.
     *
     * @param array<string, mixed> $attributes
     */
    private static function identityScalar(array $attributes, string $key): string
    {
        $value = $attributes[$key] ?? null;

        if ($value === null || is_array($value) || is_object($value) || is_resource($value)) {
            return '';
        }

        return (string) $value;
    }
}
