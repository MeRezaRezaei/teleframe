<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;
use Psr\Log\LoggerInterface;

/**
 * PassportAnonymousLoginService — the orchestrator for LOGGING A STRANGER IN.
 *
 * Everything else in this module ATTACHES a Telegram identity to a user who is
 * already authenticated. This class is the one that creates a session out of
 * nothing but a verified Telegram field, which is the capability the owner
 * actually asked for ("let laravel users login using it when one configures
 * them"). That gap is what this class exists to close: the shipped routes were
 * behind the host's `auth` guard, a callback that refused 401 without a
 * session, and not one way for an anonymous visitor to become a user.
 *
 * It is a SEPARATE class, on purpose. The temptation was to add a mode to
 * `PassportLoginService`, and that is the vulnerability: that class binds a
 * login by comparing the session presenting the callback against the userId
 * the consumed nonce was issued for, and an anonymous visitor has no userId,
 * so "anonymous mode" there necessarily means a comparison that can be absent.
 * The authenticated path stays exactly as it is, with its unconditional check
 * intact, and this class carries its own binding — one it cannot be called
 * without.
 *
 * ══ THE BINDING, AND WHY IT IS NOT OPTIONAL ══════════════════════════════
 *
 * `login()` takes a NON-NULLABLE `string $sessionFingerprint` and a
 * non-nullable `string $flowToken`. There is no default, no `?`, and no
 * "both sides known" escape hatch. That is deliberate and it is the whole
 * reason this file exists: the first account takeover in this module was a
 * `?int $sessionUserId = null` whose null skipped the comparison, and the
 * second was a check that could be skippable at the call site. The shape
 * here cannot be skipped — a caller that has no session has no string to
 * pass, and a controller that cannot produce one refuses 401 BEFORE reaching
 * this class.
 *
 * The chain then enforces, in order:
 *
 *   (a) the capability is switched on at all (config, default OFF), AND a
 *       REAL BOT is configured behind it — `bot_id != 0` and a non-empty
 *       `bot_username`. Passport is a BOT API feature: the bot's private key
 *       is what decrypts the envelope and the bot's token is what the element
 *       hash is taken over, so a host with a key and NO bot is running a login
 *       endpoint that cannot mean anything. Refused rather than served;
 *   (b) the marker is consumed ONE-TIME — unknown, spent or expired all
 *       return null and the login is refused, so a replay cannot reach
 *       anything downstream and a bad attempt cannot leave a live token
 *       behind for the legitimate browser to reuse;
 *   (c) the browser presenting the callback is the browser the marker was
 *       issued to — `SessionFingerprint::matches()`. This is the direct
 *       analogue of the authenticated path's `if ($sessionUserId !==
 *       $consumedUserId) throw`, and it is the check that makes a leaked
 *       flow token useless: the token travels in a URL and a referrer, and
 *       knowing it (plus the credentials) still only logs in the browser the
 *       marker names;
 *   (d) the credentials were produced FOR THIS TRIP — the decrypted payload's
 *       nonce digests to the digest the marker recorded. Credentials from
 *       another flow, or from a flow whose marker is spent, are refused even
 *       from the right browser;
 *   (e) the payload names exactly one identity field, and Telegram listed it
 *       in `secure_data` (see the verification section below);
 *   (f) the caller is not already authenticated — the attach flow owns that
 *       case, and letting an authenticated session be REPLACED by a claim is
 *       the shape of "log in as somebody else";
 *   (g) the field matches a row. No match is a refusal, never a new account.
 *
 * ══ IDENTITY: WHERE IT COMES FROM, AND WHY A CLAIM MAY SELECT HERE ═══════
 *
 * The owner's requirement is that a Laravel user can log in. There is no
 * session to read an id out of, so identity MUST come from the verified
 * Telegram field — there is nowhere else. `FieldIdentityResolver` states the
 * governing rule: A CLAIM MAY CONFIRM THE BOUND USER; IT MAY NEVER SELECT
 * ONE. That rule is not weakened here; it is bounded, and the bounds are:
 *
 *   1. THE FIELD MUST BE VETTED BY TELEGRAM. Only `secure_data` entries are
 *      ever read. A payload whose `email` exists solely as a top-level plain
 *      scalar is a bare claim — Telegram never vouched for it — and is
 *      refused. `PassportPayload::plainValue()` already refuses to widen
 *      itself to those scalars (an earlier attempt to do so was reverted
 *      upstream as a security regression), and this class does not reach for
 *      them either.
 *   2. VERIFICATION IS UNCONDITIONAL ON THIS PATH. Not "when the host set
 *      require_verified" — always. `require_verified` exists because the
 *      authenticated path can sit on a session and never touch a claim; this
 *      path rests entirely on the field, so a vetted field is the price of
 *      admission rather than an opt-in. The flag is still read and still
 *      honoured (`requireVerified()` short-circuits to a stricter refusal
 *      when the host has also demanded proof), so a host that turns it on
 *      gets a strictly safer path, never a weaker one. Nothing here can turn
 *      it off.
 *   3. EXACT MATCH, EXACTLY ONE FIELD. One vetted email OR one vetted phone,
 *      compared for equality against one host column. Both present is refused
 *      rather than preferring one (a payload that disagrees with itself is
 *      evidence of a problem, not of a user), and a vetted value that
 *      DISAGREES with the payload's own top-level scalar is refused too.
 *   4. NEVER A FALLBACK, NEVER A SCAN. One column, one equality, one row.
 *
 * `teleframe.passport.user_lookup` is deliberately IGNORED on this path. It
 * selects the resolver for the authenticated chain, whose two modes differ in
 * ways that only mean something when a session is bound to the comparison.
 *
 * ══ AN UNMATCHED FIELD CREATES NOTHING ═══════════════════════════════════
 *
 * A verified Telegram field that matches no user is REFUSED, loudly, and no
 * row is written. Auto-provisioning is a real product decision — it decides
 * whether anyone with a Telegram account can appear in the host's user table,
 * whether an unverified-looking-but-Telegram-vetted address becomes a login,
 * and what a host's password-reset and email-verification flows then have to
 * cope with — and it is NOT this package's to make on the host's behalf. A
 * host that wants it implements its own listener around the resolved field;
 * the seam for that is the `onLogin` hook plus the `PassportRefusal`
 * taxonomy, and this class stays out of the users table either way. Pin it in
 * the config (`unknown_user => 'refuse'`, the only shipped value) so the
 * decision is visible in configuration rather than buried in code.
 *
 * POSTURE, as everywhere in this module: inert unless
 * `teleframe.passport.anonymous_login` is true (the shipped default is
 * false) AND a real bot is configured behind it (`bot_id` non-zero and
 * `bot_username` non-empty); every refusal is loud; failures are PSR-3 logged at
 * error level with method names and timings ONLY and are ALWAYS rethrown, and
 * the package never calls Auth itself — it invokes the injected ride, exactly
 * as the authenticated path does.
 *
 * Plain-PHP constructible (the standalone-smoke gate): every seam is
 * constructor-injected, no container/facade/config call in the constructor or
 * in the chain. Zero regex.
 */
