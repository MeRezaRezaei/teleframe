<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use InvalidArgumentException;
use JsonException;

/**
 * Canonical wire names and entry codec for the Redis bus (plan Phase 3).
 *
 * ## THE v2 ENTRY NAMES A COMMITTED ROW; IT DOES NOT CARRY THE UPDATE
 *
 * `{v, account_id, seq, position, constructor, ts}` — in that key order, so
 * two entries with the same values are byte-identical and can be diffed or
 * verified without drift.
 *
 * The `update` key that v1 carried — the ENTIRE decoded update array, inlined
 * — is GONE. That inversion is D-039: the entry names
 * `(account_id, seq, position)`, the real primary key of `tf_updates`, and the
 * row is the system of record. Redis was the system of record before this and
 * losing it is the point: a payload on the bus is a second, unreconcilable copy
 * of truth that the database cannot correct.
 *
 * `constructor` is published even when nothing was stored, because routing
 * reads it (D-042): `RouteTable::matchConstructorIn()` matches on the
 * constructor name and a routed container with no row would otherwise be
 * silently dropped. `seq`/`position` are then `-1/-1` SENTINELS, not zeros —
 * zero is a legal derived value, -1 is not, so the sentinel is unmistakable in
 * a stream dump and in an ops read model.
 *
 * THE VERSION DISCRIMINATOR IS NOT OPTIONAL (`decode()` / `fromFields()`).
 * A rolling upgrade hands a v1 entry — `{account_id, update, ts}`, no `v` at
 * all — to a v2 consumer. Accepting it would hand back `null` where a payload
 * used to be, and every downstream reader would treat that as "an update with
 * no fields" rather than "an entry I do not understand". So an entry this
 * codec cannot positively identify as v2 is REFUSED with a message naming the
 * discriminator, and the refusal deliberately does NOT echo the refused bytes:
 * a bus entry carries the user's own message text, and that text reaches logs
 * and error surfaces. A refusal that printed what it refused would undo this
 * change one stack frame later.
 *
 * Extra keys are tolerated on the way in (`reason`, `error` on a dead-letter
 * row). An unknown key is not a version problem.
 */
final class StreamSchema
{
    public const STREAM = 'tg:stream:updates';

    public const GROUP = 'teleclient';

    public const CONSUMER = 'ingest-1';

    public const DL = 'tg:stream:dead-letter';

    public const RELOAD_CHANNEL = 'tg:bus:reload';

    public const ROUTES_KEY = 'tg:bus:routes';

    /** Hash of per-entry ingest-failure strike counts (field = stream entry id, whole-key TTL managed by the consumer). */
    public const STRIKES_KEY = 'tg:bus:strikes';

    /**
     * The current entry version. Bumped only by a deliberate wire change; a
     * consumer that speaks a DIFFERENT version must refuse, never guess.
     */
    public const VERSION = 2;

    /**
     * The entry version that predates the `v` discriminator: a payload-carrying
     * `{account_id, update, ts}` entry. Named here because the consumer
     * dead-letters one on sight rather than mis-parsing it (step 3).
     */
    public const VERSION_PAYLOAD = 1;

    /** `seq`/`position` published when the constructor stored NO row (D-042). */
    public const NO_ROW_SEQ = -1;

    /** @see self::NO_ROW_SEQ */
    public const NO_ROW_POSITION = -1;

    /**
     * The canonical key order of a v2 entry. Stated once so encode(),
     * fromFields() and every writer cannot drift from each other.
     *
     * @var list<string>
     */
    public const CANONICAL_KEYS = ['v', 'account_id', 'seq', 'position', 'constructor', 'ts'];

