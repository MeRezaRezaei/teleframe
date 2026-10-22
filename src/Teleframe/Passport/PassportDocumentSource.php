<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportDocumentSource — THE READER. It turns one raw webhook update plus
 * one decrypted `Credentials` blob into the §4 envelopes the resolver needs,
 * across both wire shapes, and it is the ONLY place that decides which
 * encoding a field arrived in.
 *
 * ══ WHAT IT READS, AND FROM WHERE ═════════════════════════════════════════
 *
 * Per upstream, `Credentials` "contain the data_hash and data_secret from
 * each element of Telegram Passport to which the user has allowed access",
 * plus the nonce. So the decrypted blob is the index of per-type §4
 * `DataCredentials`, and the two shapes then supply the two things it cannot:
 *
 *   |                        | Bot API                          | MTProto                                     |
 *   |------------------------|----------------------------------|---------------------------------------------|
 *   | index                  | `credentials.secure_data[type]`   | `credentials.secure_data[type]`             |
 *   | ciphertext for `data`  | `PassportData.data[].data` (b64) | `SecureValue.data` (`secureData`, RAW)      |
 *   | files                  | `PassportFile` (id/size/date)    | `SecureFile` (id/access_hash/hash/secret)   |
 *   | §6 claims              | element `email` / `phone_number` | `SecureValue.plain_data`                    |
 *
 * ══ THE ENCODING SPLIT, RESOLVED ONCE AND ONLY HERE ══════════════════════
 *
 * Four fields, three encodings, and getting any of them wrong produces an
 * `always-false` integrity check that is indistinguishable from a real tamper:
 *
 *   1. `credentials.secure_data[type].data_hash` / `.secret` — **base64 on BOTH
 *      shapes**, because these values live inside a JSON STRING on the wire.
 *      A JSON document cannot carry a raw 32-byte digest without breaking
 *      UTF-8, so Telegram base64-encodes them here.
 *   2. `PassportData.data[].data` — **base64**. The Bot API says so in as many
 *      words: "Base64-encoded encrypted Telegram Passport element data".
 *   3. `SecureValue.data.{data,data_hash,secret}` — **RAW**, per
 *      `secureData#8aeabec3 data:bytes data_hash:bytes secret:bytes` and
 *      upstream's "All hashes are raw binary, not hexits".
 *   4. `SecureFile.{file_hash,secret}` — **RAW**, same reason.
 *
 * So `fromUpdate()` decodes (1) and (2) exactly once and passes (3) and (4)
 * through untouched, and every downstream consumer takes raw bytes and cannot
 * guess. A base64 field that does not decode, or a decoded digest that is not
 * exactly 32 bytes, is NOT silently coerced — it is remembered per type and
 * surfaces as {@see PassportDocumentRefusal::ENCODING_INVALID}, which is the
 * raw-vs-base64 trap given a name instead of an always-false `hash_equals`.
 *
 * ══ §7 IS A TABLE, AND THIS CLASS IS ITS SINGLE SOURCE ════════════════════
 *
 * {@see self::ALLOWED_SLOTS} is upstream's "Type / Allowed fields" table,
 * transcribed. It decides two things and both are refusals when the payload
 * disagrees:
 *
 *   - a type asked for in a slot §7 does not grant it gets
 *     {@see PassportDocumentRefusal::SLOT_ABSENT}. The five file-only types
 *     (`utility_bill`, `bank_statement`, `rental_agreement`,
 *     `passport_registration`, `temporary_registration`) therefore have NO
 *     `data` entry, and a `data` envelope for one of them is a protocol
 *     violation rather than an empty document;
 *   - a slot §7 DOES grant, on a type Telegram sent nothing for, gets
 *     {@see PassportDocumentRefusal::ABSENT}. "You may not ask" and "you may,
 *     and there is nothing" are different facts and a host renders them
 *     differently — the second is reportable to Telegram, the first is a
 *     configuration mistake.
 *
 * ══ NEITHER SHAPE IS A FALLBACK FOR THE OTHER ═════════════════════════════
 *
 * A Bot API update and an MTProto update are read by two independent passes,
 * and the second does not run over the first's leftovers. That is what
 * `shape()` reports, and it is a name a refusal can carry, so a host can tell
 * "this shape has no way to express that" from "this response did not include
 * it". The two shapes also disagree about a coordinate neither of them shares:
 * the Bot API's `element.hash` is the "hash for using in
 * PassportElementErrorUnspecified" (an ERROR-REPORTING handle, NOT the §4
 * `data_hash`), while MTProto's `SecureValue.hash` is the same carried
 * passthrough. Neither is read as a digest here — `PassportCredentialsVerifier`
 * already explains at length that both are carried by Telegram and derived by
 * nobody, and deriving one would be the fixed point that cannot be computed.
 *
 * Plain-PHP constructible (standalone-smoke): one static factory, no
 * constructor arguments, no container, no config, no filesystem, no I/O.
 * Zero regex (`src/Teleframe`): `is_array`/`isset`/`strlen`/`substr` only.
 */
