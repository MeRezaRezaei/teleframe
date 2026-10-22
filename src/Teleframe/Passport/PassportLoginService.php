<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;
use Psr\Log\LoggerInterface;

/**
 * PassportLoginService — THE orchestrator (design §4 G4): webhook update →
 * decrypt (shipped PassportDecryptor, untouched) → validate → nonce verify
 * → identity resolve → bind (reused Identity row) → host callback. Every
 * seam is constructor-injected; zero container/facade/config() calls inside
 * — plain-PHP constructible (the standalone-smoke gate).
 *
 * POSTURE (design §1/§4/§11): inert when unconfigured — enabled=false makes
 * login() throw loud on first real use, never silent-drop. Every failure is
 * PSR-3 logged at error level (method names + ms only, NEVER payloads, keys
 * or passwords) and ALWAYS rethrown — logging never swallows, the host owns
 * retry/policy (AGENTS.md).
 *
 * The onLogin hook is the auth ride (Cycle-8b): this class NEVER calls
 * Auth::login or writes sessions; it invokes the injected closure (default:
 * the provider's host-auth ride) so "the package never stops users" stays
 * literally true. Plain-PHP hosts pass their own hook or null.
 */
final class PassportLoginService
{
    /**
     * @param  PassportConfig  $config  the teleframe.passport values (inert by default)
     * @param  PassportKeyLoader  $keys  PEM path → key — the ONLY key materialization point
     * @param  NonceRegistry  $nonces  one-time issue/consume replay guard (Memory | PSR-16)
     * @param  IdentityResolver  $identity  decrypted payload + consumed userId → Laravel user
     * @param  BindingWriter  $writer  the ONE Eloquent touchpoint (default EloquentBindingWriter)
     * @param  PasswordConfirmer|null  $confirmer  optional SRP 2FA confirm (G5, default-off)
     * @param  LoggerInterface|null  $logger  PSR-3, silent when unset (NullLogger posture)
     * @param  \Closure(Model): void|null  $onLogin  host hook — the auth ride (never Auth::login here)
     * @param  PassportDocumentResolver|null  $documents  the document seam; when
     *                                                    null one is built per
     *                                                    run from $keys + $wire,
     *                                                    so the document table
     *                                                    is NEVER skipped
     * @param  PassportWireContract|null  $wire  download seam for §4 files
     */
    public function __construct(
        private readonly PassportConfig $config,
        private readonly PassportKeyLoader $keys,
        private readonly NonceRegistry $nonces,
        private readonly IdentityResolver $identity,
        private readonly BindingWriter $writer,
        private readonly ?PasswordConfirmer $confirmer = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?\Closure $onLogin = null,
        private readonly ?PassportDocumentResolver $documents = null,
        private readonly ?PassportWireContract $wire = null,
    ) {}

    /**
     * Run the full §4 chain on a webhook update. The update array and any
     * derived key material live only inside this call — never in logs,
     * config or state.
     *
     * @param  array<string, mixed>  $update  the webhook payload (Bot API
     *                                        message.passport_data.credentials
     *                                        or MTProto SecureCredentialsEncrypted)
     * @param  int  $sessionUserId  the id of the session PRESENTING this
     *                              update. REQUIRED and non-nullable on
     *                              purpose: it is the one half of the binding
     *                              check that makes a spent nonce worthless to
     *                              anyone else, and a nullable parameter whose
     *                              null silently skips that check is exactly
     *                              the account-takeover hole this signature
     *                              exists to close (see chain() step (g)).
     *                              A host with no proven session must NOT call
     *                              this method at all.
     *
     * @throws PassportLoginException on every refusal in the chain —
     *                                not enabled, malformed credentials,
     *                                decryption/hash failure, refused type,
     *                                unknown/expired nonce (replay), unbound
     *                                session, unresolved identity, missing 2FA
     *                                password, missing sender id, or a
     *                                failing binding.
     */
    public function login(array $update, int $sessionUserId): LoginResult
    {
        $start = hrtime(true);

        try {
            return $this->chain($update, $sessionUserId);
        } catch (PassportLoginException $e) {
            $this->logFailure('login', $e, $start);
            throw $e; // already a passport-domain failure — never double-wrap
        } catch (\Throwable $e) {
            $this->logFailure('login', $e, $start);
            throw new PassportLoginException($e->getMessage(), $e);
        }
    }