    /**
     * Serialize an entry to its canonical JSON form.
     *
     * Real Telegram payloads may carry invalid UTF-8 (binary in message
     * text/entities); JSON_INVALID_UTF8_SUBSTITUTE keeps the daemon sink
     * resilient by replacing such bytes with U+FFFD, matching PHP's
     * json_decode() of any UTF-8 source. JSON_THROW_ON_ERROR still guards
     * genuine structural errors.
     *
     * A `v1`-shaped array (one carrying `update`) is REFUSED, not silently
     * coerced: an encoder that quietly dropped the payload would hide the very
     * migration this codec exists to make visible.
     *
     * @param  array<string, mixed>  $entry  a v2 entry: {v, account_id, seq, position, constructor, ts}
     *
     * @throws InvalidArgumentException When handed a pre-v2 (payload-carrying) entry.
     * @throws JsonException When the entry is not JSON-encodable.
     */
    public static function encode(array $entry): string
    {
        if (array_key_exists('update', $entry)) {
            throw new InvalidArgumentException(sprintf(
                'Bus entry carries the v%d "update" payload key; this encoder only emits v%d '
                .'committed-key entries. Encode the row identity instead.',
                self::VERSION_PAYLOAD,
                self::VERSION,
            ));
        }

        return json_encode(
            self::canonicalise($entry, self::VERSION),
            JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_INVALID_UTF8_SUBSTITUTE,
        );
    }

    /**
     * The flat Redis field map for an entry — what `xadd()` is handed.
     *
     * Every writer on the bus goes through here, which is what makes "one wire
     * shape" a property of the code rather than a promise (step 6).
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, string>
     */
    public static function fields(array $entry): array
    {
        $canonical = self::canonicalise($entry, self::VERSION);

        $fields = [];
        foreach (self::CANONICAL_KEYS as $key) {
            $fields[$key] = (string) $canonical[$key];
        }

        return $fields;
    }

    /**
     * Read a v2 entry back out of a raw stream field map.
     *
     * The counterpart of fields(), for the consumer side: a stream read hands
     * `[id => [field => value]]` with every value a string, and that map IS the
     * entry under v2 — there is no inner `update` blob to unpack.
     *
     * @param  array<string, mixed>  $fields
     * @return array{v: int, account_id: int, seq: int, position: int, constructor: string, ts: int}
     *
     * @throws InvalidArgumentException When the entry is not a v2 entry.
     */
    public static function fromFields(array $fields): array
    {
        return self::canonicalise($fields, self::versionOf($fields));
    }

    /**
     * Reverse of encode(); normalizes scalar types back to the canonical shape.
     *
     * @return array{v: int, account_id: int, seq: int, position: int, constructor: string, ts: int}
     *
     * @throws JsonException When the payload is not valid JSON.
     * @throws InvalidArgumentException When the payload is valid JSON but not a v2 entry.
     */
    public static function decode(string $payload): array
    {
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            // NEVER the payload: a bus entry carries the user's raw message
            // text, and this message reaches logs and error surfaces. Report
            // the type and the size so the shape is diagnosable.
            throw new InvalidArgumentException(sprintf(
                'Bus entry must be a JSON object, got %s (%d bytes)',
                get_debug_type($decoded),
                strlen($payload),
            ));
        }

