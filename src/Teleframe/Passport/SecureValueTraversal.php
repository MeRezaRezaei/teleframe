<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * SecureValueTraversal — the MTProto `values: Vector<SecureValue>` walk, the
 * traversal `PassportVerifiedClaims` documents as ABSENT.
 *
 * ══ WHY IT EXISTS ═══════════════════════════════════════════════════════
 *
 * §6 of `docs/reference/telegram-passport-official.md` puts the Telegram
 * VERIFIED email and phone in exactly two places, and the package read only
 * the first:
 *
 *   - Bot API: plaintext strings on the `EncryptedPassportElement` entries of
 *     `PassportData.data[]` — implemented by
 *     `PassportVerifiedClaims::fromPassportData()`.
 *   - MTProto: `secureValue.plain_data` as `securePlainEmail` /
 *     `securePlainPhone` — NOT implemented until now, so an MTProto-shaped
 *     claim-bearing update yielded no claim at all and every field-mode login
 *     on that shape was refused.
 *
 * The traversal is COMPLETE over the §7 `SecureValue` field list, not just the
 * `plain_data` leaf: `type`, the nested `data` (`SecureData`), `front_side`,
 * `reverse_side`, `selfie`, the `translation` and `files` vectors, and
 * `plain_data`. That completeness is the point — a half-walk is how the
 * previous defect shipped green — and it is observable: `branchesVisited()`,
 * `secureDataCount()` and `fileCount()` report what was actually reached, so
 * a test can prove a branch was visited rather than inferred.
 *
 * It does NOT decrypt anything. The nested `SecureData.data` is an encrypted
 * AES payload (§4) and the files are ciphertext to be downloaded with
 * `help.getFile`; decrypting them is a different concern
 * (`PassportDecryptor::decryptDataCredentials`) and this class only COUNTS
 * them. In particular an identity is NEVER read out of a `SecureData`, out of
 * a document payload, or out of any coordinate other than `plain_data` — the
 * same rule the Bot API side applies to an element's `data` sub-object.
 *
 * ══ WHAT SHAPE IT ACCEPTS ═══════════════════════════════════════════════
 *
 * BOTH spellings of the same constructor, because both are real and neither
 * is invented here:
 *
 *   1. the RAW wire array, whose keys are the TL field names of
 *      `secureValue#187fa0ca` — `type`, `data`, `front_side`, `reverse_side`,
 *      `selfie`, `translation`, `files`, `plain_data`, `hash` — with the
 *      constructor name in `_` at each nested level (`securePlainEmail`,
 *      `secureValueTypePassport`, `secureData`, `secureFile`, …);
 *   2. the GENERATED DTOs this package already ships for these constructors
 *      (`Schema\Generated\Data\Types\SecureValueData`,
 *      `SecurePlainEmailData`, `SecurePlainPhoneData`, `SecureDataData`,
 *      `SecureFileData`, `SecureValueType*Data`), whose properties are the
 *      camelCase of the same field names (`plainData`, `frontSide`, …). The
 *      constructor name is recovered from the class name
 *      (`SecurePlainEmailData` → `securePlainEmail`), so the two forms are
 *      read by ONE rule and cannot drift apart.
 *
 * The generated artifacts are NOT edited; they are read.
 *
 * ══ THE THREE RULES THAT ARE REFUSALS, NOT FALLBACKS ════════════════════
 *
 *  - the value's `type` is what permits a `plain_data` leaf at all. Per the
 *    §7 table only `secureValueTypeEmail` and `secureValueTypePhone` may carry
 *    one, so a `plain_data` on a `secureValueTypePassport` is a protocol
 *    violation and yields nothing. A payload that puts a claim where Telegram
 *    does not put one must not become an identity. The gate FAILS CLOSED: a
 *    type this class cannot NAME is refused, never treated as permission.
 *  - the leaf's own constructor must be one of the two §6 leaves
 *    (`securePlainEmail` / `securePlainPhone`). A leaf that arrived with no
 *    constructor at all falls back to its field name, symmetrically for both
 *    §6 fields; a leaf that names a THIRD constructor is not read.
 *  - two leaves of the same claim type that DISAGREE yield NO claim, exactly
 *    as on the Bot API side: picking a winner would let whoever can append a
 *    second `SecureValue` choose which identity a login resolves. Emails are
 *    compared on a normalised form (domain case-folded, one trailing dot
 *    stripped, local part byte-exact) so that a second leaf differing only in
 *    SPELLING can neither change nor deny the identity — see `agreeing()` for
 *    why that is a tightening rather than a loosening, and for the residual
 *    risk it assumes. Phones stay byte-exact.
 *
 * SECURITY: this class reads personal data and never emits it anywhere else.
 * It has no logger, it puts no value in an exception message (it never throws),
 * and the only place a value leaves it is `claims()`, which hands it to the
 * same carrier the Bot API path uses. Type NAMES and counts are what travel.
 *
 * Zero regex (`is_string`, `strrpos`, `substr`, `str_ends_with` only, because
 * `src/Teleframe` is a strictly zero-regex tree); plain-PHP constructible
 * (standalone-smoke): one static factory, no constructor arguments, no
 * container, no config, no filesystem.
 */