    /**
     * Webhook-intake convenience: silent null when the update carries NO
     * passport credentials at all (neither message.passport_data.credentials
     * nor root data/hash/secret) — the bus/router path for non-passport
     * updates. An update that DOES carry credentials is routed to login()
     * and stays loud (malformed payloads are never silently dropped; G1).
     *
     * The second parameter is nullable ONLY so the bus/router convenience
     * above stays usable: an update with no credentials in it returns null
     * without ever asking who is presenting it. An update that DOES carry
     * credentials and arrives with no bound session is REFUSED LOUD — it is
     * never handed to login(), which cannot be reached unbound at all.
     *
     * @param  array<string, mixed>  $update
     * @param  int|null  $sessionUserId  the session presenting the update, as
     *                                   in login(); null is allowed here and
     *                                   refused there.
     *
     * @throws PassportLoginException
     */
    public function handleUpdate(array $update, ?int $sessionUserId = null): ?LoginResult
    {
        // The three documented coordinates (Bot API `passport_data.credentials`,
        // the MTProto `messageActionSecureValuesSentMe` action, a bare
        // `SecureCredentialsEncrypted`), decided by the intake so the list
        // cannot exist twice and drift. An update that does NOT carry
        // credentials returns null; one that DOES and has no bound session is
        // REFUSED below — including on the MTProto shape, so a new claim
        // channel cannot become a new way to skip this check.
        if (! EncryptedCredentials::updateCarriesCredentials($update)) {
            return null;
        }

        if ($sessionUserId === null) {
            throw new PassportLoginException(
                'passport login refused: the update carries credentials but no session is bound to it, '
                .'so the login cannot prove the presenter is the session the nonce was issued to. '
                .'Call handleUpdate($update, $sessionUserId) with the authenticated user id.'
            );
        }

        return $this->login($update, $sessionUserId);
    }

    /**
     * Issue a fresh one-time nonce bound to $userId (the host starts the
     * login from an identified web session; the nonce travels to Telegram
     * inside the passport request, OUT of the package). The decrypted
     * response must return this exact nonce or consume() refuses.
     */
    public function issueNonce(int $userId): string
    {
        return $this->nonces->issue($userId);
    }

    /** The composed service itself — the facade/CLI identity probe. */
    public function service(): self
    {
        return $this;
    }

