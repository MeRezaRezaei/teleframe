<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\View\View as ContractView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;

/**
 * The Telegram Passport LOGIN surface — the HTTP half the engine never had.
 *
 * `PassportLoginService::issueNonce()` documents that "the nonce travels to
 * Telegram inside the passport request, OUT of the package" — so before this
 * controller, a host had to hand-write the entire loop. Two endpoints close
 * it, both inside the dashboard route group (therefore behind the host's
 * `web` + `auth` middleware: a passport login can never be started without a
 * Laravel session, and the session is what the nonce is bound to):
 *
 *   start    — issues a one-time nonce bound to the AUTHENTICATED user's id
 *              and hands back the Telegram-side handshake: the exact
 *              `requestPassport` argument list (bot_id / scope / nonce /
 *              public_key), a ready-to-open t.me deep link, and this
 *              controller's own callback URL. A GET renders the same thing
 *              as a page (the package's Blade shell style); a POST answers
 *              JSON for a host's own login button.
 *   callback — receives the credentials Telegram returned
 *              (data/hash/secret + the sender's telegram id) and feeds them
 *              to the UNCHANGED `PassportLoginService::login()` chain:
 *              decrypt → validate → ONE-TIME nonce consume → identity →
 *              bind → the provider's host-auth ride. This controller never
 *              decrypts, never re-verifies and never trusts a field: it
 *              builds the update envelope and steps out of the way.
 *
 * POSTURE (unchanged, deliberately): inert unless
 * `teleframe.passport.enabled` — both endpoints refuse before a nonce
 * exists, and the service itself still throws loud on a direct call. Every
 * refusal is surfaced (422/403/409), never swallowed. The nonce, the
 * encrypted payload and every decrypted field are NEVER logged; the only
 * derived value used outside the engine is a SHA-256 digest of the payload,
 * as the atomic-consume lock key.
 *
 * CONSUME ATOMICITY: the engine's CacheNonceRegistry is documented as a
 * get-then-delete with a residual TOCTOU window (PSR-16 exposes no atomic
 * get-and-delete). This path does not make that worse: the callback runs the
 * whole chain under a cache LOCK keyed on a SHA-256 digest of the payload,
 * so two concurrent callbacks carrying the same credentials are serialized —
 * the second one finds the nonce already gone and is refused by the engine's
 * own replay guard. A store with no lock support falls back to the
 * documented (unlocked) engine behaviour rather than refusing every login.
 *
 * Plain-PHP constructible: no constructor arguments, no container calls in
 * the constructor (the standalone-smoke gate constructs it bare).
 *
 * ══ THE ANONYMOUS LOGIN HALF, AND WHY IT IS A SEPARATE PAIR OF ROUTES ═════
 *
 * Everything above is ATTACH: it names the user the session already holds and
 * writes a Telegram identity onto them. It cannot authenticate anyone — every
 * route above sits inside the host's `auth` guard, and `callback()` refuses
 * 401 without a session. That is the right design for what it does, and it is
 * defended; it is also not a login system, and "let laravel users login using
 * it when one configures them" is a different feature.
 *
 * `loginStart()` / `loginCallback()` below are that feature, on routes
 * registered OUTSIDE the `auth` group (only `web` + a throttle), behind
 * `teleframe.passport.anonymous_login` which defaults to FALSE. The
 * controller here is deliberately the SAME class rather than a new one, so
 * there is one place where "what does this endpoint refuse, and with what
 * status" is written down — but the code paths share nothing that matters:
 * a different orchestrator, a different store, a different binding.
 *
 * THE BINDING IS NOT OPTIONAL, and the way that is enforced is the part worth
 * reading. The authenticated path binds a login by comparing the session
 * PRESENTING the callback with the userId its nonce was issued for — two
 * integers meaning "a Laravel user". An anonymous visitor has none, which is
 * exactly why a naive "anonymous mode" is a takeover: the comparison is
 * missing by construction. So this path does not skip a check, it has a
 * DIFFERENT one that cannot be skipped:
 *
 *   * `loginStart()` requires a real session (`hasSession()`) and refuses
 *     401 without one, BEFORE minting anything;
 *   * it records a SHA-256 FINGERPRINT of that session and a digest of the
 *     trip's passport nonce into a single-use, expiring pending marker;
 *   * `loginCallback()` recomputes the fingerprint from the session
 *     presenting it and hands it to `PassportAnonymousLoginService::login()`
 *     as a NON-NULLABLE string, which refuses a mismatch outright.
 *
 * There is no nullable parameter and no default, so no call site — including
 * one written later by someone copying a docblock — can leave the comparison
 * out. That is the specific shape of both takeovers this module has already
 * survived, and the reason the check here is a type-level property rather than
 * a branch.
 *
 * WHAT IS NEVER WRITTEN ANYWHERE: the session id, the flow token, the nonce,
 * the credential envelope and every decrypted field. The flow token appears in
 * the `start` RESPONSE (it has to — the browser carries it) and in the
 * callback URL, which is precisely why the session binding is mandatory
 * rather than a nicety.
 */
