<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

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
        public readonly ?TlAnchorModel $model = null,
    ) {
    }

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

        return sha1($this->accountId . "\0" . $ts . "\0" . $raw);
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
    public function withModel(?TlAnchorModel $model): self
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
     * Build the uprate from a stored root model (the event path). The raw
     * constructor name is not recoverable from the mirror (type and
     * constructor both studly-capitalize in the class name — ambiguous), so
     * the constructor key is empty: this path matches the `*` catch-all
     * (`onMessage`) and prefix `''` patterns. Precise constructor routing
     * belongs to the bus path where the raw array is in hand.
     */
    public static function fromMirror(TlAnchorModel $model, int $accountId, ?int $ts = null): self
    {
        if ($ts === null) {
            $created = $model->getAttribute('created_at');
            if ($created instanceof \DateTimeInterface) {
                $ts = $created->getTimestamp();
            }
        }

        return new self([], $accountId, 'event', $ts, false, $model);
    }
}