<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportErrorReporter — TELLS TELEGRAM WHAT WENT WRONG.
 *
 * Until this class existed, a document that could not be produced was a fact
 * only this package knew: the coverage table held a named refusal, the login
 * carried on, and Telegram was never told. Telegram's own contract is the
 * opposite — `setPassportDataErrors` exists so a bot can say "this field is
 * wrong, send a different one", and until it is called the user cannot
 * re-submit. So a rejection this package makes but does not report is a
 * rejection the user experiences as a login that mysteriously never finishes.
 *
 * ══ TWO METHODS, TWO SHAPES, ONE VOCABULARY ═══════════════════════════════
 *
 * Telegram has two error-reporting methods and they are not translations of
 * each other:
 *
 *  - **Bot API** `setPassportDataErrors(user_id, errors)` — the method the
 *    operator named. HTTP, `errors` is a JSON-serialized
 *    `Array of PassportElementError`. This package's only wire seam is
 *    MTProto, so this class BUILDS that array exactly and hands it to the host
 *    (`botApiErrors()`), which posts it however it posts everything else. It
 *    is not silently dropped for being unreachable from this seam: it is
 *    returned, fully formed, and asserted by a test.
 *  - **MTProto** `users.setSecureValueErrors#90c894b5(id:InputUser,
 *    errors:Vector<SecureValueError>) = Bool` — a USER-account method telling
 *    Telegram about its OWN rejected values. It is in the layer-227 mirror and
 *    the layer-229 catalogue, so `report()` dispatches it over
 *    {@see PassportWireContract} with `inputUserSelf`.
 *
 * The nine `secureValueError*` constructors and the nine
 * `PassportElementError*` subtypes are the SAME nine failures spelled two
 * ways, and `PassportFieldError` already documents the MTProto half.
 *
 * ══ THE HASH PROBLEM, AND WHY IT HAS AN HONEST FALLBACK ══════════════════
 *
 * Every one of Telegram's error constructors requires a hash it can match the
 * user's re-submission against:
 *
 *   - `data` failures need `data_hash` — available, because the raw 32-byte
 *     `data_hash` is in hand (§4) and the Bot API wants it base64;
 *   - FILE failures need `file_hash` — available ONLY on the MTProto shape,
 *     because a Bot API `PassportFile` carries `file_id`, `file_unique_id`,
 *     `file_size` and `file_date` and nothing else. There is no hash to send;
 *   - when there is no per-file hash, Telegram's own answer is
 *     `PassportElementErrorUnspecified`, which takes the ELEMENT hash — and
 *     that IS available on both shapes (the §6 error-reporting element hash,
 *     see `PassportDocumentSource::elementHash()`).
 *
 * So a Bot API file refusal reports as `Unspecified` against the element hash,
 * which is Telegram's documented mechanism for "something about this element
 * must change", NOT a silent drop and NOT a refusal to report. And when even
 * that is missing — no element hash arrived — the refusal is counted in
 * `unreportable()` rather than sent as a malformed error. A report Telegram
 * would reject is worse than an honest "I could not name the element".
 *
 * ══ WHAT IS NEVER SENT ════════════════════════════════════════════════════
 *
 * No plaintext, no decrypted payload, no `access_hash`, no `data_secret`. The
 * errors carry: the §7 type NAME, the slot NAME, the refusal CODE, and a
 * message assembled from those three plus the operator's own text. That is
 * enough for a human to know what to fix and nothing more.
 *
 * Plain-PHP constructible (standalone-smoke): one optional constructor
 * argument, no container, no config, no I/O, no logging.
 * Zero regex (`src/Teleframe`): `in_array`, `sprintf`, array lookups.
 */
final class PassportErrorReporter
{
    /** §5 — the types Telegram's `data` error constructors accept. */
    public const DATA_ERROR_TYPES = [
        'personal_details',
        'passport',
        'driver_license',
        'identity_card',
        'internal_passport',
        'address',
    ];

    /** §7 — the file-only types, which have a `data` field nowhere. */
    public const FILE_ERROR_TYPES = [
        'utility_bill',
        'bank_statement',
        'rental_agreement',
        'passport_registration',
        'temporary_registration',
    ];

    /**
     * §7 slot → the Bot API `PassportElementError*` subtype that reports it.
     *
     * `data` is deliberately ABSENT: it maps to `PassportElementErrorDataField`,
     * which is the one subtype that is keyed on `field_name` + `data_hash`
     * rather than on a file hash, so it is built separately and cannot share a
     * generic row.
     *
     * @var array<string, string>
     */
    public const SLOT_ERROR_SOURCES = [
        PassportDocumentRefusal::SLOT_FRONT_SIDE => 'front_side',
        PassportDocumentRefusal::SLOT_REVERSE_SIDE => 'reverse_side',
        PassportDocumentRefusal::SLOT_SELFIE => 'selfie',
        PassportDocumentRefusal::SLOT_FILES => 'files',
        PassportDocumentRefusal::SLOT_TRANSLATION => 'translation_file',
    ];

