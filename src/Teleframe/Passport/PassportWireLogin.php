<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;

/**
 * PassportWireLogin (Cycle 18 W3) — THE wire half of the passport login, and
 * the answer to the user's ask: *"next is telegram passport it must be able to
 * let laravel users login using it when one configures them."*
 *
 * `PassportLoginService` (Cycle 13) completes a login from an already-arrived
 * encrypted blob and makes NO Telegram call. This class makes the calls:
 *
 *   1. opt-in gate            — teleframe.passport.enabled (inert by default)
 *   2. auth.importAuthorization#a57a7dad → auth.Authorization
 *   3. refusal routing        — PASSWORD_REQUIRED / SESSION_PASSWORD_NEEDED
 *                               → the EXISTING PasswordConfirmer (2FA role
 *                               untouched), then ONE retry
 *   4. bind                   — the same BindingWriter the webhook path uses
 *   5. host hook              — the auth ride (Cycle-8b: the package never
 *                               calls Auth::login; the injected closure owns
 *                               the session)
 *
 * It returns the shipped `LoginResult`, so a host handles a webhook login and
 * a wire login with one value object. `fieldsVerified` is [] here: the
 * authorization import proves WHO logged in, not which passport fields were
 * verified — claiming otherwise would be a lie.
 *
 * The Laravel user is a PARAMETER, not a lookup: Cycle-8b says the package
 * never stops users and never owns the user, so the host passes the user it is
 * logging in (its own `Auth::user()`), and this class binds + rides. The
 * webhook path's `IdentityResolver` seam is for the other direction (a
 * credential arriving with no identified session).
 *
 * Refusals are never silently dropped:
 * - not enabled             → loud, and NO dispatch happens at all
 * - PASSWORD_REQUIRED       → routed to the confirmer; without a password or a
 *                             confirmer it becomes `PasswordRequiredException`,
 *                             which still names the 2FA remedy
 * - anything else           → logged at error and rethrown, never swallowed.
 *                             It reaches the host as a `PassportLoginException`
 *                             (the module's ONE base type, as the webhook path
 *                             does) whose `previous` is the original
 *                             `RpcErrorException` and whose message carries
 *                             Telegram's own wording; the PSR-3 line carries
 *                             the bare refusal token and never the bytes.
 *
 * Zero-regex; no container; plain-PHP constructible.
 */
final class PassportWireLogin
{
    public function __construct(
        private readonly PassportConfig $config,
        private readonly AuthorizationImporter $importer,
        private readonly BindingWriter $writer,
        private readonly ?PasswordConfirmerContract $confirmer = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?\Closure $onLogin = null,
    ) {}

    /**
     * Import a Telegram authorization and turn it into a bound login.
     *
     * @param  int  $authorizationId  the id from `auth.exportAuthorization`
     * @param  string  $bytes  the exported authorization bytes — a credential:
     *                         in-memory, in-call, never logged, never stored here
     * @param  Model  $user  the Laravel user this login is for (the host owns it)
     * @param  string|null  $password  the 2FA cloud password, used ONLY when
     *                                 Telegram answers PASSWORD_REQUIRED and a confirmer seam is bound
     *
     * @throws PassportLoginException when not enabled, or on any refusal
     * @throws PasswordRequiredException when a 2FA refusal could not be routed
     */
    public function login(int $authorizationId, string $bytes, Model $user, ?string $password = null): LoginResult
    {
        // The opt-in gate comes FIRST and dispatches NOTHING: an unconfigured
        // host must make no Telegram call at all.
        if (! $this->config->enabled()) {
            throw new PassportLoginException('passport login not enabled (configure teleframe.passport)');
        }

        $start = hrtime(true);

        try {
            return $this->chain($authorizationId, $bytes, $user, $password);
        } catch (PassportLoginException $e) {
            $this->logFailure('login', $e, $start);
            throw $e; // already a passport-domain refusal — never double-wrap
        } catch (\Throwable $e) {
            $this->logFailure('login', $e, $start);
            throw new PassportLoginException($e->getMessage(), $e);
        }
    }