final class PassportController extends Controller
{
    /** How long the consume lock is held, and how long a caller waits for it. */
    private const LOCK_SECONDS = 10;

    private const LOCK_WAIT_SECONDS = 5;

    /**
     * The most `EncryptedPassportElement` entries this controller will forward
     * from a request body. §5 lists 11 document types and §6 adds `email` and
     * `phone_number`, so 13 is every element Telegram can legitimately send;
     * 32 leaves headroom and still bounds the intake's walk of a hostile POST.
     */
    private const MAX_ELEMENTS = 32;

    /**
     * The human page: status, the handoff Telegram needs, and a paste-form
     * for credentials a host received by other means. Same view namespace
     * and styling conventions as the dashboard shell.
     */
    public function show(Request $request): ContractView
    {
        $user = $request->user();

        return view('teleframe::passport', [
            'enabled' => $this->config()->enabled(),
            'startUrl' => route('teleframe.passport.start'),
            'callbackUrl' => route('teleframe.passport.callback'),
            'userLabel' => $user === null ? '' : (string) ($user->getAttribute('email') ?? $user->getKey()),
            'botUsername' => $this->config()->botUsername(),
        ]);
    }

    /**
     * Issue the one-time nonce for the authenticated user and return the
     * Telegram-side handshake. Refuses BEFORE issuing anything when Passport
     * is off, so an unconfigured host can never mint a nonce.
     */
    public function start(Request $request, PassportLoginService $passport): JsonResponse
    {
        $config = $this->config();

        if (! $config->enabled()) {
            return $this->refused(
                'Passport login is disabled (set teleframe.passport.enabled).',
                403,
            );
        }

        $user = $request->user();
        if ($user === null) {
            return $this->refused('Unauthenticated.', 401);
        }

        $userId = (int) $user->getKey();
        if ($userId <= 0) {
            return $this->refused('The authenticated user has no usable id.', 422);
        }

        // The one-time nonce, bound to this session's user id. The engine
        // refuses the response unless it returns this exact nonce.
        $nonce = $passport->issueNonce($userId);

        // The exact `requestPassport` argument list. `scope` is the SPEC
        // object `{"v":1,"data":[…]}` (PassportConfig::requestScope()), not
        // the flat configured list — Telegram requires the wrapper, and
        // `email`/`phone` are not scope types at all. The flat list is still
        // returned alongside it, for the host's own display.
        $request_ = [
            'bot_id' => $config->botId(),
            'scope' => $config->requestScope(),
            'nonce' => $nonce,
            'public_key' => $config->publicKey(),
        ];

        $botUsername = $config->botUsername();

        return response()->json([
            'ok' => true,
            'nonce' => $nonce,
            'expires_in' => $config->nonceTtl(),
            'bot_id' => $config->botId(),
            'bot_username' => $botUsername,
            'scope' => $config->scope(),
            // The exact argument list for the bot's `requestPassport` call —
            // the nonce travels to Telegram inside it, which is the whole
            // point of issuing one.
            'request' => $request_,
            // Ready-to-open handoff: opens the chat with the bot and hands
            // it the nonce as a /start parameter, so the host does not have
            // to build a link. Null when no bot username is configured.
            'telegram_url' => $botUsername === ''
                ? null
                : 'https://t.me/'.$botUsername.'?start='.$nonce,
            // The bot's reply, when the host answers a /start token with its
            // own `requestPassport` call.
            'bot_command' => '/passport '.$nonce,
            'callback_url' => route('teleframe.passport.callback'),
        ]);
    }

