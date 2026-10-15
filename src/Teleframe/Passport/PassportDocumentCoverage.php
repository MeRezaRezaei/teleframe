<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportDocumentCoverage — THE TABLE. One row per
 * (`allowed_types` value × §7 slot), and every row is either a
 * {@see PassportDocument} or a {@see PassportDocumentRefusal}.
 *
 * ══ THE INVARIANT, STATED SO A TEST CAN STATE IT TOO ══════════════════════
 *
 *   > **every `allowed_types` value produces at least one document or a named
 *   > refusal. Never a silent absence.**
 *
 * Two mechanical consequences, and both are asserted in
 * `PassportDocumentCoverageTest` rather than asserted in prose here:
 *
 *   1. `for($type)` is never empty, for ANY string a host can put in
 *      `allowed_types` — including a name outside the §7 table, including the
 *      empty string, including `email` on a response Telegram verified no email
 *      on. The walk walks the §7 table for slots and, when a type grants no
 *      slots at all, emits one {@see PassportDocumentRefusal::SLOT_ABSENT}
 *      row so the row count cannot silently fall to zero. That last step is the
 *      load-bearing one: it is the difference between "this type produced
 *      nothing" and "this type produced a NAMED nothing", and it is exactly
 *      what the pre-document build did not do.
 *   2. no row is ever `null` and no row is ever a bare marker. There is no
 *      sentinel object standing for "we looked and there was nothing", because
 *      a sentinel is a silent absence with a nicer type.
 *
 * ══ WHY A COVERAGE OBJECT AND NOT A RETURN VALUE ═══════════════════════════
 *
 * The coverage is computed unconditionally, for every login, whether or not
 * the host wants it. That is deliberate and it is the reason the defect is
 * closed rather than deferred:
 *
 *   - making it opt-in would reproduce the bug — a host that did not opt in
 *     would get the old silent behaviour, and the default is what ships;
 *   - making it throw would turn "the user supplied four of eleven documents"
 *     into a failed login, which is a different and much worse failure;
 *   - making it log would make it unassertable, and a log line nobody reads is
 *     indistinguishable from no log line.
 *
 * So it is a VALUE, carried on {@see LoginResult}, readable by the host, and
 * reportable to Telegram through {@see PassportErrorReporter}. A host that
 * ignores it has still paid for it; a host that reads it knows exactly which of
 * its eleven requested types arrived and which did not.
 *
 * ══ WHAT EACH ROW MEANS ═══════════════════════════════════════════════════
 *
 * The §7 table decides which rows EXIST, so the row set is a property of the
 * type and not of the payload — a missing document does not remove its row, it
 * fills it with a refusal. That is the whole design: absence changes a row's
 * CONTENT and never the table's SHAPE.
 *
 * `plain_data` rows (email / phone_number) hold a verified claim, which is a
 * document in the sense the table needs: §6 says Telegram vouched for the
 * value, and a host that asked for a verified email now gets either that value
 * or a named `claim_absent` — never a login that quietly has no email.
 *
 * SECURITY: `documents()` is the only accessor that hands out personal data,
 * and it is opt-in by the caller naming it. `codesByType()`, `refusals()` and
 * `toArray()` are names and codes only.
 *
 * Plain-PHP constructible (standalone-smoke): one static factory, no container,
 * no config, no I/O, no logging. Zero regex (`src/Teleframe`).
 */
final class PassportDocumentCoverage
{
    /**
     * @param  list<string>  $allowedTypes  the host's `allowed_types`, verbatim
     * @param  list<PassportDocument|PassportDocumentRefusal>  $rows  every row,
     *                                                                in `allowed_types` order then §7 slot order
     */
    private function __construct(
        private readonly array $allowedTypes,
        private readonly array $rows,
    ) {}

    /**
     * Walk every `allowed_types` value across the §7 slots and produce the
     * table. Never throws, never returns a partial table: a type that cannot be
     * walked still gets a row.
     *
     * @param  list<string>  $allowedTypes
     */
    public static function build(
        array $allowedTypes,
        PassportPayload $payload,
        PassportDocumentSource $source,
        PassportDocumentResolver $resolver,
    ): self {
        $rows = [];

        foreach ($allowedTypes as $type) {
            foreach (self::rowsFor((string) $type, $payload, $source, $resolver) as $row) {
                $rows[] = $row;
            }
        }

        return new self($allowedTypes, $rows);
    }

    /**
     * The rows for ONE type, and the reason this is never empty.
     *
     * @return list<PassportDocument|PassportDocumentRefusal>
     */
    private static function rowsFor(
        string $type,
        PassportPayload $payload,
        PassportDocumentSource $source,
        PassportDocumentResolver $resolver,
    ): array {
        $rows = [];

        foreach ($source->allowedSlots($type) as $slot) {
            if ($slot === PassportDocumentRefusal::SLOT_PLAIN_DATA) {
                $rows[] = $resolver->resolveClaim($type, $payload->claims());

                continue;
            }

            if ($slot === PassportDocumentRefusal::SLOT_DATA) {
                $rows[] = $resolver->resolveData($type, $source);

                continue;
            }

            foreach (self::fileRows($type, $slot, $source, $resolver) as $row) {
                $rows[] = $row;
            }
        }

        // THE ROW-COUNT FLOOR. A type outside the §7 table grants no slots, so
        // the loop above produced nothing — and "nothing" is the defect. One
        // named refusal is emitted so every entry in `allowed_types` has at
        // least one row, and `isComplete()` can mean what it says.
        if ($rows === []) {
            $rows[] = PassportDocumentRefusal::named(
                $type,
                PassportDocumentRefusal::SLOT_DATA,
                PassportDocumentRefusal::SLOT_ABSENT,
                'no such Telegram Passport element type; the SecureValueType/Allowed fields table does not list it.',
            );
        }

        return $rows;
    }

