<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity;

/**
 * Q10 login-side trait: a Laravel User that "is" the telegram user app.
 *
 * Composes HasTelegram (its relations + route + findTF carry over intact),
 * then adds the LOGIN-side reverse direction: user_id -> binding -> telegram
 * account. This removes any trait-method collision when a User is BOTH
 * contactable (HasTelegram) and logged-in-as (HasUserTelegram) — the mini-app
 * User is exactly that, so the two traits must compose, not conflict.
 *
 * Login-side flow: user-app login completes -> the app calls
 * bindTelegramUser() (an explicit override point of the Q8 unique-keyed
 * binding) and the `tg-session` guard answers "which Laravel User is logged
 * in via the user-app session" through the same nullable morph.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasUserTelegram
{
    use HasTelegram;

    /**
     * Write-side convenience: record the telegram identity of the logged-in
     * user-app (primary account default). Idempotent — re-binding the same
     * (tg id, account) updates the morph, never duplicates.
     */
    public function bindTelegramUser(int $tgId, ?int $accountId = null): TlUserBinding
    {
        return Bindings::bindLaravelUser($this, $tgId, $accountId);
    }

    /**
     * Reverse-direction resolver (user_id -> binding -> telegram account):
     * the teleGRA user id this user-app is logged in as, primary-account by
     * default.
     */
    public function telegramUserId(?int $accountId = null): ?int
    {
        $binding = $this->telegramBinding($accountId);

        return $binding?->tl_user_id;
    }

    /**
     * The tenant account the user-app session runs on (primary-account
     * default). Convenience for the tg-session guard's tenant scoping.
     */
    public function telegramSessionAccount(): ?int
    {
        $binding = $this->telegramBinding();

        return $binding?->account_id;
    }
}