final class SecureValueTraversal
{
    /** §7 — the only two `SecureValueType`s allowed to carry `plain_data`. */
    private const PLAIN_EMAIL_TYPE = 'secureValueTypeEmail';

    private const PLAIN_PHONE_TYPE = 'secureValueTypePhone';

    /** §6 — the only two `SecurePlainData` constructors, and the only two
     *  `plain_data` leaves a claim may ever be read out of. */
    private const LEAF_EMAIL = 'securePlainEmail';

    private const LEAF_PHONE = 'securePlainPhone';

    private const CONSTRUCTOR_KEY = '_';

    private const DATA_SUFFIX = 'Data';

    private const BRANCH_DATA = 'data';

    private const BRANCH_PLAIN_DATA = 'plain_data';

    private const BRANCH_FRONT_SIDE = 'front_side';

    private const BRANCH_REVERSE_SIDE = 'reverse_side';

    private const BRANCH_SELFIE = 'selfie';

    private const BRANCH_TRANSLATION = 'translation';

    private const BRANCH_FILES = 'files';

    /** @var list<string> the single-file branches of §7, in field order */
    private const FILE_BRANCHES = [
        self::BRANCH_FRONT_SIDE,
        self::BRANCH_REVERSE_SIDE,
        self::BRANCH_SELFIE,
    ];

    /** @var list<string> the Vector<SecureFile> branches of §7, in field order */
    private const FILE_VECTOR_BRANCHES = [
        self::BRANCH_TRANSLATION,
        self::BRANCH_FILES,
    ];

    /** @var list<string> */
    private array $typeNames = [];

    /** @var list<string> */
    private array $branches = [];

    private int $valueCount = 0;

    private int $secureDataCount = 0;

    private int $fileCount = 0;

    /** @var list<string> */
    private array $emails = [];

    /** @var list<string> */
    private array $phones = [];

    private function __construct() {}

    /**
     * Walk a `values: Vector<SecureValue>` — a list of values, one value, or
     * anything else, which yields the empty traversal rather than a throw. A
     * webhook intake must stay quiet on shapes that are not passport data.
     */
    public static function fromValues(mixed $values): self
    {
        $traversal = new self;

        foreach (self::entries($values) as $entry) {
            $traversal->visit($entry);
        }

        return $traversal;
    }

    /**
     * The §6 claims this traversal found, in the SAME `PassportVerifiedClaims`
     * the Bot API shape produces. A walk that reached no `plain_data`, or
     * reached a contradictory one, yields `none()` — and the service layer
     * then refuses loudly, which is the required outcome, never a silent
     * downgrade to some other coordinate.
     */
    public function claims(): PassportVerifiedClaims
    {
        return PassportVerifiedClaims::fromClaimValues(
            self::agreeing($this->emails),
            self::agreeing($this->phones),
        );
    }

    /** How many `SecureValue`s the walk reached. */
    public function valueCount(): int
    {
        return $this->valueCount;
    }