    /**
     * The §4 chain, step by step. Every step is loud on refusal; nothing is
     * silent except handleUpdate()'s no-credentials gate.
     *
     * @param  array<string, mixed>  $update
     * @param  int  $sessionUserId  the session presenting the update — always
     *                              an int here, never null (login() is
     *                              non-nullable and handleUpdate() refuses a
     *                              null before it gets this far).
     *
     * @throws PassportLoginException
     */
    private function chain(array $update, int $sessionUserId): LoginResult
    {
        // (a) opt-in gate — inert by default, loud on first real use.
        if (! $this->config->enabled()) {
            throw new PassportLoginException('passport login not enabled (configure teleframe.passport)');
        }

        // (b) intake — Bot API message.passport_data.credentials OR an
        //     MTProto SecureCredentialsEncrypted array; null → loud.
        $credentials = EncryptedCredentials::fromUpdate($update);
        if ($credentials === null) {
            throw new PassportLoginException(
                'update carries no passport credentials (message.passport_data.credentials or root data/hash/secret)'
            );
        }

        // (c0) TRANSPORT INTEGRITY, BEFORE ANY DECRYPTION. The envelope's
        //      `hash` must equal SHA256 of the bytes it decrypts to, compared
        //      as 32 RAW bytes with `hash_equals` — never as a base64 string,
        //      never as 64 hexits. See `PassportCredentialsVerifier` for why
        //      that distinction is the whole check.
        //
        //      WHY HERE, AND WHY BOTH CHAINS. It sits between the intake and
        //      the decryptor because that is the only point the envelope is
        //      whole and unparsed: a tampered blob is refused before AES runs
        //      at all, and the refusal is raised by a class named for what it
        //      checks rather than inherited implicitly from a property of the
        //      call below. It is NOT the only line of defence — the shipped
        //      `PassportDecryptor` performs the same `hash_equals` internally
        //      and throws — so this is an explicit, independent consumer-level
        //      assertion, not a new security boundary. `PassportAnonymousLoginService`
        //      does the same at its own step (d), because leaving one of the two
        //      login paths unchecked would be a hole, not a hardening.
        PassportCredentialsVerifier::assertCredentialsEnvelope($credentials, $this->keys->key());

        // (c) decrypt — the SHIPPED PassportDecryptor, consumed unchanged.
        //     RSA-OAEP secret → SHA512(secret+hash) key/iv → AES-256-CBC →
        //     hash_equals. Nonce presence is ENFORCED at construction.
        $decrypted = PassportDecryptor::decryptCredentials(
            $credentials->data,
            $credentials->secret,
            $this->keys->key(),
            $credentials->hash,
        );
        // The verified email/phone come from the INTAKE — the §6 plaintext on
        // the Bot API `PassportData` object — and are passed IN. They are
        // never re-derived from `$decrypted`, whose `Credentials` JSON is
        // `{secure_data, nonce}` (§5) and therefore carries no identity at
        // all. The previous build read `secure_data.email.address`,
        // `secure_data.phone.phone_number` and the blob's top-level
        // `email`/`phone` — three coordinates Telegram never writes, which is
        // why a real response resolved as "no identity".
        $payload = new PassportPayload($decrypted, $credentials->claims);

        // (d) allowed-types + required-field rules (zero-regex allow-list).
        (new PassportValueValidator)->validate($payload, $this->config->allowedTypes());

        // (d1) THE DOCUMENT TABLE — built UNCONDITIONALLY, for every login,
        //      before the nonce is consumed and long before anything is bound.
        //
        //      WHY HERE AND WHY ALWAYS. Every `allowed_types` value must produce
        //      a document or a NAMED refusal, and the only way that can be true
        //      rather than merely intended is for the table to exist on every
        //      run. Opting in would reproduce the defect (the default ships),
        //      throwing would turn "the user supplied four of eleven documents"
        //      into a failed login, and logging alone would make it
        //      unassertable. So it is a value, computed here and carried on the
        //      LoginResult — where the host can read it and where
        //      PassportErrorReporter can name the failures to Telegram.
        //
        //      It is computed BEFORE the nonce consume on purpose: it touches
        //      no session state, so paying for it costs a login nothing when a
        //      later step refuses.
        $coverage = $this->documentCoverage($payload, $update);

        // (e) ONE-TIME consume — the replay guard (design §11): a replayed
        //     webhook (queue redelivery) hits an unknown nonce here and is
        //     refused loud — never a second login.
        $consumedUserId = $this->nonces->consume($payload->nonce());
        if ($consumedUserId === null) {
            throw new PassportLoginException(
                'unknown or expired passport nonce: the response cannot be replayed'
            );
        }

        // (f) verification gate — the `require_verified` toggle MEANS
        //     something here, not only at provider-registration time. A
        //     claim-based identity is refused BEFORE the user table is
        //     touched when the host demands proof and the payload does not
        //     carry it, so an unverified claim never reaches a query.
        //     Session mode (the default) is structurally exempt: it binds to
        //     an already-authenticated session via the consumed nonce, not to
        //     any claim in the payload, so the default flow is untouched.
        $this->assertVerificationSatisfied($payload);

        // (g) identity — the consumed nonce's userId ties the response to
        //     the web session that started the login; a non-Model resolution
        //     is refused loud, never a silent drop.
        //
        //     BIND THE SESSION, DO NOT JUST RECORD IT. The nonce alone is not
        //     proof of who is presenting it: it travels through Telegram and
        //     can appear in a URL, a referrer log or a proxy log on the way.
        //     Without this check any authenticated user who learns another
        //     user's nonce can spend it, and the host-auth ride then logs
        //     THAT user into THEIR browser — full account takeover. Identity
        //     comes from the nonce, so the session that presents it has to
        //     be the session the nonce was issued to.
        //
        //     UNCONDITIONAL. There is no "both sides known" escape hatch: the
        //     previous version of this check was
        //     `if ($sessionUserId !== null && $sessionUserId !== ...)` behind
        //     a `?int $sessionUserId = null` parameter, and the public entry
        //     point defaulted it to null — so the ONE call a host could copy
        //     from the facade docblock (`Passport::login($update)`) skipped
        //     the check entirely and was the attack, not a bypass of it.
        //     login() is now non-nullable and handleUpdate() refuses a null
        //     before reaching here, so an unbound login cannot be expressed.
        if ($sessionUserId !== $consumedUserId) {
            throw new PassportLoginException(
                'passport nonce was issued to a different session than the one presenting it'
            );
        }

        $user = $this->identity->resolve($payload, $consumedUserId);
        if (! $user instanceof Model) {
            throw new PassportLoginException(
                'passport login unresolved: no Laravel user for the consumed session (configure the identity seam)'
            );
        }

        // (h) optional SRP 2FA re-confirm (G5, default-off): the update's
        //     `password` field + the injected confirmer seam; a missing
        //     password or a missing seam is loud, never a skip.
        if ($this->config->requirePasswordConfirmation()) {
            $password = (string) ($update['password'] ?? '');
            if ($password === '') {
                throw new PassportLoginException(
                    'passport login requires the 2FA password (teleframe.passport.require_password_confirmation is enabled)'
                );
            }

            $confirmer = $this->confirmer;
            if ($confirmer === null) {
                throw new PassportLoginException(
                    'passport login requires a '.PasswordConfirmer::class
                    .' seam when require_password_confirmation is enabled — bind teleclient.passport.confirmer'
                );
            }

            $confirmer->confirm($password);
        }

        // (i) tg identity — the sender telegram user id, Bot API
        //     `message.from.id` OR the MTProto `message.from_id`
        //     `peerUser` (passport is user-only; sender_chat / peerChat is
        //     refused). Digit strings are accepted, 0 refused.
        $tgId = $this->resolveSenderTgId($update);
        if ($tgId <= 0) {
            throw new PassportLoginException(
                'passport login requires the sender telegram user id (message.from.id, or the MTProto message.from_id peerUser) — sender_chat is refused; passport is user-only'
            );
        }

        $accountId = $this->config->primaryAccountId();
        $binding = $this->writer->bind($tgId, $accountId, $user);

        // (j) provenance — "which of my users authenticated via Telegram, and
        //     with what proof?" Until this existed a passport login left the
        //     users row byte-identical to a password login. TYPES only, never
        //     values: a verified email written to a column would copy personal
        //     data into a table nobody was expecting it in.
        $this->recordProvenance($tgId, $accountId, $this->fieldsVerified($payload));

        // (k) host hook — the auth ride (Cycle-8b): the package never calls
        //     Auth::login; the injected hook owns the session. Null = the
        //     host does its own thing (plain-PHP hosts).
        $onLogin = $this->onLogin;
        if ($onLogin !== null) {
            $onLogin($user);
        }

        // (l) ONE login outcome.
        return new LoginResult(
            tgUserId: $tgId,
            accountId: $accountId,
            user: $user,
            binding: $binding,
            fieldsVerified: $this->fieldsVerified($payload),
            documents: $coverage,
        );
    }

