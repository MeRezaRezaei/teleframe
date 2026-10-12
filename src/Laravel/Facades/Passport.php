<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;

/**
 * Passport facade (design 2026-09-24 §3): THE code face of the Telegram
 * Passport login surface — "let Laravel users login using it when one
 * configures them".
 *
 *     $nonce = Passport::issueNonce(Auth::id());  // host nonce → bot request (out of package)
 *     $result = Passport::login($update, Auth::id()); // decrypt → validate → nonce → resolve → bind
 *     Auth::login($result->user);                  // Cycle-8b: the HOST owns the session
 *
 * The accessor is the fixed teleclient.passport.service seam, so the facade,
 * `Teleframe::passport()` and the `teleframe:passport` CLI share ONE service
 * instance.
 *
 * Unconfigured (teleframe.passport.enabled=false): resolves and is callable —
 * login() throws loud on first real use, never silent-drop (Notify posture).
 *
 * `login()` REQUIRES the id of the session presenting the update. It is not
 * optional and not nullable: a nonce round-trips through Telegram and lands in
 * URLs and logs, so it proves the payload is intact and not who is presenting
 * it, and the binding check that closes that hole cannot skip. A host with no
 * authenticated session must not call it (breaking change, 2026-09-26 — pass
 * `Auth::id()`, which is what the callback endpoint does).
 *
 * @method static LoginResult login(array $update, int $sessionUserId)
 * @method static ?LoginResult handleUpdate(array $update, ?int $sessionUserId = null)
 * @method static string issueNonce(int $userId)
 * @method static PassportLoginService service()
 */
class Passport extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PassportSupport::SERVICE_KEY;
    }
}