final class PassportAnonymousLoginService
{
    /**
     * @param  PassportConfig  $config  the teleframe.passport values (inert by default)
     * @param  PassportKeyLoader  $keys  PEM path → key — the ONLY key materialization point
     * @param  PendingLoginRegistry  $pending  one-time pending-login markers (the session binding)
     * @param  BindingWriter  $writer  the ONE Eloquent touchpoint for the tg identity
     * @param  string  $userClass  the host's User model class (guarded; '' disables the default lookup)
     * @param  (callable(string $column, string $value): ?Model)|null  $finder  injected lookup
     *                                                                          seam, so this class never learns about a container
     * @param  LoggerInterface|null  $logger  PSR-3, silent when unset (NullLogger posture)
     *
     * The compared columns are NOT constructor seams: they live on
     * `PassportConfig` (`emailColumn()` / `phoneColumn()`), which is the single
     * place `teleframe.passport.email_column` is read. An earlier draft also
     * took them here, and the two could then disagree with no error anywhere —
     * a constructor argument that is written and never read is worse than no
     * argument at all, because it looks load-bearing.
     * @param  \Closure(Model): void|null  $onLogin  host hook — the auth ride (never Auth::login here)
     */
    public function __construct(
        private readonly PassportConfig $config,
        private readonly PassportKeyLoader $keys,
        private readonly PendingLoginRegistry $pending,
        private readonly BindingWriter $writer,
        private readonly string $userClass = '',
        private $finder = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?\Closure $onLogin = null,
    ) {}