    /**
     * THE DOCUMENT TABLE, built from the three seams this service owns: the
     * decrypted payload (the §4 credentials index), the raw update (the
     * ciphertext and the file references), and the host's `allowed_types`.
     *
     * The key is materialised HERE, per run, by `PassportKeyLoader` — never at
     * construction — and `withPrivateKey()` only ever adds it to a resolver
     * that does not have one, so an injected resolver with its own key keeps
     * it. A missing wire seam is NOT a reason to skip the table: it is the
     * reason every file slot carries
     * {@see PassportDocumentRefusal::WIRE_UNAVAILABLE}, which is the whole
     * point.
     */
    private function documentCoverage(PassportPayload $payload, array $update): PassportDocumentCoverage
    {
        $resolver = $this->documents
            ?? new PassportDocumentResolver(null, $this->wire);

        return PassportDocumentCoverage::build(
            $this->config->allowedTypes(),
            $payload,
            PassportDocumentSource::fromUpdate($update, $payload->secureData()),
            $resolver->withPrivateKey($this->keys->key()),
        );
    }

    /**
     * The `require_verified` gate, checked on the LOGIN path.
     *
     * THE EXACT REFUSAL CONDITION — both must hold:
     *   1. `teleframe.passport.require_verified` is true (the host demands
     *      proof; the shipped default is false, so this is inert by default);
     *   2. the identity seam is a CLAIM-MATCHING one, i.e. a
     *      `FieldIdentityResolver`, and the response carries NO claim from a
     *      location Telegram vouched for — i.e. no `email` / `phone_number`
     *      element in `PassportData.data` (§6).
     *
     * What changed on 2026-09-26, and why the rule MOVED rather than
     * weakened. The gate used to refuse "an email/phone that Telegram did not
     * list in `secure_data`" — which, per §5/§6, was every real claim, because
     * the verified values do not live in `secure_data` at all: they are the
     * Bot API `PassportData` plaintext. Honouring a host's demand for proof
     * under that reading meant refusing every real Telegram response, i.e. the
     * flag was a lockout, not a check.
     *
     * So the rule is restated against the specification: a claim is VERIFIED
     * when Telegram put it there (the §6 plaintext), and UNVERIFIED when it
     * appears only at a coordinate the specification does not define (a
     * top-level `email`/`phone` inside the blob, or a `secure_data.email` /
     * `secure_data.phone` entry). Under the re-plumb a claim cannot come from
     * the second family at all — `PassportPayload` does not read it and
     * `PassportValueValidator` refuses the `secure_data` form outright — so
     * the honest remaining refusal is a payload with NO verified claim at all
     * reaching a claim-matching seam. It is still a real refusal, it is still
     * raised BEFORE the users table is touched, and the message names the
     * offending TYPES only (`PassportPayload::strayClaimTypes()`), never a
     * value.
     *
     * Why (2) is not paranoia: session mode, the default, derives the
     * identity from the consumed nonce's userId — an already authenticated web
     * session — and never looks at a claim. Refusing session mode for lacking
     * a claim would break a flow that never depended on one.
     *
     * Note the deliberate asymmetry with the provider-time guard, which is
     * left untouched: that one refuses to BUILD a field resolver without
     * require_verified, while this one enforces what the flag says at login
     * time. A host that sets require_verified=true and wants a second factor
     * of its own on top still reads Telegram's own verification, and now
     * honestly gets it.
     *
     * @throws PassportLoginException when the host demands proof and the
     *                                identity has no Telegram-verified claim
     */
    private function assertVerificationSatisfied(PassportPayload $payload): void
    {
        if (! $this->config->requireVerified()) {
            return;
        }

        if (! $this->identity instanceof FieldIdentityResolver) {
            return; // session mode (or a session-shaped custom seam): no claim involved
        }

        // The CLAIM, specifically. A document in `secure_data` is verified
        // evidence about a person, but on a claim-matching seam the identity
        // rests on the email/phone, so the flag is about THOSE: Telegram put
        // them there (§6) or it did not verify anything this login needs.
        if ($payload->claims()->types() !== []) {
            return; // Telegram sent a verified claim — the host's demand is met
        }

        // TYPE NAMES ONLY. The values at a non-spec location are never read
        // by anything in this module, so they cannot leak through here.
        $stray = $payload->strayClaimTypes();

        throw new PassportLoginException(
            'passport login refused: teleframe.passport.require_verified is enabled and the '
            .'response carries no Telegram-verified field. The verified email and phone arrive as plaintext '
            .'on the EncryptedPassportElement entries in PassportData.data (an element typed email / phone_number), '
            .'beside each element own encrypted data — never inside secure_data; '
            .($stray === []
                ? 'nothing was present at all'
                : 'the only identity present is at a location Telegram does not send ('.implode(', ', $stray).')')
            .'. Verify the claim out of band or keep teleframe.passport.user_lookup=session.'
        );
    }