    /**
     * §7 slot → the MTProto `secureValueError*` constructor, verbatim from the
     * layer-227 mirror.
     *
     * @var array<string, string>
     */
    public const SLOT_SECURE_VALUE_ERRORS = [
        PassportDocumentRefusal::SLOT_DATA => 'secureValueErrorData',
        PassportDocumentRefusal::SLOT_FRONT_SIDE => 'secureValueErrorFrontSide',
        PassportDocumentRefusal::SLOT_REVERSE_SIDE => 'secureValueErrorReverseSide',
        PassportDocumentRefusal::SLOT_SELFIE => 'secureValueErrorSelfie',
        PassportDocumentRefusal::SLOT_FILES => 'secureValueErrorFile',
        PassportDocumentRefusal::SLOT_TRANSLATION => 'secureValueErrorTranslationFile',
    ];

    /** §5 — the constructor name the MTProto `type` field wants, or ''. */
    private const UNSPECIFIED = 'unspecified';

    public function __construct(private readonly ?PassportWireContract $wire = null) {}

    /**
     * The exact `errors` array `setPassportDataErrors(user_id, errors)` takes,
     * ready for the host to post.
     *
     * Each entry is one of the nine `PassportElementError*` subtypes, built
     * from the coverage's refusals and the source's hashes:
     *
     *  - a `data` refusal with a §5 type → `PassportElementErrorDataField`,
     *    carrying the base64 of the raw `data_hash` §4 already verified;
     *  - a file refusal on a type with a raw `file_hash` (MTProto) → the
     *    specific subtype for that slot, carrying that hash;
     *  - a file refusal with no per-file hash (the Bot API shape) →
     *    `PassportElementErrorUnspecified` against the ELEMENT hash, which is
     *    Telegram's documented mechanism for "this element must change";
     *  - anything with no hash at all → omitted, and counted in
     *    `unreportable()`.
     *
     * A `claim_absent` refusal is NEVER reported: §6 makes the verified email
     * and phone optional, so "Telegram did not send one" is not a defect the
     * user can fix by re-submitting, and telling them it is would be a lie
     * about what is wrong.
     *
     * @return list<array<string, mixed>>
     */
    public function botApiErrors(PassportDocumentCoverage $coverage, PassportDocumentSource $source): array
    {
        $errors = [];

        foreach ($coverage->refusals() as $refusal) {
            $error = $this->botApiErrorFor($refusal, $source);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Dispatch `users.setSecureValueErrors` over the wire seam.
     *
     * Returns `true` on Telegram's `Bool` answer and `false` when the call was
     * refused, when no seam is bound, or when there was nothing reportable —
     * and it NEVER throws, because a failed error-report is not a reason to
     * fail a login that already succeeded. The caller learns it did not happen
     * and can log or retry; it cannot mistake it for a report Telegram got.
     */
    public function report(PassportDocumentCoverage $coverage, PassportDocumentSource $source): bool
    {
        $wire = $this->wire;
        if ($wire === null) {
            return false;
        }

        $errors = $this->secureValueErrors($coverage, $source);
        if ($errors === []) {
            return false;
        }

        try {
            $response = $wire->call(PassportWireContract::METHOD_SET_SECURE_VALUE_ERRORS, [
                // A user account reports errors about its OWN values, so the id
                // is `inputUserSelf#f7c1b13f` (api_full.tl:21). A bot's
                // equivalent is the Bot API method above, which takes the
                // telegram user id directly.
                'id' => ['_' => 'inputUserSelf'],
                'errors' => $errors,
            ]);
        } catch (\Throwable) {
            return false;
        }

        return ($response['_'] ?? '') === 'boolTrue' || ($response['result'] ?? false) === true;
    }

    /**
     * The `Vector<SecureValueError>` bodies, in the layer-227 constructor
     * spelling. Public because a host that dispatches over its own MTProto
     * seam needs them without dispatching through this one.
     *
     * @return list<array<string, mixed>>
     */
    public function secureValueErrors(PassportDocumentCoverage $coverage, PassportDocumentSource $source): array
    {
        $errors = [];

        foreach ($coverage->refusals() as $refusal) {
            $error = $this->secureValueErrorFor($refusal, $source);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * The refusals that COULD NOT be named to Telegram — no `data_hash`, no
     * per-file hash, and no element hash to fall back on. Counted rather than
     * dropped, so "the user was never told" is visible to the host.
     *
     * @return list<PassportDocumentRefusal>
     */
    public function unreportable(PassportDocumentCoverage $coverage, PassportDocumentSource $source): array
    {
        $missed = [];

        foreach ($coverage->refusals() as $refusal) {
            if ($this->botApiErrorFor($refusal, $source) === null
                && $this->secureValueErrorFor($refusal, $source) === null) {
                $missed[] = $refusal;
            }
        }

        return $missed;
    }

    /**
     * One `PassportElementError*` entry, or null when the refusal cannot be
     * named.
     *
     * @return array<string, mixed>|null
     */
    private function botApiErrorFor(PassportDocumentRefusal $refusal, PassportDocumentSource $source): ?array
    {
        // §6 makes the claim optional; a user cannot "fix" a field Telegram
        // never verified, so reporting it would misattribute the problem.
        if ($refusal->is(PassportDocumentRefusal::CLAIM_ABSENT)) {
            return null;
        }

        $message = $refusal->message();

        if ($refusal->slot === PassportDocumentRefusal::SLOT_DATA) {
            $hash = $this->dataHashBase64($refusal->type, $source);

            if ($hash === null || ! in_array($refusal->type, self::DATA_ERROR_TYPES, true)) {
                return $this->unspecified($refusal, $source, $message);
            }

            return [
                'source' => 'data',
                'type' => $refusal->type,
                // Telegram matches a re-submission by the hash of the FIELD, so
                // a field-less data error cannot be matched and is reported as
                // unspecified — with `field_name` naming the whole payload.
                'field_name' => $refusal->slot,
                'data_hash' => $hash,
                'message' => $message,
            ];
        }

        $fileHash = $this->fileHashBase64($refusal->type, $refusal->slot, $source);
        if ($fileHash !== null) {
            $sourceName = self::SLOT_ERROR_SOURCES[$refusal->slot] ?? null;
            if ($sourceName !== null) {
                return [
                    'source' => $sourceName,
                    'type' => $refusal->type,
                    'file_hash' => $fileHash,
                    'message' => $message,
                ];
            }
        }

        return $this->unspecified($refusal, $source, $message);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function unspecified(PassportDocumentRefusal $refusal, PassportDocumentSource $source, string $message): ?array
    {
        $elementHash = $source->elementHash($refusal->type);
        if ($elementHash === '') {
            return null; // counted by unreportable(), never sent malformed
        }

        return [
            'source' => self::UNSPECIFIED,
            'type' => $refusal->type,
            'element_hash' => $elementHash,
            'message' => $message,
        ];
    }

    /**
     * One `secureValueError*` body, or null when the refusal cannot be named.
     *
     * @return array<string, mixed>|null
     */
    private function secureValueErrorFor(PassportDocumentRefusal $refusal, PassportDocumentSource $source): ?array
    {
        if ($refusal->is(PassportDocumentRefusal::CLAIM_ABSENT)) {
            return null;
        }

        $constructor = self::SLOT_SECURE_VALUE_ERRORS[$refusal->slot] ?? null;
        if ($constructor === null) {
            return null; // plain_data — §6 has no secureValueError for it
        }

        $typeName = $this->secureValueTypeName($refusal->type);
        if ($typeName === '') {
            return null;
        }

        $body = ['_' => $constructor, 'type' => $typeName];

        if ($refusal->slot === PassportDocumentRefusal::SLOT_DATA) {
            $hash = $this->dataHashBase64($refusal->type, $source);
            if ($hash === null) {
                return null;
            }

            // `secureValueErrorData#e8a40bd9 type data_hash field text` — the
            // `field` names WHICH part of the payload is wrong, and this module
            // never read a decrypted payload's fields, so the refusal's slot is
            // the honest answer: the whole encrypted payload must change.
            $body['data_hash'] = base64_decode($hash, true);
            $body['field'] = $refusal->slot;
            $body['text'] = $refusal->message();

            return $body;
        }

        $hash = $this->fileHashBase64($refusal->type, $refusal->slot, $source);
        if ($hash === null) {
            return null;
        }

        $body['file_hash'] = base64_decode($hash, true);
        $body['text'] = $refusal->message();

        return $body;
    }

    /**
     * The raw §4 `data_hash`, base64 — or null when this response never
     * carried one for the type (which is exactly what
     * {@see PassportDocumentRefusal::CREDENTIALS_ABSENT} means).
     */
    private function dataHashBase64(string $type, PassportDocumentSource $source): ?string
    {
        $envelope = $source->dataEnvelope($type);
        if ($envelope === null) {
            return null;
        }

        return base64_encode($envelope->rawHash());
    }

    /**
     * The first raw §4 `file_hash` in a slot, base64 — or null. Only the
     * MTProto shape has one at all; a Bot API `PassportFile` does not, and the
     * caller falls back to `Unspecified` there.
     */
    private function fileHashBase64(string $type, string $slot, PassportDocumentSource $source): ?string
    {
        $envelopes = $source->fileEnvelopes($type, $slot);
        if ($envelopes === []) {
            return null;
        }

        $hash = $envelopes[0]->rawHashForVerify();
        if ($hash === '' || strlen($hash) !== 32) {
            return null;
        }

        return base64_encode($hash);
    }

    /** `passport` → `secureValueTypePassport`, or '' for a name §7 lacks. */
    private function secureValueTypeName(string $type): string
    {
        foreach (PassportDocumentSource::SECURE_VALUE_TYPE_CONSTRUCTORS as $constructor => $plain) {
            if ($plain === $type) {
                return $constructor;
            }
        }

        return '';
    }
}