    /**
     * Start an anonymous login: mint the pending marker and the one-time
     * passport nonce bound to it, for the session identified by
     * $sessionFingerprint.
     *
     * Refuses when the capability is off, so an unconfigured host can never
     * mint a marker or a nonce on this path. `$sessionFingerprint` is a
     * non-nullable string on purpose: there is no "no session" overload, so a
     * caller cannot start a login it cannot later finish against a browser.
     *
     * @throws PassportLoginException when the capability is off
     */
    public function start(string $sessionFingerprint): PendingLoginTicket
    {
        if (! $this->config->enabled()) {
            throw new PassportLoginException('passport login not enabled (configure teleframe.passport)');
        }

        if (! $this->config->anonymousLoginEnabled()) {
            throw new PassportLoginException(
                'anonymous passport login is disabled (set teleframe.passport.anonymous_login to turn it on — '
                .'read that config key before you do: it lets an unauthenticated visitor become a logged-in user)'
            );
        }

        // A BLANK fingerprint is refused HERE, at the boundary, rather than
        // relying on the caller to have gone through SessionFingerprint::of()
        // (which throws on a blank id) or on the controller to have checked.
        //
        // That matters because a blank is the one value that would defeat the
        // binding while looking exactly like a bound marker: every caller that
        // failed to obtain a session would produce the same one, so two
        // different un-sessioned browsers would match each other and the
        // comparison would pass. "The check can be skipped by passing a blank"
        // is the same family of bug as "the check can be skipped by passing
        // null", which is how this module was breached twice.
        if (trim($sessionFingerprint) === '') {
            throw new PassportLoginException(
                'an anonymous passport login must be bound to a real browser session: a blank fingerprint is refused '
                .'rather than stored, because every sessionless caller would produce the same one'
            );
        }

        return $this->pending->issue($sessionFingerprint);
    }

    /**
     * Run the full chain for an anonymous visitor and log them in.
     *
     * @param  array<string, mixed>  $update  the credential envelope, exactly as the
     *                                        authenticated path takes it (Bot API
     *                                        message.passport_data.credentials or root
     *                                        data/hash/secret)
     * @param  string  $flowToken  the pending marker's handle, presented by this request
     * @param  string  $sessionFingerprint  SessionFingerprint::of() of the session
     *                                      PRESENTING this request
     * @param  int  $authenticatedUserId  the already-authenticated user's id on this
     *                                    request, or 0 for none — non-nullable so the
     *                                    "must not replace a live session" refusal cannot
     *                                    be skipped by omitting the argument
     *
     * @throws PassportLoginException on every refusal — capability off, no bot
     *                                configured behind it, unknown/spent/
     *                                expired marker, marker issued to a different
     *                                browser, credentials minted for a different flow,
     *                                a type the host does not allow, no vetted identity
     *                                field, a field matching no user, a missing sender
     *                                telegram id, or a failing binding.
     */
    public function login(array $update, string $flowToken, string $sessionFingerprint, int $authenticatedUserId = 0): LoginResult
    {
        $start = hrtime(true);

        try {
            return $this->chain($update, $flowToken, $sessionFingerprint, $authenticatedUserId);
        } catch (PassportLoginException $e) {
            $this->logFailure('login', $e, $start);
            throw $e; // already a passport-domain failure — never double-wrap
        } catch (\Throwable $e) {
            $this->logFailure('login', $e, $start);
            throw new PassportLoginException('anonymous passport login failed unexpectedly', $e);
        }
    }

    /** The composed service itself — the facade/CLI identity probe. */
    public function service(): self
    {
        return $this;
    }

