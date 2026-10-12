<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * EncryptedCredentials — immutable intake DTO for the bot-received passport
 * credentials (design §2/G1, §9 constructor sketch). One shape feeds the
 * shipped `PassportDecryptor::decryptCredentials(data, secret, pem, hash)`:
 * all three fields are base64 strings, carried as-is.
 *
 * Accepted update shapes (G1 contract):
 *  - Bot API `message.passport_data.credentials` {data, hash, secret};
 *  - MTProto `SecureCredentialsEncrypted` array {data, hash, secret}
 *    (optionally under the '_' wire constructor name).
 *
 * AND THE VERIFIED CLAIMS BESIDE THEM. This is the intake half of the
 * re-plumb: a Bot API `PassportData` object carries the Telegram-verified
 * email and phone as PLAINTEXT on the `EncryptedPassportElement` entries in
 * `PassportData.data[]` (an element typed `email` / `phone_number`; see
 * `docs/reference/telegram-passport-official.md` §6), and the previous
 * intake threw them away — it read only `credentials` and the services then
 * went looking for those values INSIDE the encrypted blob, at three
 * coordinates the specification does not define. They are captured here, in
 * the one class whose job is "what did this update actually carry", and handed
 * on as a `PassportVerifiedClaims` so no downstream class has to re-derive
 * where a verified value lives.
 *
 * The MTProto shape has its own §6 channel, and it is the one §6 names for it:
 * the verified email/phone arrive on the `values: Vector<SecureValue>` SIBLING
 * of the `SecureCredentialsEncrypted`, as `plain_data`
 * (`securePlainEmail`/`securePlainPhone`), and `SecureValueTraversal` walks the
 * whole §7 `SecureValue` field list to reach them. So the intake reads them
 * from there.
 *
 * ══ WHAT "THE SAME CLAIM, THE SAME LOGIN" ACTUALLY MEANS ═════════════════
 *
 * A claim is the same value object in both shapes, and the intake above is
 * shape-agnostic about it. The LOGIN, though, has a second edge the intake
 * does not own: the chain needs the SENDER's telegram user id, and the two
 * shapes spell it differently —
 *
 *   - Bot API: `message.from.id`;
 *   - MTProto: `message.from_id`, a `Peer` — api.tl:121/122
 *     `from_id:flags.8?Peer` — and only a `peerUser` names a user at all
 *     (`peerUser#59511722 user_id:long`; `peerChat` is the `sender_chat` this
 *     module refuses, `peerChannel` is a broadcast).
 *
 * That edge used to read `message.from.id` ONLY, so the previous revision of
 * this docblock's "resolves exactly as it does on a Bot API one" was not
 * true: a genuine `updateNewMessage` frame carries no such field, every
 * MTProto-shaped login was refused at the sender, and the suite was green only
 * because its fixture had Bot-API sender coordinates bolted onto an MTProto
 * frame. `SenderUserId` now reads both spellings, and the action is read from
 * `message.action` as well, so a PURE `updateNewMessage` is a first-class
 * input: given that frame, an MTProto-shaped response resolves a field-mode
 * login the same way a Bot API one does — and a shape that yields no claim is
 * still refused loudly. What is NOT claimed is that the intake reads every
 * spelling a host might invent: only the four action coordinates below, and
 * only an action that NAMES itself `messageActionSecureValuesSentMe`.
 *
 * The coordinate is the SPEC's: §1 says the MTProto result arrives as
 * `updateNewMessage` → `messageService` → `messageActionSecureValuesSentMe`
 * (api.tl:167, `values:Vector<SecureValue> credentials:SecureCredentialsEncrypted`),
 * and the pinned fixture is a real `MessageActionSecureValuesSentMeData`. Only
 * that action yields claims. A `values` list sitting BESIDE the credentials at
 * the root of the update is a coordinate Telegram never writes, so it is still
 * not read — the same polarity the `PassportData` test holds.
 *
 * A shape that yields no claim still yields `PassportVerifiedClaims::none()`,
 * and a field-mode login on it is refused loudly rather than resolved from a
 * value nobody vouched for.
 *
 * `fromUpdate()` is quiet on malformed input (missing/empty required field
 * → null): the webhook intake must not throw on non-passport updates, and
 * an update whose well-formed fields EXCEED the per-field size caps is
 * refused LOUD (unbounded attacker input is never buffered).
 * `fromArray()` is the loud round-trip side of `toArray()`.
 */
final class EncryptedCredentials
{
    /** @var non-empty-string */
    public readonly string $data;

    /** @var non-empty-string */
    public readonly string $hash;

    /** @var non-empty-string */
    public readonly string $secret;

    /** §6 — the verified email/phone the update carried in plaintext. */
    public readonly PassportVerifiedClaims $claims;

    /**
     * api.tl:167 — `messageActionSecureValuesSentMe#1b287353 values:Vector<SecureValue>
     * credentials:SecureCredentialsEncrypted = MessageAction`. The one action
     * whose `values` carry the §6 `plain_data` claims.
     */
    private const ACTION_SENT_ME = 'messageActionSecureValuesSentMe';

