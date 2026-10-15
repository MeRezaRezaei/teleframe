<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportDocumentRefusal — THE NAMED REFUSAL. The only thing this module is
 * allowed to produce instead of a document, and the reason the whole document
 * half is countable.
 *
 * ══ WHY THIS CLASS EXISTS, AND WHAT IT REPLACES ═══════════════════════════
 *
 * Before it, the document surface did not exist AT ALL, and the observable
 * consequence was the worst kind of failure this package can produce: a
 * SUCCESS. `allowed_types` accepted eleven §5 document types, the login chain
 * validated them by name and then read nothing out of any of them, and a
 * response carrying a passport produced a `LoginResult` whose
 * `fields_verified` listed `passport` while no passport had ever been opened.
 * Nothing threw. Nothing logged. The host's own test suite — 385 methods —
 * was green, because "green" only ever meant "the name was on the list".
 *
 * The rule this class makes enforceable:
 *
 *   > **every `allowed_types` value produces a document OR A NAMED REFUSAL.
 *   > Never a silent absence.**
 *
 * "Silent absence" is not a return value. It is not an empty array, and it is
 * not an exception message that says only "failed". It is the outcome where a
 * host asks for `driver_license`, gets nothing, cannot tell that from "the user
 * declined to send one", and has no error to show Telegram or to log. Every
 * refusal therefore carries THREE things that are always present:
 *
 *   - the `type` — which `allowed_types` value this answers;
 *   - the `slot` — which §7 field of that type (see {@see self::SLOTS}), so a
 *     host can tell "the passport's front side" from "the passport's selfie";
 *   - the `code` — a member of a CLOSED vocabulary ({@see self::CODES}).
 *
 * The code is closed on purpose, and that is the load-bearing part: a free-text
 * message is not countable, cannot be aggregated, and cannot be a regression
 * gate. `PassportDocumentCoverage` pins the vocabulary against the §7 table, so
 * a new failure mode has to be NAMED before it can be reported — which is the
 * same discipline as the endpoint-coverage gate elsewhere in this repository:
 * make the gap countable rather than invisible.
 *
 * ══ WHY A REFUSAL AND NOT AN EXCEPTION ═══════════════════════════════════
 *
 * Three reasons, and they are not interchangeable:
 *
 *  1. A refusal is an OUTCOME, not a crash. The chain's own failures are
 *     exceptions and stay exceptions (`PassportLoginException`), because a
 *     failed login must stop the login. A missing document must not: the host
 *     asked for eleven types, the user supplied four, and the right behaviour
 *     is to log in on the four and REPORT the seven — which is precisely what
 *     `setPassportDataErrors` exists to tell Telegram about.
 *  2. A refusal must be collectable ACROSS a whole scope. `codesByType()` is
 *     the input to `PassportErrorReporter`, so a refusal that threw could not
 *     be reported at all — the exception would have replaced the report.
 *  3. It keeps the one-error-oracle hardening of the crypto intact.
 *     `PassportDecryptor::DECRYPT_ERROR` and
 *     `PassportCredentialsVerifier::INTEGRITY_ERROR` are deliberately
 *     indistinguishable from each other so a prober learns nothing about which
 *     leg ran. A document refusal is not an oracle — it is a statement about
 *     the HOST'S OWN allow-list versus what Telegram sent, made to the host —
 *     so it may be as specific as it needs to be. It never quotes a plaintext,
 *     a digest or a value: `message()` is built from the code and the two
 *     names, nothing else.
 *
 * SECURITY: `type` and `slot` are §7 vocabulary names and `code` is a member of
 * this file's own list. No value, plaintext, digest or key material can reach
 * this object, so `message()` is safe to log as it stands.
 *
 * Plain-PHP constructible (standalone-smoke): two named static factories and
 * four readers, no constructor arguments, no container, no config.
 * Zero regex (this file lives in `src/Teleframe`): `in_array` / `sprintf`.
 */
final class PassportDocumentRefusal
{
    /** §7 — the `SecureValue` fields a document can occupy. */
    public const SLOT_DATA = 'data';

    public const SLOT_PLAIN_DATA = 'plain_data';

    public const SLOT_FRONT_SIDE = 'front_side';

    public const SLOT_REVERSE_SIDE = 'reverse_side';

    public const SLOT_SELFIE = 'selfie';

    public const SLOT_TRANSLATION = 'translation';

    public const SLOT_FILES = 'files';

    /**
     * The closed `slot` vocabulary, in §7 field order. Pinned against the
     * `SecureValue` field list so a new slot cannot appear unlisted.
     *
     * @var list<string>
     */
    public const SLOTS = [
        self::SLOT_DATA,
        self::SLOT_PLAIN_DATA,
        self::SLOT_FRONT_SIDE,
        self::SLOT_REVERSE_SIDE,
        self::SLOT_SELFIE,
        self::SLOT_TRANSLATION,
        self::SLOT_FILES,
    ];

    // ── the closed `code` vocabulary ───────────────────────────────────
    //
    // Every one of these is a distinct, host-actionable outcome. "Unknown" is
    // deliberately NOT a member: a code that means "something went wrong" is
    // the silent absence wearing a name, and the whole point of the vocabulary
    // is that a reader can act on each entry without asking what happened.

    /** The host allowed this type; Telegram sent nothing for it at all. */
    public const ABSENT = 'document_absent';