final class PassportDocumentSource
{
    /** The Bot API shape — `message.passport_data`. */
    public const SHAPE_BOT_API = 'bot-api';

    /** The MTProto shape — `messageActionSecureValuesSentMe.values`. */
    public const SHAPE_MTPROTO = 'mtproto';

    /** Neither shape carried a document channel. */
    public const SHAPE_NONE = 'none';

    /**
     * §7 / upstream's "Type → Allowed fields" table, verbatim. This is the
     * only place the table exists; `PassportDocumentCoverage` walks it to
     * decide which slots a type must answer for, and its test pins it against
     * the vendored reference so a row cannot be added or dropped silently.
     *
     * `plain_data` is included rather than special-cased because it is what
     * §7 grants `email` and `phone_number`, and those two are `allowed_types`
     * values like any other.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_SLOTS = [
        'email' => [PassportDocumentRefusal::SLOT_PLAIN_DATA],
        'phone_number' => [PassportDocumentRefusal::SLOT_PLAIN_DATA],
        'personal_details' => [PassportDocumentRefusal::SLOT_DATA],
        'passport' => [
            PassportDocumentRefusal::SLOT_DATA,
            PassportDocumentRefusal::SLOT_FRONT_SIDE,
            PassportDocumentRefusal::SLOT_SELFIE,
            PassportDocumentRefusal::SLOT_TRANSLATION,
        ],
        'driver_license' => [
            PassportDocumentRefusal::SLOT_DATA,
            PassportDocumentRefusal::SLOT_FRONT_SIDE,
            PassportDocumentRefusal::SLOT_REVERSE_SIDE,
            PassportDocumentRefusal::SLOT_SELFIE,
            PassportDocumentRefusal::SLOT_TRANSLATION,
        ],
        'identity_card' => [
            PassportDocumentRefusal::SLOT_DATA,
            PassportDocumentRefusal::SLOT_FRONT_SIDE,
            PassportDocumentRefusal::SLOT_REVERSE_SIDE,
            PassportDocumentRefusal::SLOT_SELFIE,
            PassportDocumentRefusal::SLOT_TRANSLATION,
        ],
        'internal_passport' => [
            PassportDocumentRefusal::SLOT_DATA,
            PassportDocumentRefusal::SLOT_FRONT_SIDE,
            PassportDocumentRefusal::SLOT_SELFIE,
            PassportDocumentRefusal::SLOT_TRANSLATION,
        ],
        'address' => [PassportDocumentRefusal::SLOT_DATA],
        'utility_bill' => [
            PassportDocumentRefusal::SLOT_FILES,
            PassportDocumentRefusal::SLOT_TRANSLATION,
        ],
        'bank_statement' => [
            PassportDocumentRefusal::SLOT_FILES,
            PassportDocumentRefusal::SLOT_TRANSLATION,
        ],
        'rental_agreement' => [
            PassportDocumentRefusal::SLOT_FILES,
            PassportDocumentRefusal::SLOT_TRANSLATION,
        ],
        'passport_registration' => [
            PassportDocumentRefusal::SLOT_FILES,
            PassportDocumentRefusal::SLOT_TRANSLATION,
        ],
        'temporary_registration' => [
            PassportDocumentRefusal::SLOT_FILES,
            PassportDocumentRefusal::SLOT_TRANSLATION,
        ],
    ];

    /**
     * §8 aliases → the §7 element name, on the RESPONSE side.
     *
     * The shipped default `allowed_types` is `['personal_details', 'email',
     * 'phone']`, and `phone` is NOT a §7 name — the SecureValueTypes are
     * `secureValueTypeEmail` and `secureValueTypePhone`, and the Bot API element
     * types are `email` and `phone_number`. So a host that never edited the
     * default was asking for a type that does not exist, and the table named
     * that as a refusal instead of silently dropping it — correct, but it means
     * the shipped default cannot resolve a phone at all.
     *
     * `PassportConfig::expandScopeAlias()` already translates `phone` →
     * `phone_number` on the REQUEST side, so the request asked correctly and
     * only the response side was left un-translated: the claim arrived and the
     * table had no row for it. This table is that missing half. It is kept
     * separate from `expandScopeAlias()` on purpose — that one expands a
     * request into what to ASK for, this one names what arrived — but they must
     * agree, and the coverage test pins both against the same pair.
     *
     * @var array<string, string>
     */
    public const TYPE_ALIASES = [
        'phone' => 'phone_number',
    ];

