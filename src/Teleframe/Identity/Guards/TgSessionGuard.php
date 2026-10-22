<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity\Guards;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Identity\TlUserBinding;

/**
 * Q10 `tg-session` guard: our user-app sessions -> binding -> Laravel User.
 *
 * The user-app login flow (TeleframeAuthService phone/QR/2FA) leaves the
 * logged-in telegram identity in the host session; this guard walks the
 * Q8 nullable morph from that identity to the bound Laravel User:
 *
 *   tg_user_id (session/attribute) -> TlUserBinding (primary-account default,
 *   Q7) -> morph `user` (Laravel User), else null (guest).
 *
 * Session source of truth (ruled, low-to-high precedence):
 *   1. explicit callable resolver (plain-PHP / non-session hosts),
 *   2. request attribute `telegram_session_user_id` / `telegram_session_account_id`,
 *   3. the Laravel session keys `telegram_session_user_id` then `telegram_user_id`
 *      — written by the host at its login-completion write-hook when the
 *      session middleware is mounted (guarded by Request::hasSession()).
 */
final class TgSessionGuard implements Guard
{
    private ?Authenticatable $user = null;

    private bool $resolved = false;

    /**
     * @param (callable(): int|string|null)|null $sessionUserIdResolver
     */
    public function __construct(
        private readonly Request $request,
        private readonly mixed $sessionUserIdResolver = null,
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

        return $this->user = $this->resolveFromSession();
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

    private function resolveFromSession(): ?Authenticatable
    {
        $identity = $this->sessionIdentity();
        if ($identity === null) {
            return null;
        }

        $tgId = (int) $identity;
        $accountId = $this->sessionAccountId();

        $binding = TlUserBinding::bindingFor($tgId, $accountId);
        $resolved = $binding?->resolver();

        return $resolved instanceof Authenticatable ? $resolved : null;
    }

    /**
     * @return int|string|null
     */
    private function sessionIdentity()
    {
        if (is_callable($this->sessionUserIdResolver)) {
            return ($this->sessionUserIdResolver)();
        }

        $attribute = $this->request->attributes->get('telegram_session_user_id');
        if (is_int($attribute) || (is_string($attribute) && ctype_digit($attribute))) {
            return (int) $attribute;
        }

        if ($this->request->hasSession()) {
            $session = $this->request->session();
            $key = $session->get('telegram_session_user_id', $session->get('telegram_user_id'));
            if (is_int($key) || (is_string($key) && ctype_digit($key))) {
                return (int) $key;
            }
        }

        return null;
    }

    private function sessionAccountId(): ?int
    {
        $attribute = $this->request->attributes->get('telegram_session_account_id');
        if (is_int($attribute) || (is_string($attribute) && ctype_digit($attribute))) {
            return (int) $attribute;
        }

        if ($this->request->hasSession()) {
            $account = $this->request->session()->get('telegram_session_account_id');
            if (is_int($account) || (is_string($account) && ctype_digit($account))) {
                return (int) $account;
            }
        }

        return null;
    }
}