    /**
     * Record the login's authentication provenance on the binding, TYPES ONLY.
     *
     * Delegates to the writer when it implements `PassportProvenanceRecorder`
     * (the shipped Eloquent + memory writers do). A host that injected its own
     * `BindingWriter` predating this seam simply does not record — checked
     * with `instanceof`, never an interface-method call, so adding provenance
     * is NOT a breaking change for an existing host implementation and a
     * missing recorder is never fatal.
     *
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
     * The sender's stable telegram bigint — Bot API `message.from.id` or the
     * MTProto `message.from_id` `peerUser`. Single-sourced in `SenderUserId`,
     * which the anonymous chain calls too, so the two chains cannot drift
     * into reading different coordinates.
     *
     * @param  array<string, mixed>  $update
     */
    private function resolveSenderTgId(array $update): int
    {
        return SenderUserId::resolve($update);
    }

    /**
     * The verified field TYPES as a plain list — the §5 secure_data document
     * types Telegram sent plus the §6 claim types it verified. NAMES only:
     * this is what `LoginResult::fieldsVerified` and the provenance recorder
     * carry, and a verified email is evidence of a verified email whether it
     * arrived as a document or as the plaintext `PassportData` field.
     *
     * @return list<string>
     */
    private function fieldsVerified(PassportPayload $payload): array
    {
        return $payload->verifiedFieldTypes();
    }

    /**
     * PSR-3 error with method names + ms ONLY — never payloads, keys,
     * passwords or nonces (AGENTS.md credentials rule, design §11).
     */
    private function logFailure(string $method, \Throwable $e, int $startNanos): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->error(
            'PassportLoginService::'.$method.' failed after '
            .((int) round((hrtime(true) - $startNanos) / 1e6)).' ms',
            ['exception' => $e::class, 'method' => $method],
        );
    }
}