    /** A walk that reached no `SecureValue` at all. */
    public function isEmpty(): bool
    {
        return $this->valueCount === 0;
    }

    /**
     * The `secureValueType*` constructor names seen, in order, WITHOUT
     * repeats. Names, never values.
     *
     * @return list<string>
     */
    public function typeNames(): array
    {
        return $this->typeNames;
    }

    /**
     * The §7 branch names the walk actually visited (`data`, `plain_data`,
     * `front_side`, …), in visit order, WITHOUT repeats. This is how a test
     * proves a branch was traversed rather than assumed — the difference
     * between "the walk reaches `front_side`" and "the walk does not crash on
     * `front_side`".
     *
     * @return list<string>
     */
    public function branchesVisited(): array
    {
        return $this->branches;
    }

    /** Nested `SecureData` values reached. Their ciphertext is NOT decrypted. */
    public function secureDataCount(): int
    {
        return $this->secureDataCount;
    }

    /** `SecureFile`s reached across `front_side`/`reverse_side`/`selfie`/
     *  `translation`/`files`. Their bytes are NOT downloaded. */
    public function fileCount(): int
    {
        return $this->fileCount;
    }

    // ── the walk ──────────────────────────────────────────────────────

    private function visit(mixed $value): void
    {
        if (! is_array($value) && ! is_object($value)) {
            return;
        }

        // `secureValue#187fa0ca` has `type:SecureValueType` UNCONDITIONALLY
        // (only `data`/`front_side`/`reverse_side`/`selfie`/`translation`/
        // `files`/`plain_data` are flag-guarded), so a node without a type is
        // not a SecureValue and the walk does not count it.
        $rawType = self::field($value, 'type', 'type');

        if ($rawType === null) {
            return;
        }

        $this->valueCount++;

        $typeName = self::constructorOf($rawType);
        if ($typeName !== null && ! in_array($typeName, $this->typeNames, true)) {
            $this->typeNames[] = $typeName;
        }

        $this->visitSecureData($value);
        $this->visitFiles($value);
        $this->visitPlainData($value, $typeName);
    }

    /** `data:flags.0?SecureData` — reached and counted, never descended into. */
    private function visitSecureData(mixed $value): void
    {
        if (self::field($value, self::BRANCH_DATA, 'data') === null) {
            return;
        }

        $this->branches[] = self::BRANCH_DATA;
        $this->secureDataCount++;
    }

    /** The three single-file branches and the two file vectors of §7. */
    private function visitFiles(mixed $value): void
    {
        foreach (self::FILE_BRANCHES as $branch) {
            $camel = self::camelize($branch);

            if (self::field($value, $branch, $camel) === null) {
                continue;
            }

            $this->branches[] = $branch;
            $this->fileCount++;
        }

        foreach (self::FILE_VECTOR_BRANCHES as $branch) {
            $files = self::field($value, $branch, $branch);

            if (! is_array($files)) {
                continue;
            }

            $this->branches[] = $branch;
            $this->fileCount += count($files);
        }
    }