    /** api.tl:167 — the one action whose `values` carry a `SecureValue` list. */
    private const ACTION_SENT_ME = 'messageActionSecureValuesSentMe';

    /**
     * Every `secureValueType*` constructor → the `allowed_types` name.
     *
     * PUBLIC because `PassportErrorReporter` needs the reverse direction: the
     * MTProto `secureValueError*` family spells its `type` as a CONSTRUCTOR
     * (`secureValueTypePassport`) where the Bot API `PassportElementError*`
     * family spells the same thing as the plain name (`passport`). One table,
     * two spellings, and a translation that exists in exactly one place.
     *
     * @var array<string, string>
     */
    public const SECURE_VALUE_TYPE_CONSTRUCTORS = [
        'secureValueTypePersonalDetails' => 'personal_details',
        'secureValueTypePassport' => 'passport',
        'secureValueTypeDriverLicense' => 'driver_license',
        'secureValueTypeIdentityCard' => 'identity_card',
        'secureValueTypeInternalPassport' => 'internal_passport',
        'secureValueTypeAddress' => 'address',
        'secureValueTypeUtilityBill' => 'utility_bill',
        'secureValueTypeBankStatement' => 'bank_statement',
        'secureValueTypeRentalAgreement' => 'rental_agreement',
        'secureValueTypePassportRegistration' => 'passport_registration',
        'secureValueTypeTemporaryRegistration' => 'temporary_registration',
        'secureValueTypeEmail' => 'email',
        'secureValueTypePhone' => 'phone_number',
    ];

    /** @var array<string, PassportDataEnvelope|null> §4 `data` envelopes by type */
    private array $dataEnvelopes = [];

    /** @var array<string, string> types whose encoding was refused */
    private array $encodingFaults = [];

    /** @var array<string, list<PassportFileEnvelope>> §4 file envelopes by slot key */
    private array $fileEnvelopes = [];

    /** @var list<string> the types this update actually carried */
    private array $types = [];

    /**
     * @var array<string, string> the §6 ERROR-REPORTING element hash, base64
     *
     * NOT a digest and NOT verified: on the Bot API it is the element's own
     * `hash` field ("Base64-encoded element hash for using in
     * PassportElementErrorUnspecified"), and on MTProto it is `SecureValue.hash`
     * — the field `PassportCredentialsVerifier`'s docblock shows is a base64
     * PASSTHROUGH out of Telegram's datacenter, hashed by nobody and derived by
     * nobody. It is carried here for exactly one reason: it is the handle
     * Telegram needs to name an element in a `PassportElementErrorUnspecified`,
     * so `PassportErrorReporter` can tell Telegram which element failed.
     */
    private array $elementHashes = [];