    /**
     * Receive the credentials Telegram returned and run the shipped chain.
     *
     * Accepts either the Bot API envelope (`data`/`hash`/`secret` + `from_id`
     * for the sender's telegram user id) or the same three fields sent
     * under `credentials` — the shapes a host's bot handler or the Telegram
     * Passport website widget produces. A request with no authenticated
     * session is refused 401 before the chain runs, because the session is
     * the only thing the consumed nonce can be bound to.
     */
    public function callback(Request $request, PassportLoginService $passport): JsonResponse|RedirectResponse
    {
        // WHO IS PRESENTING — before anything else, and before the chain can
        // touch the nonce. Without a bound session this endpoint has nothing
        // to compare the consumed nonce against, so it refuses instead of
        // calling login() with a value it does not have. (login() is
        // non-nullable precisely so this cannot be forgotten downstream; see
        // PassportLoginService::chain() step (g).)
        $sessionUserId = $this->sessionUserId($request);
        if ($sessionUserId === null) {
            return $this->refused(
                'Unauthenticated: a passport login must be presented by the session its nonce was issued to.',
                401,
            );
        }

        $data = $this->credentials($request);
        if ($data === null) {
            return $this->refused(
                'Malformed passport credentials: data/hash/secret must be non-empty strings.',
                422,
            );
        }

        $fromId = $this->senderTgId($request->input('from_id', $request->input('from', [])));
        if ($fromId === null) {
            return $this->refused(
                'The sender telegram user id is required (passport is user-only; sender_chat is refused).',
                422,
            );
        }

        // The minimal Bot API envelope the engine's own intake understands.
        // Nothing here is inspected, re-verified or trusted — the service
        // owns every field decision (design §4).
        //
        // `passport_data` is rebuilt as a REAL `PassportData` object, and that
        // includes the ELEMENT LIST: the verified email and phone are
        // plaintext fields of an `EncryptedPassportElement` in `PassportData.data`
        // (§6), not members of the `PassportData` object itself. It used to be
        // rebuilt with `credentials` alone, which discarded them — and since
        // those are the only coordinates the specification puts the verified
        // values at, a callback that arrived from Telegram with a real email
        // could not log in through this controller at all. The elements are
        // forwarded verbatim; no value is read, logged, echoed or put in a URL
        // by this class.
        $update = [
            'message' => [
                'from' => ['id' => $fromId],
                'passport_data' => $this->passportData($request, $data),
            ],
        ];

        $password = $request->input('password');
        if (is_string($password) && $password !== '') {
            $update['password'] = $password;
        }

        $run = fn (): LoginResult => $passport->login($update, $sessionUserId);

        try {
            // Serialize concurrent consumes of the SAME credentials so the
            // engine's replay guard sees the second one (see class docblock).
            $lock = Cache::lock(
                'teleframe.passport.callback:'.hash('sha256', $data['data']),
                self::LOCK_SECONDS,
            );
            $result = $lock->block(self::LOCK_WAIT_SECONDS, $run);
        } catch (LockTimeoutException) {
            // The lock is genuinely busy. Nothing has run yet, so falling
            // through to the engine is safe: the one-time nonce is still
            // enforced there, and a lock timeout is reported as such.
            $result = $this->runUnlocked($run);
        } catch (PassportLoginException $refused) {
            // A real refusal from the chain, rethrown by Lock::block(). Return
            // it as a VALUE exactly as runUnlocked() does, and do NOT retry:
            // the one-time nonce was already consumed, so a second pass would
            // fail at the nonce and report a replay for what was really a 2FA,
            // identity or database error.
            $result = $refused;
        } catch (\Throwable $lockUnsupported) {
            // A store with no lock support throws here BEFORE running $run.
            // Anything else is a genuine unexpected failure and must not be
            // silently converted into a second attempt at authentication.
            if (! $this->isLockUnsupported($lockUnsupported)) {
                throw $lockUnsupported;
            }

            $result = $this->runUnlocked($run);
        }

        if ($result instanceof PassportLoginException) {
            return $this->refused($result->getMessage(), 422);
        }

        if (! $result instanceof LoginResult) {
            return $this->refused('Passport login produced no result.', 500);
        }

        $redirect = $this->config()->successRedirect();

        if ($redirect !== '') {
            return redirect()->to($redirect);
        }

        return response()->json([
            'ok' => true,
            'tg_user_id' => $result->tgUserId,
            'account_id' => $result->accountId,
            'fields_verified' => $result->fieldsVerified,
        ]);
    }