    /**
     * Per-field intake size caps on the base64 string lengths. Telegram's
     * real envelope is far smaller (data ~KB, RSA-2048 secret 344 chars,
     * SHA-256 hash 44 chars), so these only ever trip on abusive input.
     */
    private const DATA_MAX_BYTES = 65536;

    private const SECRET_MAX_BYTES = 8192;

    private const HASH_MAX_BYTES = 64;

    private const SIZE_LIMIT_ERROR = 'Passport credentials exceed the per-field size limits (data 65536 / secret 8192 / hash 64 bytes max).';

    /**
     * @param  non-empty-string  $data  base64-encrypted credentials payload
     * @param  non-empty-string  $hash  base64 SHA-256 integrity hash
     * @param  non-empty-string  $secret  base64 secret (encrypted with bot RSA key)
     */
    private function __construct(
        string $data,
        string $hash,
        string $secret,
        PassportVerifiedClaims $claims,
    ) {
        $this->data = $data;
        $this->hash = $hash;
        $this->secret = $secret;
        $this->claims = $claims;
    }

    /**
     * Whether this update carries passport credentials AT ALL — the three
     * documented coordinates: the Bot API `message.passport_data.credentials`,
     * the MTProto `messageActionSecureValuesSentMe` action, and a
     * `SecureCredentialsEncrypted` handed over on its own.
     *
     * The predicate is INCOMPLETE-BY-CONSTRUCTION unless every arm is
     * complete, and both directions of that were wrong in the first revision:
     * an arm that accepted a constructor-less action was FAILING OPEN (key
     * presence standing in for identity — the module's own "a coordinate that
     * looks right and is not" defect, inside the predicate that guards the
     * 5cbbde5b refusal), and an arm that required a `values` key was FAILING
     * CLOSED on the WRONG THING (a credential-bearing update with no `values`
     * channel was reported as carrying nothing, so `handleUpdate()` returned
     * null and the unbound-session refusal never fired). So: an action counts
     * only by its CONSTRUCTOR, and its `values` channel is optional — a
     * missing `values` means "no claim", not "not credentials". A credential
     * update that reaches the caller therefore ALWAYS meets the refusal below
     * it, whatever shape it arrived in.
     *
     * QUIET and un-throwing (it is a routing question, not an intake: no size
     * caps are applied and nothing is validated), so a bus or router can ask
     * it before deciding this update is its business. `PassportLoginService::
     * handleUpdate()` asks exactly this, and the answer decides whether an
     * update with NO bound session is refused loudly or quietly ignored —
     * which is a security difference, not a convenience one, so the coordinate
     * list lives here and nowhere else.
     *
     * @param  array<string, mixed>  $update
     */
    public static function updateCarriesCredentials(array $update): bool
    {
        if (is_array($update['message']['passport_data']['credentials'] ?? null)) {
            return true;
        }

        if (self::sentValuesAction($update) !== null) {
            return true;
        }

        return self::arrayHasCredentialsKeys($update);
    }

    /**
     * Extract credentials from a webhook update — Bot API
     * `message.passport_data.credentials` OR an MTProto
     * `SecureCredentialsEncrypted` array. Null when the update carries no
     * well-formed credentials (G1 contract: missing/empty → null).
     *
     * @param  array<string, mixed>  $update
     */
    public static function fromUpdate(array $update): ?self
    {
        // Bot API. The claims are read from the SAME `PassportData` object the
        // credentials came out of (§6), never from inside the blob.
        $passportData = $update['message']['passport_data'] ?? null;
        if (is_array($passportData)) {
            $credentials = $passportData['credentials'] ?? null;
            if (is_array($credentials)) {
                return self::fromIntake($credentials, PassportVerifiedClaims::fromPassportData($passportData));
            }
        }

        // MTProto `messageActionSecureValuesSentMe` — the §1 coordinate. Its
        // `values` sibling is the §6 claim channel, and `credentials` is the
        // same envelope the two branches above carry. An action with no
        // `values` key at all still carries credentials and still arrives
        // here: its claims are `none()` (no channel, no claim), which the
        // claim-based login refuses loudly.
        $action = self::sentValuesAction($update);
        if ($action !== null) {
            return self::fromIntake(
                $action['credentials'],
                PassportVerifiedClaims::fromSecureValues($action['values'] ?? null),
            );
        }

        // MTProto `SecureCredentialsEncrypted` handed over on its own — a
        // host-adapted form with no action around it, so it carries no
        // `values` sibling and therefore no claim.
        if (self::arrayHasCredentialsKeys($update)) {
            return self::fromIntake($update, PassportVerifiedClaims::none());
        }

        return null;
    }