        return self::canonicalise($decoded, self::versionOf($decoded));
    }

    /**
     * Read a PRE-MIGRATION (payload-carrying) entry, and only that.
     *
     * The single sanctioned reader of a v1 entry, and it exists for three
     * jobs that are all about EXISTING artifacts rather than new traffic:
     *
     *  - a pre-migration dead-letter row, which the old consumer preserved
     *    verbatim and which `identityOf()` migrates onto the v2 shape;
     *  - a pre-migration stream entry a host explicitly bound the legacy
     *    drain seam for; and
     *  - `IngestConsumer::retryable()` deciding whether such a row is worth
     *    replaying.
     *
     * It is deliberately NOT what any writer publishes and NOT what the
     * consumer's hot path uses: `decode()` and `fromFields()` are the only
     * readers on the bus, and they refuse a v1 entry outright. Keeping the
     * legacy shape readable in exactly one named place is what stops a
     * migration path from becoming a second, quieter way to run the old wire
     * format.
     *
     * @return array{account_id: int, update: array<string, mixed>, ts: int}
     *
     * @throws JsonException When the payload is not valid JSON.
     * @throws InvalidArgumentException When it is not a legacy entry. The
     *                                  message never echoes the payload.
     */
    public static function decodeLegacy(string $payload): array
    {
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException(sprintf(
                'Legacy bus entry must be a JSON object, got %s (%d bytes)',
                get_debug_type($decoded),
                strlen($payload),
            ));
        }

        foreach (['account_id', 'update', 'ts'] as $key) {
            if (! array_key_exists($key, $decoded)) {
                throw new InvalidArgumentException(sprintf(
                    'Legacy bus entry is missing the "%s" key.',
                    $key,
                ));
            }
        }

        if (! is_array($decoded['update'])) {
            throw new InvalidArgumentException('Legacy bus entry "update" must be an object.');
        }

        return [
            'account_id' => (int) $decoded['account_id'],
            'update' => $decoded['update'],
            'ts' => (int) $decoded['ts'],
        ];
    }

    /**
     * The `v` field read as an int, defaulting to the payload-carrying version.
     *
     * ABSENCE IS THE DISCRIMINATOR: a v1 entry has no `v` at all, so an
     * unmarked entry is v1 by definition rather than "unknown". Every reader
     * runs it through this, so one rule decides for the whole package.
     *
     * @param  array<string, mixed>  $entry
     */
    public static function versionOf(array $entry): int
    {
        $raw = $entry['v'] ?? null;

        if ($raw === null || ! is_numeric($raw)) {
            return self::VERSION_PAYLOAD;
        }

        return (int) $raw;
    }

    /**
     * The `(account_id, seq, position)` an entry names, as a query-able string.
     *
     * Used by the dead-letter `reason` tag (D-043): a DL row carries no
     * payload, so the composite key in the reason is the ONLY thing that lets
     * an operator trace a bad entry back to its `tf_updates` row.
     *
     * @param  array<string, mixed>  $entry
     */
    public static function keyLabel(array $entry): string
    {
        return sprintf(
            'account_id=%d seq=%d position=%d',
            (int) ($entry['account_id'] ?? 0),
            (int) ($entry['seq'] ?? self::NO_ROW_SEQ),
            (int) ($entry['position'] ?? self::NO_ROW_POSITION),
        );
    }

    /**
     * Coerce one entry to the canonical shape, or refuse it.
     *
     * The single gate: encode(), fields(), decode() and fromFields() all come
     * through here, so "v2 only" is enforced in exactly one place and a fourth
     * writer cannot opt out of it by accident.
     *
     * @param  array<string, mixed>  $entry
     * @return array{v: int, account_id: int, seq: int, position: int, constructor: string, ts: int}
     *
     * @throws InvalidArgumentException
     */
    private static function canonicalise(array $entry, int $version): array
    {
        if ($version !== self::VERSION) {
            throw new InvalidArgumentException(sprintf(
                'Refusing a v%d bus entry: this build speaks v%d (committed-key entries carrying '
                .'%s). A v%d entry carries the update payload inline and is never mis-parsed into one. '
                .'Drain the v%d entries already on the stream before upgrading the publisher.',
                $version,
                self::VERSION,
                implode(', ', self::CANONICAL_KEYS),
                self::VERSION_PAYLOAD,
                self::VERSION_PAYLOAD,
            ));
        }

        foreach (['account_id', 'seq', 'position', 'constructor', 'ts'] as $key) {
            if (! array_key_exists($key, $entry)) {
                throw new InvalidArgumentException(sprintf(
                    'Bus entry is missing the "%s" key.',
                    $key,
                ));
            }
        }

        return [
            'v' => self::VERSION,
            'account_id' => (int) $entry['account_id'],
            'seq' => (int) $entry['seq'],
            'position' => (int) $entry['position'],
            'constructor' => (string) $entry['constructor'],
            'ts' => (int) $entry['ts'],
        ];
    }
}