    /**
     * One §7 file slot: a refusal when nothing arrived, one row per envelope
     * when it did.
     *
     * @return list<PassportDocument|PassportDocumentRefusal>
     */
    private static function fileRows(
        string $type,
        string $slot,
        PassportDocumentSource $source,
        PassportDocumentResolver $resolver,
    ): array {
        $envelopes = $source->fileEnvelopes($type, $slot);

        if ($envelopes !== []) {
            $rows = [];
            foreach ($envelopes as $envelope) {
                $rows[] = $resolver->resolveFile($envelope);
            }

            return $rows;
        }

        if (! $source->hasType($type)) {
            return [PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::ABSENT,
                'Telegram sent no element of this type in this response.',
            )];
        }

        // The type arrived but this slot is empty on it. That is a DIFFERENT
        // fact from "the type never arrived" and a host reports them
        // differently — a response with no selfie on a passport is a
        // legitimate thing to tell Telegram about; a missing passport is not
        // something a user can be re-prompted for on the field that is absent.
        return [PassportDocumentRefusal::named(
            $type,
            $slot,
            PassportDocumentRefusal::SLOT_ABSENT,
            'the element arrived but carried no file in this slot.',
        )];
    }

    /** Every row, in `allowed_types` order then §7 slot order. */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * The rows for one type. NEVER empty for a string that appeared in
     * `allowed_types` — that is invariant (1) above, and it is what the gate
     * pins.
     *
     * @return list<PassportDocument|PassportDocumentRefusal>
     */
    public function for(string $type): array
    {
        $rows = [];
        foreach ($this->rows as $row) {
            if ($row->type === $type) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Just the decrypted documents. The one accessor that hands out personal
     * data; nothing in this package calls it on its own.
     *
     * @return list<PassportDocument>
     */
    public function documents(): array
    {
        $documents = [];
        foreach ($this->rows as $row) {
            if ($row instanceof PassportDocument) {
                $documents[] = $row;
            }
        }

        return $documents;
    }

    /** @return list<PassportDocumentRefusal> */
    public function refusals(): array
    {
        $refusals = [];
        foreach ($this->rows as $row) {
            if ($row instanceof PassportDocumentRefusal) {
                $refusals[] = $row;
            }
        }

        return $refusals;
    }

    /**
     * The refusal CODES per type, deduplicated — the input shape a host
     * aggregates on and the shape `PassportErrorReporter` maps to Telegram's
     * per-field error constructors.
     *
     * @return array<string, list<string>>
     */
    public function codesByType(): array
    {
        $codes = [];
        foreach ($this->refusals() as $refusal) {
            if (! in_array($refusal->code, $codes[$refusal->type] ?? [], true)) {
                $codes[$refusal->type][] = $refusal->code;
            }
        }

        return $codes;
    }

    /** The `allowed_types` values the table was built for, verbatim. */
    public function allowedTypes(): array
    {
        return $this->allowedTypes;
    }

    /**
     * THE INVARIANT, as a boolean: every `allowed_types` value has at least one
     * row.
     *
     * This is the method `PassportDocumentCoverageTest` asserts over the whole
     * §7 table, over the shipped default allow-list, and over a set of hostile
     * allow-lists (unknown names, the empty string, duplicates). It is the gate
     * that would catch a regression to silent absence.
     *
     * It does NOT re-check that every row is one of the two classes, because
     * that is a TYPE-level guarantee, not a runtime one: `$rows` is
     * `list<PassportDocument|PassportDocumentRefusal>` and `build()` has no
     * third thing to put in it. A second `instanceof` loop over an already
     * narrowed property would be statically dead code pretending to be a
     * check — and "a check that cannot fail is not a check" is the exact
     * lesson `PassportCredentialsVerifier` writes at length. The half of the
     * invariant that CAN fail at runtime is the row COUNT, and that is what is
     * checked here.
     */
    public function isComplete(): bool
    {
        if ($this->rows === [] && $this->allowedTypes !== []) {
            return false;
        }

        foreach ($this->allowedTypes as $type) {
            if ($this->for((string) $type) === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * A one-glance report — NAMES and CODES only, never a value. This is the
     * shape a host logs, renders on an admin screen, or diffs between runs.
     *
     * @return array{allowed_types: list<string>, rows: list<array{type: string, slot: string, outcome: string, payload_class?: string}>}
     */
    public function toArray(): array
    {
        $rows = [];

        foreach ($this->rows as $row) {
            $entry = $row instanceof PassportDocument
                ? [
                    'type' => $row->type,
                    'slot' => $row->slot,
                    'outcome' => 'document',
                    'payload_class' => $row->payloadClass,
                ]
                : [
                    'type' => $row->type,
                    'slot' => $row->slot,
                    'outcome' => 'refused',
                    'payload_class' => $row->code,
                ];

            $rows[] = $entry;
        }

        return [
            'allowed_types' => $this->allowedTypes,
            'rows' => $rows,
        ];
    }
}