    // ── the ANONYMOUS login half (outside the host's auth guard) ─────

    /**
     * Start an anonymous login: mint the pending marker + the one-time
     * passport nonce bound to it, and hand back the Telegram-side handshake.
     *
     * REFUSES when the capability is off (403), so a host that has not
     * deliberately turned it on can never mint a marker on this path. The
     * capability is `teleframe.passport.anonymous_login` and it is a SEPARATE
     * switch from `teleframe.passport.enabled`: enabling Passport to attach
     * Telegram identities to accounts must not also hand sessions to
     * strangers.
     *
     * The session is required, not optional. A guest arriving with no Laravel
     * session has nothing to bind the marker to, and a marker issued to
     * nothing is exactly the shape that turns a leaked flow token into a
     * login — so this refuses 401 before issuing anything. The route carries
     * `web`, which is what gives a guest a session to bind to; the check is
     * here too, so a host that mounts these routes on its own group without
     * `web` gets a refusal rather than an unbound flow.
     *
     * The `flow` token in the response is a handle the BROWSER carries and
     * presents at the callback (as a `?flow=` query parameter on the callback
     * URL, which is how the Telegram website widget's POST reaches us). It is
     * not a credential: on its own it authenticates nobody.
     */
    public function loginStart(Request $request, PassportAnonymousLoginService $passport): JsonResponse
    {
        $config = $this->config();

        if (! $config->enabled()) {
            return $this->refused('Passport login is disabled (set teleframe.passport.enabled).', 403);
        }

        if (! $config->anonymousLoginEnabled()) {
            return $this->refused(
                'Anonymous passport login is disabled (set teleframe.passport.anonymous_login to turn it on).',
                403,
            );
        }

        $sessionFingerprint = $this->sessionFingerprint($request);
        if ($sessionFingerprint === null) {
            return $this->refused(
                'Unauthenticated: an anonymous passport login must be started from a browser session to bind to.',
                401,
            );
        }

        // A browser that is ALREADY authenticated is refused here too, not
        // only at the callback. Minting a marker it could never finish would
        // be harmless in itself — the callback refuses the same browser — but
        // a host that put this button on an authenticated page would be
        // shipping a login form that can only ever fail, and "already
        // authenticated" is the answer it needs. The attach flow owns that
        // case, properly, behind the `auth` guard.
        if (($this->sessionUserId($request) ?? 0) > 0) {
            return $this->refused(
                'This browser is already authenticated: use the authenticated passport flow to attach a Telegram identity.',
                403,
            );
        }

        $ticket = $passport->start($sessionFingerprint);

        return response()->json([
            'ok' => true,
            // The handle the callback must present. Single-use, expiring, and
            // worthless from any browser but the one that fetched it.
            'flow' => $ticket->token(),
            'nonce' => $ticket->nonce(),
            'expires_in' => $config->nonceTtl(),
            'bot_id' => $config->botId(),
            'bot_username' => $config->botUsername(),
            'scope' => $config->scope(),
            'request' => [
                'bot_id' => $config->botId(),
                // The spec-shaped scope object, for the same reasons as the
                // authenticated path above.
                'scope' => $config->requestScope(),
                'nonce' => $ticket->nonce(),
                'public_key' => $config->publicKey(),
            ],
            'telegram_url' => $config->botUsername() === ''
                ? null
                : 'https://t.me/'.$config->botUsername().'?start='.$ticket->nonce(),
            // The flow travels in the callback URL so a widget POST carries
            // it. That is also why the session binding is mandatory: a URL is
            // a place a token leaks.
            'callback_url' => route('teleframe.passport.login.callback').'?flow='.$ticket->token(),
        ]);
    }

