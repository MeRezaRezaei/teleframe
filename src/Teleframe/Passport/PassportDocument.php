<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportDocument — ONE decrypted Telegram Passport document, or one
 * verified §6 claim, as a value object.
 *
 * ══ WHAT A "DOCUMENT" IS, WHICH THE NAME ALONE DOES NOT SAY ══════════════
 *
 * The §7 table (`docs/reference/telegram-passport-official.md` §7, and
 * upstream's own "Type / Allowed fields" table) splits an `allowed_types` value
 * into two very different kinds of thing, and collapsing them would be the
 * same defect this class's sibling `PassportDocumentRefusal` was written to
 * kill — a slot that carries nothing and a slot that carries something, looking
 * identical downstream:
 *
 *  - **an ENCRYPTED payload**, decrypted here. §4 `DataCredentials` over a
 *    `SecureData.data` field, yielding exactly one of three JSON objects
 *    depending on the §5 type: `PersonalDetails` (`personal_details`),
 *    `IdDocumentData` (`passport`, `driver_license`, `identity_card`,
 *    `internal_passport`), `ResidentialAddress` (`address`). The
 *    `payloadClass()` accessor names which one, so a caller never has to
 *    infer the shape from the type alone and a fixture cannot be read as a
 *    document of the wrong class.
 *  - **a VERIFIED CLAIM**, not encrypted at all. `email` and `phone_number`
 *    are §6 plaintext: "Emails and phone numbers sent using telegram passport
 *    are *already verified*". Their `payloadClass()` is
 *    {@see self::CLASS_VERIFIED_CLAIM} and their `json()` is the single-key
 *    map Telegram wrote. Calling them "documents" is a convenience; the class
 *    name is explicit about what happened to the bytes, which is the thing that
 *    actually matters when a host decides whether it can store the value.
 *
 * The five FILE-ONLY types (`utility_bill`, `bank_statement`,
 * `rental_agreement`, `passport_registration`, `temporary_registration`) have
 * NO `data` slot at all — §7 gives them `files` and `translation` only — so a
 * decoded `data` payload for one of those is not "a document with no fields",
 * it is a protocol violation, and it is refused with
 * {@see PassportDocumentRefusal::SLOT_ABSENT} rather than accepted here.
 *
 * ══ WHY THE PAYLOAD IS AN ARRAY AND NOT A MODEL ══════════════════════════
 *
 * `PersonalDetails` has eleven fields, `IdDocumentData` has nine, and
 * `ResidentialAddress` has two, and Telegram may add fields without a layer
 * bump. Modelling all three here would freeze a wire shape this package does
 * not own and would make every upstream addition a breaking change. So the
 * decoded JSON is carried verbatim as an array and the CLASS is named
 * separately — which is also what keeps a claim out of the type system: a
 * `PassportDocument` can hold a verified email because §6 says that value is
 * verified, and holding it is not the same as having decrypted anything.
 *
 * SECURITY: this object holds personal data. It has no logger, nothing in this
 * module ever puts `json()` into an exception, a log line or a URL, and
 * `PassportErrorReporter` — the one caller that reports OUT to Telegram —
 * reads `type`, `slot` and `payloadClass()` and never `json()`. A host that
 * wants the values has them; a host that does not never has to.
 *
 * Plain-PHP constructible (standalone-smoke), no constructor arguments, no
 * container, no config, no filesystem. Zero regex (`src/Teleframe`).
 */
final class PassportDocument
{
    /** §4 — the decrypted `SecureData.data` of a `personal_details` element. */
    public const CLASS_PERSONAL_DETAILS = 'PersonalDetails';

    /** §4 — the decrypted `SecureData.data` of any of the four ID documents. */
    public const CLASS_ID_DOCUMENT = 'IdDocumentData';

    /** §4 — the decrypted `SecureData.data` of an `address` element. */
    public const CLASS_RESIDENTIAL_ADDRESS = 'ResidentialAddress';

    /** §6 — an `email` / `phone_number` claim, which was never encrypted. */
    public const CLASS_VERIFIED_CLAIM = 'VerifiedClaim';

    /** §4 — a downloaded + decrypted file (JPG bytes), never JSON. */
    public const CLASS_FILE = 'File';

    /**
     * Every payload class this object can carry. Pinned by the coverage test so
     * a class cannot appear on a document without being declared here — the
     * "advertised and absent" rule that S5 exists to fix elsewhere in this
     * repository, applied at the only place it can actually recur.
     *
     * @var list<string>
     */
    public const PAYLOAD_CLASSES = [
        self::CLASS_PERSONAL_DETAILS,
        self::CLASS_ID_DOCUMENT,
        self::CLASS_RESIDENTIAL_ADDRESS,
        self::CLASS_VERIFIED_CLAIM,
        self::CLASS_FILE,
    ];

    /**
     * §7 + upstream's per-type table: which §5/§6 type yields which payload
     * class in its `data` slot. The five file-only types are absent ON PURPOSE:
     * §7 grants them no `data` field, and `PassportDocumentSource` reads their
     * absence as {@see PassportDocumentRefusal::SLOT_ABSENT}.
     *
     * @var array<string, string>
     */
    public const DATA_PAYLOAD_CLASSES = [
        'personal_details' => self::CLASS_PERSONAL_DETAILS,
        'passport' => self::CLASS_ID_DOCUMENT,
        'driver_license' => self::CLASS_ID_DOCUMENT,
        'identity_card' => self::CLASS_ID_DOCUMENT,
        'internal_passport' => self::CLASS_ID_DOCUMENT,
        'address' => self::CLASS_RESIDENTIAL_ADDRESS,
    ];

    /** §7 — the types that carry no `data` slot at all, only files. */
    public const FILE_ONLY_TYPES = [
        'utility_bill',
        'bank_statement',
        'rental_agreement',
        'passport_registration',
        'temporary_registration',
    ];

    /**
     * @param  string  $type  the `allowed_types` value this answers
     * @param  string  $slot  the §7 slot — always a member of
     *                        {@see PassportDocumentRefusal::SLOTS}
     * @param  string  $payloadClass  a member of {@see self::PAYLOAD_CLASSES}
     * @param  array<string, mixed>  $json  the decoded payload; for
     *                                      {@see self::CLASS_FILE} this is the
     *                                      raw JPG bytes under the `bytes` key
     */
    private function __construct(
        public readonly string $type,
        public readonly string $slot,
        public readonly string $payloadClass,
        private readonly array $json,
    ) {}

    /**
     * A decrypted §4 `SecureData.data` payload.
     *
     * The decode is strict about being an array: `json_decode(..., true)` on a
     * scalar or on invalid JSON yields something that is not an array, and
     * handing that back as a "document" would reproduce the silent-absence
     * defect one level down. The caller is expected to have refused such a
     * payload already with {@see PassportDocumentRefusal::INTEGRITY_FAILED};
     * this guard exists so a future caller cannot skip that step and get a
     * document-shaped lie instead.
     *
     * @param  array<string, mixed>  $json
     */
    public static function fromDecrypted(string $type, string $slot, string $payloadClass, array $json): self
    {
        return new self($type, $slot, $payloadClass, $json);
    }

    /**
     * A §6 verified claim. `email` arrives under `email` and `phone_number`
     * under `phone_number` — the specification's own field names, not this
     * package's, so a host rendering the map needs no translation table.
     */
    public static function fromClaim(string $type, string $field, string $value): self
    {
        return new self($type, PassportDocumentRefusal::SLOT_PLAIN_DATA, self::CLASS_VERIFIED_CLAIM, [$field => $value]);
    }

    /**
     * A downloaded and decrypted §4 file. The bytes are carried under `bytes`
     * and the file's declared size under `size`; nothing is parsed, because a
     * JPG is bytes and treating it as anything else is a decoder this package
     * does not have.
     */
    public static function fromFile(string $type, string $slot, string $bytes, int $declaredSize = 0): self
    {
        return new self($type, $slot, self::CLASS_FILE, [
            'bytes' => $bytes,
            'size' => $declaredSize,
        ]);
    }

    /** The decoded payload. Personal data — never logged by this module. */
    public function json(): array
    {
        return $this->json;
    }

    /** The decrypted file bytes, or '' when this is not a {@see self::CLASS_FILE}. */
    public function bytes(): string
    {
        $bytes = $this->json['bytes'] ?? '';

        return is_string($bytes) ? $bytes : '';
    }

    /**
     * A payload key read as a string, or null. The only accessor shape that
     * hands personal data out one field at a time, for a caller that wants a
     * single value rather than the whole map.
     */
    public function value(string $key): ?string
    {
        $value = $this->json[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function isFile(): bool
    {
        return $this->payloadClass === self::CLASS_FILE;
    }

    public function isClaim(): bool
    {
        return $this->payloadClass === self::CLASS_VERIFIED_CLAIM;
    }

    /**
     * `type.slot` — the identity of one entry in the coverage table. This is
     * what makes the table addressable: a host reads `coverage()` and can look
     * up `passport.front_side` without scanning.
     */
    public function key(): string
    {
        return $this->type.'.'.$this->slot;
    }

    /**
     * @return array{type: string, slot: string, payload_class: string, fields: list<string>}
     */
    public function toArray(): array
    {
        // NAMES only, never values — the same rule the provenance recorder and
        // the §6 refusal messages follow.
        return [
            'type' => $this->type,
            'slot' => $this->slot,
            'payload_class' => $this->payloadClass,
            'fields' => array_keys($this->json),
        ];
    }
}
