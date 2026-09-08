<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity\Guards;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Identity\IdentityConfig;
use MeRezaRezaei\Teleframe\Identity\TlUserBinding;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\VerifyMiniAppInitData;

/**
 * Q10 `tg-webapp` guard (RequestGuard wrapper for Mini App init-data).
 *
 * Reuses the existing `VerifyMiniAppInitData` HMAC core by INSTANTIATION —
 * the middleware's public behavior is untouched (audited in Phase 5b: it is
 * not modified there). This guard adds the two steps the middleware's raw RT
 * contract intentionally leaves out:
 *
 *  - FRESHNESS: init-data without an `auth_date` or older than
 *    `teleframe.identity.miniapp_auth_max_age` (default 1800s) is REJECTED —
 *    closing the middleware's "infinite replay window" friction F4.
 *  - BINDING resolution (Q8/Q10): the validated telegram user id is resolved
 *    through `tl_user_bindings` (primary-account default, Q7) to the bound
 *    Laravel User. Unbound / plain-PHP => null (guest) — the binding's
 *    nullable morph means this guard is safe with NO Laravel User at all.
 *
 * It also re-uses the middleware's exact configured-bot-token fallback chain
 * (word-for-word) so a session accepted here is accepted by `tg.miniapp`.
 */
final class TgWebAppGuard implements Guard
{
    private ?Authenticatable $user = null;

    private bool $resolved = false;

    public function __construct(
        private readonly Request $request,
        private readonly ?string $explicitBotToken = null,
    ) {
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;

        return $this->user = $this->resolveFromInitData();
    }

    /**
     * @return int|string|null
     */
    public function id()
    {
        $user = $this->user();
        if ($user === null) {
            return null;
        }

        return $user->getAuthIdentifier();
    }

    /**
     * Q10 credential gate: a request is "valid" when its init-data HMAC is
     * genuine, fresh, and names a telegram user resolvable to a Laravel User.
     *
     * @param array<string, mixed> $credentials
     */
    public function validate(array $credentials = []): bool
    {
        return $this->user() !== null;
    }

    public function hasUser(): bool
    {
        return $this->user() !== null;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;
        $this->resolved = true;

        return $this;
    }

    private function resolveFromInitData(): ?Authenticatable
    {
        $initData = $this->request->header('X-Telegram-Init-Data')
            ?? $this->request->input('initData');
        if (! is_string($initData) || $initData === '') {
            return null;
        }

        $token = $this->explicitBotToken ?? IdentityConfig::botToken();
        if ($token === null) {
            return null;
        }

        // Reuse (never reimplement) the middleware's HMAC core.
        $validated = (new VerifyMiniAppInitData())->validateInitData($initData, $token);
        if ($validated === null) {
            return null;
        }

        if (! $this->isFreshEnough($initData)) {
            return null;
        }

        $tgId = $validated['id'] ?? null;
        if (! is_int($tgId) && ! (is_string($tgId) && ctype_digit($tgId))) {
            return null;
        }

        // Q7: explicit account_id request param wins, else primary account.
        $accountOverride = $this->request->input('account_id');
        $accountId = (is_int($accountOverride) || (is_string($accountOverride) && ctype_digit($accountOverride)))
            ? (int) $accountOverride
            : null;

        $binding = TlUserBinding::bindingFor((int) $tgId, $accountId);
        $resolved = $binding?->resolver();

        return $resolved instanceof Authenticatable ? $resolved : null;
    }

    /**
     * Refresh-window gate: reject absent/auth_date-stale sessions (F4). A
     * replayed init-data skips into the window or fails the HMAC — either
     * way it never authenticates.
     */
    private function isFreshEnough(string $initData): bool
    {
        $authDate = self::authDate($initData);
        if ($authDate === null) {
            return false;
        }

        return (time() - $authDate) <= IdentityConfig::miniappAuthMaxAge();
    }

    private static function authDate(string $initData): ?int
    {
        foreach (explode('&', $initData) as $pair) {
            $parts = explode('=', $pair, 2);
            if ($parts[0] === 'auth_date' && isset($parts[1])) {
                $value = urldecode($parts[1]);

                return ctype_digit($value) ? (int) $value : null;
            }
        }

        return null;
    }
}