    /**
     * @param  array<string, mixed>  $update
     *
     * @throws PassportLoginException
     */
    private function chain(array $update, string $flowToken, string $sessionFingerprint, int $authenticatedUserId): LoginResult
    {
        // (a) capability gate — off by default, loud on first real use.
        if (! $this->config->enabled() || ! $this->config->anonymousLoginEnabled()) {
            throw new PassportLoginException(
                'anonymous passport login is disabled (set teleframe.passport.anonymous_login)'
            );
        }

        // (a2) THE BOT PRESENCE GATE. Enabling the capability is not enough:
        // Passport is a BOT API feature, so the bot's PRIVATE key is what
        // decrypts the data envelope and the bot's TOKEN is what the
        // per-element hash is taken over. A host holding a Passport RSA key
        // but NO bot therefore runs a live anonymous login endpoint whose
        // entire premise is absent — "useless but running", which is the worst
        // of both: it answers requests, it costs the host the attack surface,
        // and the presence of a working login form is what invites traffic.
        //
        // Both halves are required and both are already-read config, so this
        // makes an IMPLICIT precondition EXPLICIT rather than adding a switch:
        // `bot_id` is the identity `requestPassport` asks with, and a 0 is
        // Telegram's own "unconfigured" sentinel (the shipped default);
        // `bot_username` is what makes `telegram_url` a real destination
        // rather than the `null` the response degrades to when it is blank.
        // With either missing the endpoint cannot complete a single real
        // handshake, so it is refused here — AT the gate, before the pending
        // marker is consumed, so a refused host never even spends a token.
        //
        // Zero-regex: `===` on the int, `trim()` on the string. Names only —
        // never a bot id value, a username value or any key material.
        $missing = [];

        if ($this->config->botId() === 0) {
            $missing[] = 'teleframe.passport.bot_id';
        }

        if (trim($this->config->botUsername()) === '') {
            $missing[] = 'teleframe.passport.bot_username';
        }

        if ($missing !== []) {
            throw new PassportLoginException(
                'anonymous passport login requires a configured bot, and '.implode(' and ', $missing)
                .(count($missing) === 1 ? ' is unset' : ' are unset')
                .': Telegram Passport is a BOT API feature, so the bot private key'
                .' (teleframe.passport.private_key_path) is what decrypts the data'
                .' envelope and the bot token is what the per-element hash is taken'
                .' over — with no bot behind them this login endpoint is running but'
                .' cannot complete a handshake, so it is refused rather than served'
            );
        }

        // (b) ONE-TIME marker consume. Single-use AND expiring, and the
        //     delete happens before the answer is known — so a wrong browser
        //     cannot spend a token and then leave it alive for the right one.
        $binding = $this->pending->consume($flowToken);
        if ($binding === null) {
            throw new PassportLoginException(
                'unknown, already spent or expired passport login flow: the response cannot be replayed'
            );
        }

        // (c) THE SESSION BINDING — the direct analogue of the authenticated
        //     path's unconditional `$sessionUserId !== $consumedUserId` throw,
        //     and the reason the flow token is not a bearer credential. A
        //     flow token travels in a URL and lands in referrer logs; without
        //     this line, whoever holds it could finish somebody else's login
        //     in their own browser. Both arguments are server-derived digests,
        //     never anything the request body can choose.
        if (! SessionFingerprint::matches($binding->sessionFingerprint(), $sessionFingerprint)) {
            throw new PassportLoginException(
                'passport login flow was issued to a different browser session than the one presenting it'
            );
        }

        // (d) the credentials must belong to THIS flow — the decrypted
        //     payload's nonce against the digest recorded at start.
        $credentials = EncryptedCredentials::fromUpdate($update);
        if ($credentials === null) {
            throw new PassportLoginException(
                'update carries no passport credentials (message.passport_data.credentials or root data/hash/secret)'
            );
        }

        // (d0) TRANSPORT INTEGRITY, BEFORE ANY DECRYPTION. The envelope's
        //      `hash` must equal SHA256 of the bytes it decrypts to, compared
        //      as 32 RAW bytes with `hash_equals` — never as a base64 string,
        //      never as 64 hexits. See `PassportCredentialsVerifier` for the
        //      raw-vs-encoded trap this closes and for what it is NOT: it
        //      authenticates the BLOB, never the identity claim.
        //
        //      Same point as `PassportLoginService`'s step (c0) and for the
        //      same reason: the envelope is whole and unparsed exactly here, so
        //      a tampered blob is refused before AES runs. It is an explicit
        //      second assertion, not the only one — the shipped
        //      `PassportDecryptor` raises the same comparison internally.
        //      The delivery below is untouched; the check simply gates it.
        PassportCredentialsVerifier::assertCredentialsEnvelope($credentials, $this->keys->key());

        $decrypted = PassportDecryptor::decryptCredentials(
            $credentials->data,
            $credentials->secret,
            $this->keys->key(),
            $credentials->hash,
        );
        // The verified email/phone are the §6 plaintext on the matching
        // `EncryptedPassportElement` in `PassportData.data`, carried in by the
        // intake — never re-derived from the decrypted blob, whose
        // `Credentials` JSON is `{secure_data, nonce}` (§5) and holds no
        // identity.
        $payload = new PassportPayload($decrypted, $credentials->claims);

        if (! SessionFingerprint::matches($binding->nonceDigest(), hash('sha256', $payload->nonce()))) {
            throw new PassportLoginException(
                'passport credentials were not produced for this login flow'
            );
        }

        // (e) allowed-types + required-field rules (zero-regex allow-list).
        (new PassportValueValidator)->validate($payload, $this->config->allowedTypes());

        // (f) no live session may be REPLACED by a claim. The attach flow
        //     owns the authenticated case; an anonymous login that swapped
        //     one account for another would be a takeover wearing a login
        //     form.
        if ($authenticatedUserId > 0) {
            throw new PassportLoginException(
                'this browser is already authenticated: use the authenticated passport flow to attach a Telegram '
                .'identity, not the anonymous one to replace the session'
            );
        }

        // (g) the identity field — vetted, singular, exact. See the class
        //     docblock: this is the one place a claim selects a user, and
        //     the bounds are Telegram's own vetting, an unconditional
        //     verification requirement, and one exact column comparison.
        [$column, $value] = $this->verifiedIdentityField($payload);

        $user = $this->find($column, $value);
        if (! $user instanceof Model) {
            // REFUSE, never provision. See the class docblock: whether an
            // unmatched verified field may create an account is the host's
            // product decision, not this package's. The configured policy is
            // READ here, so the key is live rather than decorative, and any
            // value other than the shipped `refuse` is reported as a
            // configuration error rather than silently ignored — a host who
            // set `unknown_user=create` must never believe it is quietly
            // provisioning when it is in fact refusing.
            $policy = $this->config->unknownUserPolicy();

            throw new PassportLoginException(
                $policy === 'refuse'
                    ? 'no Laravel user matches the verified Telegram field: this package does not create accounts, '
                        .'so provision the user yourself and log in again (teleframe.passport.unknown_user=refuse)'
                    : 'no Laravel user matches the verified Telegram field, and teleframe.passport.unknown_user='
                        .$policy.' is not a policy this package implements: refusing, because the only shipped policy '
                        .'is `refuse` and no value of this key may create an account'
            );
        }

        // (h) sender telegram id — Bot API `message.from.id` OR the MTProto
        //     `message.from_id` `peerUser` (passport is user-only;
        //     sender_chat / peerChat is refused). Digit strings accepted, 0
        //     refused.
        $tgId = $this->resolveSenderTgId($update);
        if ($tgId <= 0) {
            throw new PassportLoginException(
                'passport login requires the sender telegram user id (message.from.id, or the MTProto message.from_id peerUser) — sender_chat is refused; passport is user-only'
            );
        }

        // (i) the tg identity binding — the SAME writer the authenticated
        //     path uses, so there is one Eloquent touchpoint in the module.
        $accountId = $this->config->primaryAccountId();
        $bindingRow = $this->writer->bind($tgId, $accountId, $user);

        // (j) provenance — TYPES only, never values, for the same reason as
        //     the authenticated path: a verified email written to a column
        //     would copy personal data into a table nobody expects it in.
        $this->recordProvenance($tgId, $accountId, $this->fieldsVerified($payload));

        // (k) the auth ride — the package never calls Auth::login itself.
        $onLogin = $this->onLogin;
        if ($onLogin !== null) {
            $onLogin($user);
        }

        return new LoginResult(
            tgUserId: $tgId,
            accountId: $accountId,
            user: $user,
            binding: $bindingRow,
            fieldsVerified: $this->fieldsVerified($payload),
        );
    }