    /**
     * The chain, step by step. Every step is loud on refusal.
     *
     * @throws PassportLoginException
     * @throws PasswordRequiredException
     */
    private function chain(int $authorizationId, string $bytes, Model $user, ?string $password): LoginResult
    {
        $imported = $this->importWith2faRoute($authorizationId, $bytes, $password);

        // The imported telegram user id IS the Identity binding key — the
        // same bigint the webhook path resolves from message.from.id, so a user
        // who logged in both ways is one binding, not two.
        $tgUserId = $imported->userId;
        $accountId = $this->config->primaryAccountId();
        $binding = $this->writer->bind($tgUserId, $accountId, $user);

        // The auth ride (Cycle-8b): the injected hook owns the session.
        $onLogin = $this->onLogin;
        if ($onLogin !== null) {
            $onLogin($user);
        }

        return new LoginResult(
            tgUserId: $tgUserId,
            accountId: $accountId,
            user: $user,
            binding: $binding,
        );
    }

    /**
     * `auth.importAuthorization#a57a7dad`, with the ONE documented 2FA route.
     *
     * A `PASSWORD_REQUIRED` refusal is not a failed login: it is Telegram saying
     * "this account needs its cloud password for passport". The already-shipped
     * PasswordConfirmer runs the exact SRP chain
     * (`account.getPassword` → `compute2faProof` → `auth.checkPassword`) and the
     * import is retried ONCE. A second refusal, a missing confirmer, or a
     * missing password all end in `PasswordRequiredException` — named, so the
     * host knows the remedy — and never in a swallowed error.
     *
     * @throws PassportLoginException
     * @throws PasswordRequiredException
     */
    private function importWith2faRoute(int $authorizationId, string $bytes, ?string $password): ImportedAuthorization
    {
        try {
            return $this->importer->import($authorizationId, $bytes);
        } catch (\Throwable $e) {
            if (! PassportRefusal::passwordRequired($e)) {
                throw $e; // not a 2FA refusal — the original refusal, untouched
            }

            $confirmer = $this->confirmer;
            if ($confirmer === null) {
                throw new PasswordRequiredException(
                    'telegram refused the passport import with '
                    .PassportRefusal::tokenOf($e).' but no '.PasswordConfirmer::class
                    .' seam is bound — bind '.PassportSupport::CONFIRMER_KEY.' to complete the 2FA step',
                    $e
                );
            }

            if ($password === null || $password === '') {
                throw new PasswordRequiredException(
                    'telegram refused the passport import with '
                    .PassportRefusal::tokenOf($e).' — the 2FA cloud password is required'
                    .' (the password is never logged)',
                    $e
                );
            }

            // The 2FA role of PasswordConfirmer is untouched: it is called, and
            // its own failure contract (log + rethrow) stands.
            $confirmer->confirm($password);

            try {
                return $this->importer->import($authorizationId, $bytes);
            } catch (\Throwable $retry) {
                if (PassportRefusal::passwordRequired($retry)) {
                    throw new PasswordRequiredException(
                        'telegram still refuses the passport import with '
                        .PassportRefusal::tokenOf($retry).' after the 2FA step completed',
                        $retry
                    );
                }

                throw $retry;
            }
        }
    }

    /**
     * PSR-3 error with the refusal token and the elapsed ms ONLY — never
     * payloads, authorization bytes, keys, passwords or nonces (AGENTS.md
     * credentials rule).
     */
    private function logFailure(string $method, \Throwable $e, int $startNanos): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->error(
            'PassportWireLogin::'.$method.' failed after '
            .((int) round((hrtime(true) - $startNanos) / 1e6)).' ms',
            [
                'exception' => $e::class,
                'method' => $method,
                'refusal' => PassportRefusal::tokenOf($e),
            ],
        );
    }
}