    /**
     * Finish an anonymous login: run the shipped anonymous chain and — the
     * whole point of the path — establish a Laravel session for the resolved
     * user.
     *
     * Every refusal is surfaced (401/403/422), never swallowed, and no
     * response, redirect, log line or exception message carries a session id,
     * a flow token, a nonce, a credential or a decrypted field value.
     */
    public function loginCallback(Request $request, PassportAnonymousLoginService $passport): JsonResponse|RedirectResponse
    {
        $config = $this->config();

        if (! $config->enabled() || ! $config->anonymousLoginEnabled()) {
            return $this->refused(
                'Anonymous passport login is disabled (set teleframe.passport.anonymous_login).',
                403,
            );
        }

        // WHO IS PRESENTING — before anything else, and before the chain can
        // touch the pending marker. Without a session there is no fingerprint
        // to compare, so there is no binding, so there is no login: refuse
        // rather than run a chain whose central check has nothing to check.
        $sessionFingerprint = $this->sessionFingerprint($request);
        if ($sessionFingerprint === null) {
            return $this->refused(
                'Unauthenticated: an anonymous passport login must be finished by the browser session that started it.',
                401,
            );
        }

        // An already-authenticated browser is refused here and pointed at the
        // attach flow. Letting a claim REPLACE a live session is the shape of
        // "log in as somebody else", and the authenticated route already owns
        // that case properly.
        $authenticatedUserId = $this->sessionUserId($request) ?? 0;
        if ($authenticatedUserId > 0) {
            return $this->refused(
                'This browser is already authenticated: use the authenticated passport flow to attach a Telegram identity.',
                403,
            );
        }

        $flowToken = $request->input('flow');
        if (! is_string($flowToken) || $flowToken === '') {
            return $this->refused(
                'The passport login flow token is required (start the login first).',
                422,
            );
        }

        $data = $this->credentials($request);
        if ($data === null) {
            return $this->refused(
                'Malformed passport credentials: data/hash/secret must be non-empty strings.',
                422,
            );
        }

        $fromId = $this->senderTgId($request->input('from_id', $request->input('from', [])));
        if ($fromId === null) {
            return $this->refused(
                'The sender telegram user id is required (passport is user-only; sender_chat is refused).',
                422,
            );
        }

        // The same minimal Bot API envelope the authenticated path builds,
        // with the §6 verified plaintext fields forwarded rather than dropped.
        // Nothing here is inspected, re-verified or trusted — the anonymous
        // service owns every field decision, including which claim may act.
        $update = [
            'message' => [
                'from' => ['id' => $fromId],
                'passport_data' => $this->passportData($request, $data),
            ],
        ];

        try {
            $result = $passport->login($update, $flowToken, $sessionFingerprint, $authenticatedUserId);
        } catch (PassportLoginException $refused) {
            return $this->refused($refused->getMessage(), 422);
        }

        $redirect = $config->successRedirect();
        if ($redirect !== '') {
            return redirect()->to($redirect);
        }

        return response()->json([
            'ok' => true,
            'tg_user_id' => $result->tgUserId,
            'account_id' => $result->accountId,
            'fields_verified' => $result->fieldsVerified,
        ]);
    }

    /**
     * The no-lock fallback: run the chain and turn a refusal into a
     * returned exception instead of an exception escaping, so the caller
     * answers it exactly like the locked path.
     *
     * @param  \Closure(): LoginResult  $run
     */
    private function runUnlocked(\Closure $run): LoginResult|PassportLoginException
    {
        try {
            return $run();
        } catch (PassportLoginException $e) {
            return $e;
        }
    }

