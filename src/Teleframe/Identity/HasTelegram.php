<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Q9 contactable-side trait: a Laravel User that can be reached ON Telegram.
 *
 * Adds the binding relation (bindings are morphMany against the nullable
 * morph — the User owning rows shares them with HasUserTelegram), a
 * Laravel-native `routeNotificationForTelegram()` override point the
 * TelegramChannel reads (return null to fall back to the configured default
 * sender, or the `{account_or_default, tg_id}` route shape), and the
 * `User::findTF()` convenience from the roadmap gate.
 *
 * Return shapes stay `Illuminate\Notifications\Notifiable`-compatible:
 * routeNotificationForTelegram is the exact same unreserved name Laravel's
 * channel manager probes for rebindable notification targets.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasTelegram
{
    /**
     * All bindings this Laravel User owns (hasMany through the nullable
     * morph, reverse of TlUserBinding::user()).
     */
    public function telegramBindings(): MorphMany
    {
        return $this->morphMany(TlUserBinding::class, 'user', 'user_type', 'user_id');
    }

    /**
     * Q9 override point + default: the telegram route on which to reach this
     * notifiable. Default = the binding's telegram id on its first known
     * account (account null => TelegramChannel substitutes the configured
     * default sender). Override in the model to hard-pin an account.
     *
     * @return array{account?: int|null, tg_id: int}|null
     */
    public function routeNotificationForTelegram(): ?array
    {
        $binding = $this->telegramBindings()
            ->where('account_id', '!=', null)
            ->orderBy('account_id')
            ->first();

        return $binding === null ? null : [
            'tg_id' => (int) $binding->tl_user_id,
            'account' => $binding->account_id,
        ];
    }

    /**
     * Q7 dev-facing find: the Laravel User reached by a telegram identity.
     * Primary-account default, explicit accountId override. Static so host
     * code writes `User::findTF($tgId)` — delegates to the binding service
     * (tenancy enforced there, single point).
     */
    public static function findTF(int $tgId, ?int $accountId = null): ?static
    {
        $binding = Bindings::findTF($tgId, $accountId);

        if ($binding === null) {
            return null;
        }

        /** @var Model|null $user */
        $user = $binding->resolver();

        return $user instanceof static ? $user : null;
    }

    /**
     * Convenience: the current primary binding of THIS user, if any.
     */
    public function telegramBinding(?int $accountId = null): ?TlUserBinding
    {
        $query = TlUserBinding::query()
            ->where('user_type', static::class)
            ->where('user_id', (int) $this->getKey());

        if ($accountId !== null) {
            return $query->where('account_id', $accountId)->first();
        }

        $primary = IdentityConfig::primaryAccountId();
        if ($primary !== null) {
            return $query->where('account_id', $primary)->first();
        }

        return $query->first();
    }
}