    private string $shape = self::SHAPE_NONE;

    /** @param  array<string, mixed>  $secureData  the decrypted `Credentials.secure_data` */
    private function __construct(private readonly array $secureData) {}

    /**
     * Read one update. `$secureData` is the decrypted `Credentials.secure_data`
     * — the per-type §4 `DataCredentials` index — and `$update` is the raw
     * webhook payload, which supplies the ciphertext and the files.
     *
     * Never throws. A shape this class cannot read yields `SHAPE_NONE` and
     * every lookup then answers with a NAMED refusal, because the caller's
     * contract is that a host asking for a type always gets an answer.
     *
     * @param  array<string, mixed>  $update
     * @param  array<string, mixed>  $secureData
     */
    public static function fromUpdate(array $update, array $secureData): self
    {
        $source = new self($secureData);
        $source->readBotApi($update);
        $source->readMtProto($update);

        return $source;
    }

    /** Which shape supplied the documents — {@see self::SHAPE_*}. */
    public function shape(): string
    {
        return $this->shape;
    }

    /** The `allowed_types` values this update actually carried, in §5 order. */
    public function types(): array
    {
        return $this->types;
    }

    public function hasType(string $type): bool
    {
        return in_array($type, $this->types, true);
    }

    /** §7 — the slots `$type` may occupy, or [] for a name outside the table.
     *
     *  The §8 ALIAS is resolved here, and only here, so the alias exists in one
     *  place and every caller inherits it. The row is still stamped with the
     *  name the host CONFIGURED (see `PassportDocumentCoverage::rowsFor()`),
     *  so the table stays keyed by `allowed_types` and a host reading it does
     *  not have to know Telegram's spelling.
     */
    public function allowedSlots(string $type): array
    {
        return self::ALLOWED_SLOTS[self::resolveAlias($type)] ?? [];
    }

    /** `phone` → `phone_number`; anything else is itself. */
    public static function resolveAlias(string $type): string
    {
        return self::TYPE_ALIASES[$type] ?? $type;
    }

    /**
     * Whether this shape has ANY channel a document could arrive on. A type
     * asking `hasChannelFor()` is not the same question as `hasType()`, and
     * conflating them is how "silently no document" started.
     */
    public function hasChannelFor(string $type): bool
    {
        if (! isset(self::ALLOWED_SLOTS[$type])) {
            return false;
        }

        return $this->shape !== self::SHAPE_NONE;
    }

    /**
     * The §4 `DataCredentials` for `$type`, or null when the type carried no
     * `data`. `encodingFault($type)` distinguishes "not there" from "there and
     * unreadable".
     */
    public function dataEnvelope(string $type): ?PassportDataEnvelope
    {
        return $this->dataEnvelopes[$type] ?? null;
    }

    /** Why a type's §4 fields could not be read, or '' when they read cleanly. */
    public function encodingFault(string $type): string
    {
        return $this->encodingFaults[$type] ?? '';
    }

    /**
     * The §6 element hash for `$type`, base64 — the handle
     * `PassportElementErrorUnspecified` names the element by, or '' when the
     * response carried none.
     *
     * Deliberately NOT usable as an integrity digest, and deliberately not
     * offered to anything that could mistake it for one: this is the raw
     * `EncryptedPassportElement.hash` string, passed through untouched (and
     * base64-encoded from raw on the MTProto side, because that field is raw
     * `bytes` and the error constructors want a base64 string).
     */
    public function elementHash(string $type): string
    {
        return $this->elementHashes[$type] ?? '';
    }

    /**
     * The §4 file envelopes in one slot, in Telegram's order. Empty for a slot
     * the update did not carry — the caller turns that into a named refusal,
     * because "no files" and "this slot does not exist for this type" are
     * different facts.
     *
     * @return list<PassportFileEnvelope>
     */
    public function fileEnvelopes(string $type, string $slot): array
    {
        return $this->fileEnvelopes[$type.'.'.$slot] ?? [];
    }