    /**
     * The fingerprint of the session PRESENTING this request, or null when
     * there is no session to present.
     *
     * Null is a REFUSAL, not an unknown to be tolerated — and it is the same
     * posture as the `?int $sessionUserId` that was removed from
     * `PassportLoginService::login()`. Here it is what the anonymous endpoints
     * refuse 401 on, and a null can never reach
     * `PassportAnonymousLoginService::login()`, whose parameter is a
     * non-nullable string: an unbound anonymous login is not expressible.
     *
     * Only the DIGEST leaves this method. The session id is a credential
     * (AGENTS.md), so it is never returned, logged, or written anywhere but
     * into the pending marker's own store, as a hash.
     */
    private function sessionFingerprint(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        $session = $request->session();
        if (! $session->isStarted()) {
            // A guest arriving with no session has nothing to bind the marker
            // to. `web` starts one; a host that mounted this route without it
            // lands here and is refused instead of issuing an unbound flow.
            return null;
        }

        $id = $session->getId();
        if ($id === '') {
            return null;
        }

        return SessionFingerprint::of($id);
    }

    /**
     * The Laravel session user id, or null when there is no authenticated user.
     *
     * Passed to the login service so the consumed nonce can be checked against
     * the session presenting it. A null here is NOT a bypass and NOT a
     * tolerable unknown: callback() refuses the request outright, above, and
     * the service's parameter is non-nullable, so an unbound login cannot be
     * expressed at any layer.
     */
    private function sessionUserId(Request $request): ?int
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $key = $user->getKey();