    /** The type arrived, but carries none of this slot (§7 forbids it there). */
    public const SLOT_ABSENT = 'slot_absent';

    /** `Credentials.secure_data[type]` carried no `data_hash` / `secret`. */
    public const CREDENTIALS_ABSENT = 'credentials_absent';

    /** The element carries no `data` ciphertext to decrypt. */
    public const CIPHERTEXT_ABSENT = 'ciphertext_absent';

    /**
     * A field did not arrive in an encoding this class will accept — a
     * `data_hash` that is not 32 bytes once decoded, or a base64 that does not
     * decode. This is the RAW-vs-BASE64 trap made nameable: the failure is
     * reported against the ENCODING, at the boundary, instead of surfacing
     * later as a hash comparison that can never pass.
     */
    public const ENCODING_INVALID = 'encoding_invalid';

    /** The §4 transport check or the nested decrypt refused the bytes. */
    public const INTEGRITY_FAILED = 'integrity_failed';

    /**
     * This shape's file reference carries no `file_hash` / `secret`, so the
     * file cannot be decrypted by anyone — including Telegram. It is the Bot
     * API `PassportFile` case (`file_id`, `file_unique_id`, `file_size`,
     * `file_date` — and nothing else), and it is a genuine property of the
     * shape, not a bug in this package: the download is possible there, the
     * DECRYPT is not.
     */
    public const FILE_HASH_UNAVAILABLE = 'file_hash_unavailable';

    /** No `PassportWireContract` is bound, so nothing can be downloaded. */
    public const WIRE_UNAVAILABLE = 'wire_unavailable';

    /** `upload.getFile` refused, or returned no bytes. */
    public const DOWNLOAD_FAILED = 'download_failed';

    /** §4 — "Files are JPG, max 10 MB." Refused before it is buffered whole. */
    public const FILE_TOO_LARGE = 'file_too_large';

    /**
     * `email` / `phone_number` were allowed and Telegram verified no such
     * claim (§6). Not an error in the payload — §6 makes the value optional —
     * but a named absence, because "no verified email" and "the email was
     * rejected" are different facts and a host renders them differently.
     */
    public const CLAIM_ABSENT = 'claim_absent';

    /**
     * The closed `code` vocabulary. `PassportDocumentCoverageTest` pins this
     * list against the codes the production path can actually emit, so a code
     * that is declared but unreachable — or reachable but undeclared — is a
     * failure rather than a curiosity.
     *
     * @var list<string>
     */
    public const CODES = [
        self::ABSENT,
        self::SLOT_ABSENT,
        self::CREDENTIALS_ABSENT,
        self::CIPHERTEXT_ABSENT,
        self::ENCODING_INVALID,
        self::INTEGRITY_FAILED,
        self::FILE_HASH_UNAVAILABLE,
        self::WIRE_UNAVAILABLE,
        self::DOWNLOAD_FAILED,
        self::FILE_TOO_LARGE,
        self::CLAIM_ABSENT,
    ];

    /** A type that could not even be named (an allow-list entry read as empty). */
    public const UNKNOWN_TYPE = '(unnamed)';

    /**
     * @param  string  $type  the `allowed_types` value this answers — always non-empty
     * @param  string  $slot  the §7 slot — always a member of {@see self::SLOTS}
     * @param  string  $code  a member of {@see self::CODES}
     * @param  string  $detail  an operator-facing sentence, NEVER a value
     */
    private function __construct(
        public readonly string $type,
        public readonly string $slot,
        public readonly string $code,
        private readonly string $detail,
    ) {}

    /**
     * The named refusal.
     *
     * An unrecognised SLOT or CODE is coerced to the closest honest statement
     * rather than accepted, so a caller cannot smuggle a silent absence in
     * under a code that says nothing. The TYPE is never coerced, and that is
     * deliberate: `PassportDocumentCoverage` looks rows up BY the type string
     * it was given, so rewriting `''` to a placeholder here would make
     * `for('')` return [] while a row existed under the placeholder — which is
     * the row-count floor failing from the other direction. An empty type
     * stays empty, is reported as such, and is still a row.
     */
    public static function named(string $type, string $slot, string $code, string $detail = ''): self
    {
        return new self(
            $type,
            in_array($slot, self::SLOTS, true) ? $slot : self::SLOT_DATA,
            in_array($code, self::CODES, true) ? $code : self::ABSENT,
            $detail,
        );
    }

    /**
     * The one-line rendering a host may log verbatim. Built from the two
     * §7 NAMES and the code — never from a plaintext, a digest or a value,
     * because the only inputs this object accepts are vocabulary words.
     */
    public function message(): string
    {
        $sentence = $this->detail === '' ? '' : ' '.$this->detail;

        return sprintf(
            'Telegram Passport refused %s.%s: %s.%s',
            $this->type === '' ? self::UNKNOWN_TYPE : $this->type,
            $this->slot,
            $this->code,
            $sentence,
        );
    }

    /** `type.slot` — the addressable key, matching the coverage table's. */
    public function key(): string
    {
        return ($this->type === '' ? self::UNKNOWN_TYPE : $this->type).'.'.$this->slot;
    }

    /**
     * @return array{type: string, slot: string, code: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'slot' => $this->slot,
            'code' => $this->code,
            'message' => $this->message(),
        ];
    }

    public function is(string $code): bool
    {
        return $this->code === $code;
    }
}