    // ── the two shape readers ──────────────────────────────────────────

    /**
     * Bot API — `message.passport_data.data[]`, the `EncryptedPassportElement`
     * list. `data` / `hash` on the ELEMENT are base64; `hash` there is the
     * ERROR-REPORTING element hash and is deliberately not read as a digest.
     */
    private function readBotApi(array $update): void
    {
        $passportData = $update['message']['passport_data'] ?? null;
        if (! is_array($passportData)) {
            return;
        }

        $elements = $passportData['data'] ?? null;
        if (! is_array($elements)) {
            return;
        }

        $this->shape = self::SHAPE_BOT_API;

        foreach ($elements as $element) {
            if (! is_array($element)) {
                continue;
            }

            $type = $element['type'] ?? null;
            if (! is_string($type) || ! isset(self::ALLOWED_SLOTS[$type])) {
                continue;
            }

            $this->remember($type);
            $this->readBotApiElement($type, $element);
        }
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function readBotApiElement(string $type, array $element): void
    {
        // The ELEMENT HASH IS STORED AS THE BASE64 STRING TELEGRAM SENT, not as
        // its decoded bytes. `PassportElementErrorUnspecified.element_hash` is
        // "Base64-encoded element hash", so handing the reporter raw bytes
        // would produce an error object Telegram cannot match against a
        // re-submission — a report that looks sent and is not. It is validated
        // as base64 and then carried verbatim.
        $elementHash = self::base64StringField($element, 'hash');
        if ($elementHash !== null) {
            $this->elementHashes[$type] = $elementHash;
        }

        $this->readBotApiData($type, $element);

        // THE FILES ARE READ UNCONDITIONALLY, and that is a bug this class
        // shipped for one revision: the `data` walk used to `return` early when
        // an element carried no `data`, and the file loops sat BELOW those
        // returns. That is precisely the §7 rule — `utility_bill`,
        // `bank_statement`, `rental_agreement`, `passport_registration` and
        // `temporary_registration` have NO `data` field, only `files` and
        // `translation` — so the five file-only types were the five types the
        // walk skipped, and every one of their scans came out as
        // `slot_absent` on a response that had sent them. It survived because
        // the fixtures only ever put files on types that ALSO carry `data`.
        //
        // `data` and the files are INDEPENDENT §7 slots and are now read as
        // such: neither can short-circuit the other.
        $this->readBotApiFiles($type, $element);
    }

    /**
     * The §4 `data` half. A missing `data` field is NORMAL for the file-only
     * types and for `email`/`phone_number`, so it is not a fault — it is
     * simply no envelope, and `dataEnvelope()` answers null while
     * `encodingFault()` answers ''.
     *
     * @param  array<string, mixed>  $element
     */
    private function readBotApiData(string $type, array $element): void
    {
        $ciphertext = self::base64Field($element, 'data');
        if ($ciphertext === null) {
            return;
        }

        [$hash, $secret] = $this->credentialsPair($type);

        if ($hash === null || $secret === null) {
            // §4 needs both. The index that carries them is `secure_data`, and
            // its absence is a NAMED outcome, not a reason to hand the resolver
            // a half-envelope.
            $this->encodingFaults[$type] = PassportDocumentRefusal::CREDENTIALS_ABSENT;

            return;
        }

        $envelope = PassportDataEnvelope::fromRaw($type, $ciphertext, $hash, $secret);
        if ($envelope === null) {
            $this->encodingFaults[$type] = PassportDocumentRefusal::ENCODING_INVALID;

            return;
        }

        $this->dataEnvelopes[$type] = $envelope;
    }

    /**
     * The §7 file half — the three single-file slots and the two vectors.
     *
     * @param  array<string, mixed>  $element
     */
    private function readBotApiFiles(string $type, array $element): void
    {
        foreach (PassportFileEnvelope::SINGLE_SLOTS as $slot) {
            $file = $element[$slot] ?? null;
            if (is_array($file)) {
                $this->pushFile(PassportFileEnvelope::withoutCredentials(
                    $type,
                    $slot,
                    0,
                    self::intField($file, 'file_size'),
                ));
            }
        }

        foreach (PassportFileEnvelope::VECTOR_SLOTS as $slot) {
            $files = $element[$slot] ?? null;
            if (! is_array($files)) {
                continue;
            }

            $index = 0;
            foreach ($files as $file) {
                if (! is_array($file)) {
                    continue;
                }

                $this->pushFile(PassportFileEnvelope::withoutCredentials(
                    $type,
                    $slot,
                    $index,
                    self::intField($file, 'file_size'),
                ));
                $index++;
            }
        }
    }

    /**
     * MTProto — the `values: Vector<SecureValue>` sibling of the
     * `SecureCredentialsEncrypted`, per `messageActionSecureValuesSentMe#1b287353`.
     * Every field read here is a RAW `bytes` field and is passed through
     * untouched.
     */
    private function readMtProto(array $update): void
    {
        $values = self::sentValues($update);
        if (! is_array($values)) {
            return;
        }

        $this->shape = self::SHAPE_MTPROTO;

        foreach ($values as $value) {
            if (! is_array($value)) {
                continue;
            }

            $type = self::SECURE_VALUE_TYPE_CONSTRUCTORS[self::constructorOf($value['type'] ?? null)] ?? '';
            if ($type === '') {
                // FAIL CLOSED: a `type` this class cannot name is a refusal,
                // never a wildcard. Same rule as `SecureValueTraversal`.
                continue;
            }

            $this->remember($type);
            $this->readMtProtoValue($type, $value);

            // `SecureValue.hash` is RAW `bytes`; the error constructors want a
            // base64 string, so it is encoded ONCE here rather than by each
            // consumer. It is a carried pass-through, not a derived digest — see
            // `PassportCredentialsVerifier` on why nobody derives it.
            $valueHash = self::bytesField($value, 'hash');
            if ($valueHash !== null) {
                $this->elementHashes[$type] = base64_encode($valueHash);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function readMtProtoValue(string $type, array $value): void
    {
        $data = $value['data'] ?? null;
        if (is_array($data)) {
            $this->readMtProtoSecureData($type, $data);
        }

        foreach (PassportFileEnvelope::SINGLE_SLOTS as $slot) {
            $file = $value[$slot] ?? null;
            if (! is_array($file)) {
                continue;
            }

            $this->readMtProtoSecureFile($type, $slot, 0, $file);
        }

        foreach (PassportFileEnvelope::VECTOR_SLOTS as $slot) {
            $files = $value[$slot] ?? null;
            if (! is_array($files)) {
                continue;
            }

            $index = 0;
            foreach ($files as $file) {
                if (! is_array($file)) {
                    continue;
                }

                $this->readMtProtoSecureFile($type, $slot, $index, $file);
                $index++;
            }
        }
    }

    /**
     * `secureData#8aeabec3 data:bytes data_hash:bytes secret:bytes`. RAW, so
     * the digest arrives as 32 bytes and is passed straight on. When the
     * `SecureValue` carries no `data` branch but `secure_data[type]` does, the
     * index supplies the credentials and the element supplies nothing — the
     * §4 envelope is then incomplete without ciphertext, which is a refusal.
     *
     * @param  array<string, mixed>  $data
     */
    private function readMtProtoSecureData(string $type, array $data): void
    {
        $ciphertext = self::bytesField($data, 'data');
        $hash = self::bytesField($data, 'data_hash');
        $secret = self::bytesField($data, 'secret');

        if ($hash === null || strlen($hash) !== 32) {
            $this->encodingFaults[$type] = PassportDocumentRefusal::ENCODING_INVALID;

            return;
        }

        if ($secret === null) {
            $this->encodingFaults[$type] = PassportDocumentRefusal::CREDENTIALS_ABSENT;

            return;
        }

        if ($ciphertext === null) {
            return; // a `secureData` ctor with no data is no envelope, not a fault
        }

        $envelope = PassportDataEnvelope::fromRaw($type, $ciphertext, $hash, $secret);
        if ($envelope === null) {
            $this->encodingFaults[$type] = PassportDocumentRefusal::ENCODING_INVALID;

            return;
        }

        $this->dataEnvelopes[$type] = $envelope;
    }

    /**
     * `secureFile#7d09c27e id access_hash size dc_id date file_hash secret` —
     * the ONLY shape that carries §4 `FileCredentials` at all.
     *
     * @param  array<string, mixed>  $file
     */
    private function readMtProtoSecureFile(string $type, string $slot, int $index, array $file): void
    {
        // `secureFileEmpty#64199744` carries no id, hash or secret at all.
        if (self::constructorOf($file) === 'secureFileEmpty') {
            $this->pushFile(PassportFileEnvelope::withoutCredentials($type, $slot, $index, 0));

            return;
        }

        $hash = self::bytesField($file, 'file_hash');
        $secret = self::bytesField($file, 'secret');

        $this->pushFile(PassportFileEnvelope::fromWire(
            $type,
            $slot,
            $index,
            self::intField($file, 'id'),
            self::intField($file, 'access_hash'),
            $hash ?? '',
            $secret ?? '',
            self::intField($file, 'size'),
        ));
    }

    // ── the shared legs ────────────────────────────────────────────────

    /**
     * The §4 `data_hash` / `secret` for `$type`, decoded from the base64 the
     * decrypted `Credentials` JSON carries. `[null, null]` when the index has
     * no entry for the type.
     *
     * @return array{?string, ?string}
     */
    private function credentialsPair(string $type): array
    {
        $entry = $this->secureData[$type] ?? null;
        if (! is_array($entry)) {
            return [null, null];
        }

        $hash = self::base64Field($entry, 'data_hash');
        $secret = self::base64Field($entry, 'secret');

        if ($hash === null || strlen($hash) !== 32) {
            return [null, null];
        }

        return [$hash, $secret];
    }

    private function pushFile(PassportFileEnvelope $envelope): void
    {
        $this->fileEnvelopes[$envelope->type().'.'.$envelope->slot()][] = $envelope;
    }

    private function remember(string $type): void
    {
        if (! in_array($type, $this->types, true)) {
            $this->types[] = $type;
        }
    }

    /**
     * The `values` list of a `messageActionSecureValuesSentMe`, or null. Only
     * an action that NAMES ITSELF is accepted — the same constructor-not-key-
     * presence rule `EncryptedCredentials` uses, for the same reason.
     *
     * @param  array<string, mixed>  $update
     * @return mixed
     */
    private static function sentValues(array $update)
    {
        $candidates = [
            $update['message']['action'] ?? null,
            $update['messageService']['action'] ?? null,
            $update['message_service']['action'] ?? null,
            $update['action'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            if (($candidate['_'] ?? null) !== self::ACTION_SENT_ME) {
                continue;
            }

            return $candidate['values'] ?? null;
        }

        return null;
    }

    private static function constructorOf(mixed $node): string
    {
        return is_array($node) && is_string($node['_'] ?? null) ? $node['_'] : '';
    }

    /** A RAW `bytes` field, or null. Never decoded — MTProto is raw (§1). */
    private static function bytesField(array $node, string $key): ?string
    {
        $value = $node[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** A base64 field, strictly decoded. `false` is refused, never coerced. */
    private static function base64Field(array $node, string $key): ?string
    {
        $decoded = self::base64StringField($node, $key);

        return $decoded === null ? null : base64_decode($decoded, true);
    }

    /**
     * A base64 field carried as the STRING Telegram sent, validated by decoding
     * it and then keeping the original. Used only where the downstream
     * consumer needs the encoded form (the §6 element hash).
     */
    private static function base64StringField(array $node, string $key): ?string
    {
        $value = $node[$key] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = base64_decode($value, true);

        return $decoded === false || $decoded === '' ? null : $value;
    }

    private static function intField(array $node, string $key): int
    {
        $value = $node[$key] ?? null;

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    }
}