        return is_int($key) ? $key : (is_numeric($key) ? (int) $key : null);
    }

    /**
     * Did this failure mean "this cache store cannot lock", as opposed to a
     * real error raised by the login chain itself?
     *
     * Laravel's Lock::block() rethrows whatever the callback threw, so a
     * blanket catch would re-run a chain that had already consumed the one-time
     * nonce — turning a 2FA or database failure into a bogus "replay" report.
     * Only a driver that cannot lock may fall through to the unlocked path.
     */
    private function isLockUnsupported(\Throwable $e): bool
    {
        // Two shapes reach here. A store that does not implement LockProvider
        // makes Cache::lock() throw BadMethodCallException (a LogicException:
        // "This cache store does not support locking."), while a driver that
        // cannot refresh raises a RuntimeException. Both mean the same thing
        // here — no lock capability, chain never started — and both are safe to
        // fall through. A genuine failure from inside the chain is a
        // PassportLoginException, handled by its own branch above.
        return ($e instanceof \RuntimeException || $e instanceof \LogicException)
            && str_contains(strtolower($e->getMessage()), 'lock');
    }

    // ── internals ────────────────────────────────────────────────────

    /**
     * data/hash/secret, from a `credentials` object or from the three flat
     * fields — looked for BOTH at the top level of the body and nested inside
     * a `passport_data` object, because a real Bot API `PassportData` carries
     * `credentials` as one of its two fields and a genuine callback therefore
     * nests them. The flat form is what the Telegram Passport website widget
     * POSTs. Null when any of the three is missing/empty (the engine's own
     * intake re-checks, including the per-field size caps).
     *
     * @return array{data: string, hash: string, secret: string}|null
     */
    private function credentials(Request $request): ?array
    {
        $source = $request->input();

        $passportData = $source['passport_data'] ?? null;
        if (is_array($passportData)) {
            $source = $passportData + $source;
        }

        $nested = $source['credentials'] ?? null;
        if (is_array($nested)) {
            $source = $nested + $source;
        }

        $data = $source['data'] ?? null;
        $hash = $source['hash'] ?? null;
        $secret = $source['secret'] ?? null;

        if (! is_string($data) || $data === ''
            || ! is_string($hash) || $hash === ''
            || ! is_string($secret) || $secret === '') {
            return null;
        }

        return ['data' => $data, 'hash' => $hash, 'secret' => $secret];
    }

    /**
     * The `PassportData` object the engine's intake expects: the three
     * credential fields plus the ELEMENT LIST they belong to.
     *
     * A Bot API `PassportData` has exactly two fields — `data` (an
     * `Array of EncryptedPassportElement`) and `credentials` (an
     * `EncryptedCredentials`). The verified email and phone live on the
     * ELEMENTS, as the plaintext `email` / `phone_number` field of an element
     * whose `type` is `"email"` / `"phone_number"` (§6) — beside that
     * element's own `data`/`hash`, never inside the encrypted blob. So this
     * method rebuilds the object by FORWARDING the element list the request
     * carried, and forwards it verbatim: it never inspects, copies, validates,
     * normalises, logs or echoes a single claim value. The engine's intake
     * (`PassportVerifiedClaims`) is the only thing that reads one.
     *
     * Rebuilding with `credentials` alone — which is what this did before the
     * re-plumb — discarded the element list, and the verified email and phone
     * are the ONLY coordinates this package reads an identity from, so an
     * email/phone login could not work through this controller no matter what
     * Telegram sent.
     *
     * The element list is accepted nested under `passport_data` (the real
     * shape) or at the top level of the body (how the Telegram Passport
     * website widget POSTs it). A host that has only the verified claims to
     * hand may pass them as top-level `email` / `phone_number` and they are
     * wrapped into correctly-typed elements here, so a hand-built callback
     * still exercises the same spec coordinate; the nested list wins when both
     * are present. Only arrays-of-arrays are accepted, and the count is
     * bounded (see `MAX_ELEMENTS`) so a hostile POST cannot make the intake
     * walk an unbounded list.
     *
     * @param  array{data: string, hash: string, secret: string}  $data
     * @return array{data: list<array<string, mixed>>, credentials: array<string, string>}
     */
    private function passportData(Request $request, array $data): array
    {
        $nested = $request->input('passport_data');
        $source = is_array($nested) ? $nested : [];

        $elements = $this->elements($source['data'] ?? $request->input('data'));
        if ($elements === []) {
            $elements = $this->claimElements($request, $source);
        }

        return ['data' => $elements, 'credentials' => $data];
    }

    /**
     * The request's own `EncryptedPassportElement` list, filtered to arrays and
     * bounded. Zero-regex: `is_array` and `array_slice` only.
     *
     * @return list<array<string, mixed>>
     */
    private function elements(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $elements = [];
        foreach ($value as $element) {
            if (is_array($element) && count($elements) < self::MAX_ELEMENTS) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    /**
     * Hand-posted claims wrapped into the spec's own shape: one element per
     * claim present, typed the way Telegram types it. Only the TYPE and the
     * field NAME are constructed here — the value is passed through, never
     * read, and `elements()` above still bounds the result.
     *
     * @param  array<string, mixed>  $source
     * @return list<array<string, mixed>>
     */
    private function claimElements(Request $request, array $source): array
    {
        $elements = [];

        $email = $this->plainClaim($source['email'] ?? $request->input('email'));
        if ($email !== null) {
            $elements[] = ['type' => 'email', 'email' => $email];
        }

        $phone = $this->plainClaim($source['phone_number'] ?? $request->input('phone_number'));
        if ($phone !== null) {
            $elements[] = ['type' => 'phone_number', 'phone_number' => $phone];
        }

        return $elements;
    }

    /**
     * A verified-claim value, or null when it is absent or not a non-empty
     * string. Type-checked and nothing more: this class never inspects,
     * validates, normalises or logs a field value.
     */
    private function plainClaim(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * The sender's telegram id — an int or a digit string (never 0, never a
     * sender_chat). Zero-regex, like the engine's own rule.
     */
    private function senderTgId(mixed $value): ?int
    {
        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && $value !== '' && ltrim($value, '0123456789') === '') {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function config(): PassportConfig
    {
        /** @var array<string, mixed> $values */
        $values = (array) config('teleframe.passport', []);

        return new PassportConfig($values);
    }

    /**
     * A surfaced refusal. The message is the engine's own (method names and
     * shapes only) — never a payload, a nonce or a decrypted field.
     */
    private function refused(string $message, int $status): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $message], $status);
    }
}