    /**
     * The `messageActionSecureValuesSentMe` payload `{values, credentials}` if
     * this update carries one, else null.
     *
     * Three ways in, and all of them are stated rather than guessed:
     *  - `message.action` — where a real `updateNewMessage` puts it, because
     *    `message` is a `Message` VARIANT and the `action:MessageAction` field
     *    belongs to the `messageService#7a800e0a` member of that variant
     *    (api.tl:122). This is the one coordinate a pure MTProto update
     *    actually has;
     *  - `messageService.action` / `message_service.action` — the same object
     *    unwrapped to the root by a host, in this package's generated
     *    `MessageServiceData::$action` spelling and in the raw TL one;
     *  - the action handed over directly as `$update['action']`, which is what
     *    a host that unwrapped the update before intake passes.
     *
     * An action is accepted ONLY when it IS this constructor. The first
     * revision also accepted an action that carried NO constructor and had
     * both documented fields, on the reasoning that a host-adapted shape
     * might have dropped the name — and that is the module's own recurring
     * defect class ("a coordinate that looks right and is not") sitting
     * INSIDE the predicate that guards the 5cbbde5b refusal: key presence was
     * standing in for identity. `peerUser` proves the general point: a bag of
     * keys that looks like a value can be a channel, a chat or a bot. A
     * constructor is what makes a TL object the object it claims to be, and
     * `MessageActionSecureValuesSentMeData` is generated, so any host that
     * really has one has the name available. No constructor → no action.
     *
     * `values` is NOT required, and its absence is the §6 rule rather than a
     * malformed shape: `values` carries the verified claims and a payload with
     * no `values` channel simply has none, so `fromUpdate()` hands on
     * `PassportVerifiedClaims::none()` and the claim-based login is refused
     * loudly. Requiring the key, as the first revision did, meant an update
     * that DOES carry credentials was not recognised as carrying them at all:
     * `updateCarriesCredentials()` said false, `handleUpdate()` returned null,
     * and the unbound-session refusal never fired. A credential-bearing update
     * must always REACH that refusal.
     *
     * @param  array<string, mixed>  $update
     * @return array{values?: mixed, credentials: array<string, mixed>}|null
     */
    private static function sentValuesAction(array $update): ?array
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

            if (! isset($candidate['credentials']) || ! is_array($candidate['credentials'])) {
                continue;
            }

            // Re-shaped rather than returned raw: the caller is handed the two
            // documented fields and nothing else, so a `values` key Telegram
            // did not write is `null` (no channel, no claim) rather than a
            // hole in the type.
            return [
                'values' => $candidate['values'] ?? null,
                'credentials' => $candidate['credentials'],
            ];
        }

        return null;
    }

    /**
     * Loud intake: throws PassportLoginException on malformed credentials.
     *
     * @param  array<string, mixed>  $credentials  {data, hash, secret}
     */
    public static function fromArray(array $credentials): self
    {
        $data = $credentials['data'] ?? null;
        $hash = $credentials['hash'] ?? null;
        $secret = $credentials['secret'] ?? null;

        if (! is_string($data) || $data === ''
            || ! is_string($hash) || $hash === ''
            || ! is_string($secret) || $secret === '') {
            throw new PassportLoginException(
                'Malformed passport credentials: data/hash/secret must be non-empty strings.'
            );
        }

        self::assertWithinSizeLimits($data, $hash, $secret);

        return new self($data, $hash, $secret, PassportVerifiedClaims::none());
    }

    /**
     * The three encrypted fields ONLY. The verified claims are deliberately
     * absent: they are plaintext, they are personal data, and a round-trip
     * that reintroduced them would be a second place they are copied around.
     *
     * @return array{data: string, hash: string, secret: string}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'hash' => $this->hash,
            'secret' => $this->secret,
        ];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private static function arrayHasCredentialsKeys(array $credentials): bool
    {
        return array_key_exists('data', $credentials)
            && array_key_exists('hash', $credentials)
            && array_key_exists('secret', $credentials);
    }

    /**
     * The shared intake used by `fromUpdate()`: quiet (null) on
     * missing/empty/non-string fields — the G1 non-passport contract — and
     * LOUD PassportLoginException when well-formed fields exceed the
     * per-field size caps (oversized input is refused at intake, never
     * buffered downstream).
     *
     * @param  array<string, mixed>  $credentials
     */
    private static function fromIntake(array $credentials, PassportVerifiedClaims $claims): ?self
    {
        $data = $credentials['data'] ?? null;
        $hash = $credentials['hash'] ?? null;
        $secret = $credentials['secret'] ?? null;

        if (! is_string($data) || $data === ''
            || ! is_string($hash) || $hash === ''
            || ! is_string($secret) || $secret === '') {
            return null;
        }

        self::assertWithinSizeLimits($data, $hash, $secret);

        return new self($data, $hash, $secret, $claims);
    }

    /**
     * @param  non-empty-string  $data
     * @param  non-empty-string  $hash
     * @param  non-empty-string  $secret
     */
    private static function assertWithinSizeLimits(string $data, string $hash, string $secret): void
    {
        if (strlen($data) > self::DATA_MAX_BYTES
            || strlen($hash) > self::HASH_MAX_BYTES
            || strlen($secret) > self::SECRET_MAX_BYTES) {
            throw new PassportLoginException(self::SIZE_LIMIT_ERROR);
        }
    }
}