    /**
     * `plain_data:flags.5?SecurePlainData` — the ONLY identity channel, and
     * only on the two §7 types allowed to carry one.
     *
     * FAIL CLOSED, in both directions, and deliberately:
     *
     *  - the value's TYPE must be positively recognised. `constructorOf()`
     *    is the only way this class learns a node's name, and it returns null
     *    for a node that carries no name at all — a `type` with no `_`, or a
     *    `type` that is a bare string rather than a `SecureValueType` box. The
     *    first revision of this gate read `$typeName === null` as PERMISSION
     *    ("a node that names nothing names nothing we can object to"), and
     *    that is fail-OPEN: an unrecognised type is exactly the payload a
     *    forgery would use, so a name this class cannot read is a refusal,
     *    not a wildcard. The Bot API side has no such hole because
     *    `$element['type']` IS a plain string and "unreadable" and "not a
     *    claim type" are the same test there. Here they are two tests, and
     *    only the second one is a refusal.
     *  - the leaf's constructor must be one of the two §6 leaves. A
     *    constructor-less leaf is still read (both fields, symmetrically — see
     *    below), but a leaf that NAMES a different constructor is not one of
     *    the two §6 leaves and is not read.
     *
     * A constructor-less leaf falls back to its field name, and the fallback
     * is SYMMETRIC: the first revision short-circuited on
     * `$leaf === null || $leaf === 'securePlainEmail'`, so a phone-only leaf
     * that arrived without a constructor was silently dropped while an
     * email-only one was read — a shape-dependent claim, which is the
     * opposite of a rule. Both §6 fields are now read under the same
     * condition, so a constructor-less leaf yields whichever of the two
     * fields it carries. It cannot merge them into one value: the two go to
     * two lists, each with its own agreement rule, exactly as two elements of
     * the Bot API list would.
     */
    private function visitPlainData(mixed $value, ?string $typeName): void
    {
        $plain = self::field($value, self::BRANCH_PLAIN_DATA, 'plainData');

        if ($plain === null) {
            return;
        }

        $this->branches[] = self::BRANCH_PLAIN_DATA;

        // §7: only these two types may carry a plain leaf, and only a type
        // this class can NAME counts. A null/unreadable type name is a
        // refusal, never a wildcard.
        if ($typeName !== self::PLAIN_EMAIL_TYPE && $typeName !== self::PLAIN_PHONE_TYPE) {
            return;
        }

        $leaf = self::constructorOf($plain);

        if ($leaf !== null && $leaf !== self::LEAF_EMAIL && $leaf !== self::LEAF_PHONE) {
            return; // a leaf that names a third constructor is not a §6 leaf.
        }

        $email = self::readable(self::field($plain, 'email', 'email'));
        $phone = self::readable(self::field($plain, 'phone', 'phone'));

        if (($leaf === null || $leaf === self::LEAF_EMAIL) && $email !== null) {
            $this->emails[] = $email;
        }

        if (($leaf === null || $leaf === self::LEAF_PHONE) && $phone !== null) {
            $this->phones[] = $phone;
        }
    }

    // ── shape helpers ─────────────────────────────────────────────────

    /**
     * The list of entries, so a caller may hand a `Vector<SecureValue>`, a
     * single `SecureValue`, or nothing at all.
     *
     * @return list<mixed>
     */
    private static function entries(mixed $values): array
    {
        if (! is_array($values)) {
            return is_object($values) ? [$values] : [];
        }

        return array_is_list($values) ? $values : [$values];
    }

    /**
     * One field of a node, in either spelling: the TL snake_case name for a
     * wire array, the camelCase property name for a generated DTO.
     */
    private static function field(mixed $node, string $snake, string $camel): mixed
    {
        if (is_array($node)) {
            return $node[$snake] ?? $node[$camel] ?? null;
        }

        if (is_object($node)) {
            if (isset($node->{$camel})) {
                return $node->{$camel};
            }

            return $node->{$snake} ?? null;
        }

        return null;
    }

    /**
     * The constructor name of a node: the `_` wire key, or the generated
     * DTO's own class name with the `Data` suffix dropped and the first
     * letter lower-cased (`SecurePlainEmailData` → `securePlainEmail`).
     * Null when the node carries no constructor at all.
     */
    private static function constructorOf(mixed $node): ?string
    {
        if (is_array($node)) {
            $name = $node[self::CONSTRUCTOR_KEY] ?? null;

            return is_string($name) && $name !== '' ? $name : null;
        }

        if (! is_object($node)) {
            return null;
        }

        $short = $node::class;
        $cut = strrpos($short, '\\');
        if ($cut !== false) {
            $short = substr($short, $cut + 1);
        }

        if (str_ends_with($short, self::DATA_SUFFIX)) {
            $short = substr($short, 0, -strlen(self::DATA_SUFFIX));
        }

        if ($short === '') {
            return null;
        }

        return lcfirst($short);
    }

    /** `front_side` → `frontSide`. Zero regex: an underscore hop, nothing else. */
    private static function camelize(string $snake): string
    {
        $cut = strpos($snake, '_');

        if ($cut === false || $cut === 0) {
            return $snake;
        }

        return substr($snake, 0, $cut).ucfirst(substr($snake, $cut + 1));
    }

