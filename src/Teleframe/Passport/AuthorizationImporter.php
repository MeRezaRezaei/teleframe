<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Psr\Log\LoggerInterface;

/**
 * AuthorizationImporter (Cycle 18 W3) — `auth.importAuthorization#a57a7dad
 * id:long bytes:bytes = auth.Authorization`, the OFFICIAL layer-227 constructor
 * (TL_telegram_v227.tl:2243, mirrored in src/Schema/schema/sources/api_full.tl;
 * in the wire closure since the 2026-09-25 seed completion, see
 * docs/superpowers/specs/2026-09-24-passport-login-gap-analysis.md §3.1).
 *
 * THIS is the call that turns a passport exchange into a login: it takes the
 * authorization another party exported and returns the `auth.Authorization`
 * that is now this session's, carrying the telegram user id the Identity
 * binding is keyed on. The dispatch carries the method name and EXACTLY the
 * two documented params — a test pins both, plus the official id in
 * PassportWireContract::OFFICIAL_IDS.
 *
 * Refusal contract (the success criteria, word for word):
 * - `PASSWORD_REQUIRED` / `SESSION_PASSWORD_NEEDED` are NOT swallowed and NOT
 *   reclassified: they propagate as themselves (typed `RpcErrorException`) so
 *   PassportWireLogin can route them to the existing PasswordConfirmer. A test
 *   pins that a 2FA refusal is still `PASSWORD_REQUIRED` after leaving here.
 * - `auth.authorizationSignUpRequired` is refused LOUD: Telegram wants a
 *   sign-up, which is not a login, and returning a half-imported authorization
 *   would be a lie.
 * - an authorization with no usable user id is refused loud — binding 0 to a
 *   Laravel user is exactly the silent corruption this module must not do.
 *
 * Zero-regex; no container; plain-PHP constructible.
 */
final class AuthorizationImporter
{
    /** The authorization constructor a successful import returns. */
    public const CONSTRUCTOR = 'auth.authorization';

    /** Telegram wants a sign-up instead of a login — never silently accepted. */
    public const CONSTRUCTOR_SIGN_UP_REQUIRED = 'auth.authorizationSignUpRequired';

    public function __construct(
        private readonly PassportWireContract $wire,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * @param  int  $id  the exported authorization id (auth.exportedAuthorization.id)
     * @param  string  $bytes  the exported authorization bytes — a credential:
     *                         in-memory, in-call, never logged, never persisted here
     *
     * @throws PassportLoginException on a sign-up demand or an unusable user id
     */
    public function import(int $id, string $bytes): ImportedAuthorization
    {
        if ($id <= 0) {
            throw new PassportLoginException(
                'auth.importAuthorization needs a positive authorization id (got '.$id.')'
            );
        }

        if ($bytes === '') {
            throw new PassportLoginException('auth.importAuthorization needs the exported authorization bytes');
        }

        // The dispatch: the OFFICIAL method name, exactly the two documented
        // params. Nothing else is added, and nothing is logged.
        $response = $this->wire->call(PassportWireContract::METHOD_IMPORT_AUTHORIZATION, [
            'id' => $id,
            'bytes' => $bytes,
        ]);

        $constructor = (string) ($response['_'] ?? '');
        if ($constructor === self::CONSTRUCTOR_SIGN_UP_REQUIRED) {
            throw new PassportLoginException(
                'telegram answered the import with auth.authorizationSignUpRequired — a sign-up is required,'
                .' which is not a passport login (refused, not treated as imported)'
            );
        }

        $user = $response['user'] ?? null;
        if (! is_array($user)) {
            throw new PassportLoginException(
                'auth.importAuthorization returned no user (constructor "'.($constructor === '' ? 'none' : $constructor).'")'
            );
        }

        $userId = $user['id'] ?? null;
        if (! is_int($userId) && ! (is_string($userId) && ltrim($userId, '0123456789') === '')) {
            throw new PassportLoginException(
                'auth.importAuthorization returned a user with no usable id (refused — never bind 0)'
            );
        }

        $imported = new ImportedAuthorization((int) $userId, $user, $response);

        if ($this->logger !== null) {
            // The credential-free projection only.
            $this->logger->info('auth.importAuthorization returned an authorization', $imported->toArray());
        }

        return $imported;
    }
}