    /**
     * The ONE identity field this path will act on, as a [column, value] pair
     * ready for a single equality lookup.
     *
     * The value comes from §6 and nowhere else: the Bot API `PassportData`
     * the `EncryptedPassportElement` entries in `PassportData.data[]`
     * (typed `email` / `phone_number`), which Telegram has already verified. It is NOT `secure_data.email` / `secure_data.phone` (not §5
     * types, so a payload carrying one is refused by the validator before
     * this runs) and NOT the decrypted blob's own top-level scalars (the
     * `Credentials` JSON is `{secure_data, nonce}`).
     *
     * Refusals, all loud:
     *  - both an email and a phone → self-inconsistent, refused rather than
     *    preferring one. A payload that disagrees with itself is evidence of a
     *    problem, not of a user.
     *  - neither → there is nothing to log in with. When the only identity
     *    present sits at a non-spec location the message says so, by TYPE
     *    NAME only, never the value.
     *
     * @return array{0: string, 1: string}
     *
     * @throws PassportLoginException
     */
    private function verifiedIdentityField(PassportPayload $payload): array
    {
        $email = $payload->plainEmail();
        $phone = $payload->plainPhone();

        if ($email !== null && $phone !== null) {
            throw new PassportLoginException(
                'refused: the payload carries both a verified email and a verified phone, so it does not name one identity'
            );
        }

        if ($email !== null) {
            return [$this->config->emailColumn(), $email];
        }

        if ($phone !== null) {
            return [$this->config->phoneColumn(), $phone];
        }

        // No verified field. Say precisely which unverified claims were
        // present — TYPE NAMES ONLY, never the values, which are personal data
        // and must not reach a log, a response body or a referrer.
        $stray = $payload->strayClaimTypes();

        throw new PassportLoginException(
            $stray === []
                ? 'refused: the payload carries no verified email or phone, so there is no identity to log in with. '
                    .'Telegram sends the verified values as plaintext on the EncryptedPassportElement entries in '
                    .'PassportData.data (an element typed email / phone_number), beside each element own encrypted data'
                : 'refused: an anonymous passport login requires the Telegram-verified email or phone from the '
                    .'PassportData.data elements, but the only identity present is at a location Telegram does '
                    .'not send ('.implode(', ', $stray).') — inside the encrypted credentials'
                    .($this->config->requireVerified()
                        ? ' — teleframe.passport.require_verified is enabled and is honoured here too'
                        : '')
        );
    }