    /** A non-empty, non-blank string, or null. Never trims what it returns. */
    private static function readable(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }

    /**
     * The single agreed value, or null. Two that DISAGREE name no value at
     * all — same rule as the Bot API element list, same reason: a winner would
     * be chosen by whoever appended the second entry.
     *
     * ══ THE COMPARISON IS NORMALISED, AND WHY THAT IS NOT A LOOSENING ═════
     *
     * DECISION (deliberate, was a byte-exact `!==`): the two emails are
     * compared on a NORMALISED FORM, and the value RETURNED is still the
     * first raw value Telegram sent, byte for byte. The normalisation is:
     *
     *   - the DOMAIN — everything after the LAST `@` — is case-folded
     *     (`strtolower`), because a domain is case-INSENSITIVE by DNS
     *     (RFC 4343), so `Example.TEST` and `example.test` are one domain and
     *     one mailbox, not two addresses;
     *   - exactly ONE trailing dot is stripped from that domain, because
     *     `example.test.` is the same name written as a fully-qualified
     *     (rooted) name — a spelling of the SAME domain, not a different one;
     *   - the LOCAL PART is compared byte-exact and is NEVER case-folded. The
     *     local part is case-SENSITIVE (RFC 5321 §2.4), and folding it would
     *     merge `Ada@example.test` with `ada@example.test` — two distinct
     *     strings the standard does not license calling one mailbox. Those
     *     still disagree, and a payload carrying both is still refused.
     *   - a value with no `@` at all has no domain to fold and is compared
     *     byte-exact, so a malformed value cannot normalise itself into
     *     agreement with a well-formed one.
     *
     * WHY THIS DOES NOT WEAKEN THE CHECK IT GUARDS: the rule this method
     * exists to enforce is that a second `SecureValue` must not be able to
     * CHOOSE the identity a login resolves. Normalising does not give anyone
     * that power — on agreement the first value is returned UNCHANGED, so an
     * appended leaf can never change which mailbox a login resolves to; it
     * can only fail to be noticed, which is inert. On disagreement the claim
     * is still dropped and the login still refused, so the "append a second
     * leaf to make the victim unloginable" attack is still refused. The
     * normalisation therefore removes a DENIAL surface without opening an
     * IMPERSONATION one.
     *
     * The RESIDUAL RISK, stated rather than hidden: the decision assumes the
     * two spellings folded above really are one mailbox. That is a fact about
     * DNS and about RFC 5321, not a guess about a provider — but if a
     * deployment ever needed provider-specific case rules (some mail systems
     * treat the local part as case-insensitive), the correct place to add
     * them is HERE, deliberately, with the same "first value wins, never
     * merged" property — never inside the identity resolver, which must keep
     * comparing what Telegram actually sent.
     *
     * PHONES are NOT normalised at all: `+15551234567` has no spelling
     * variants this class may collapse (`+1 555 123 4567` is a different
     * string and is refused), so a phone stays byte-exact on both sides.
     *
     * @param  list<string>  $values
     */
    private static function agreeing(array $values): ?string
    {
        $first = $values[0] ?? null;

        if ($first === null) {
            return null;
        }

        $comparable = self::comparable($first);

        foreach ($values as $value) {
            if (self::comparable($value) !== $comparable) {
                return null;
            }
        }

        return $first;
    }

    /**
     * The comparison form of one email: local part verbatim, domain
     * case-folded and un-rooted. Zero regex — `strrpos`/`substr`/
     * `str_ends_with`/`strtolower` only, because `src/Teleframe` is a
     * strictly zero-regex tree.
     */
    private static function comparable(string $email): string
    {
        $at = strrpos($email, '@');

        if ($at === false) {
            return $email;
        }

        $domain = substr($email, $at + 1);

        if (str_ends_with($domain, '.')) {
            $domain = substr($domain, 0, -1);
        }

        return substr($email, 0, $at + 1).strtolower($domain);
    }
}