    /**
     * One equality lookup against the host's user table, via the injected
     * seam when present and via a guarded Eloquent static otherwise.
     * Exactly one column, exactly one value, at most one row.
     */
    private function find(string $column, string $value): ?Model
    {
        if ($this->finder !== null) {
            $user = ($this->finder)($column, $value);

            return $user instanceof Model ? $user : null;
        }

        if ($this->userClass === '' || ! class_exists($this->userClass) || ! is_subclass_of($this->userClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $userClass */
        $userClass = $this->userClass;

        $user = $userClass::query()->where($column, $value)->first();

        return $user instanceof Model ? $user : null;
    }

    /**
     * @param  list<string>  $fieldsVerified  SecureValueType NAMES, never values
     */
    private function recordProvenance(int $tgId, ?int $accountId, array $fieldsVerified): void
    {
        $writer = $this->writer;

        if ($writer instanceof PassportProvenanceRecorder) {
            $writer->recordProvenance($tgId, $accountId, $fieldsVerified);
        }
    }

    /**
     * The sender's stable telegram id — Bot API `message.from.id` or the
     * MTProto `message.from_id` `peerUser`. Single-sourced in `SenderUserId`,
     * which the authenticated chain calls too, so the two chains cannot drift
     * into reading different coordinates.
     *
     * @param  array<string, mixed>  $update
     */
    private function resolveSenderTgId(array $update): int
    {
        return SenderUserId::resolve($update);
    }

    /**
     * The verified field TYPES — the §5 document types plus the §6 claim types
     * Telegram verified. NAMES only, never values, for the same reason as the
     * authenticated path.
     *
     * @return list<string>
     */
    private function fieldsVerified(PassportPayload $payload): array
    {
        return $payload->verifiedFieldTypes();
    }

    /**
     * PSR-3 error with method names + ms ONLY — never a payload, a field value,
     * a flow token, a nonce, a key or a session fingerprint (AGENTS.md
     * credentials rule).
     */
    private function logFailure(string $method, \Throwable $e, int $startNanos): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->error(
            'PassportAnonymousLoginService::'.$method.' failed after '
            .((int) round((hrtime(true) - $startNanos) / 1e6)).' ms',
            ['exception' => $e::class, 'method' => $method],
        );
    }
}
