<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Vault\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;

/**
 * Optional trait for the developer's Laravel User model.
 * Grants polymorphic access to Teleframe vault apps and accounts
 * owned by this user — no pivot table needed (morph columns on
 * the vault tables themselves).
 *
 * Usage:
 *   class User extends Model { use HasTeleframe; }
 *   $user->telegramApps;      // MorphMany<TelegramApp>
 *   $user->telegramAccounts;  // MorphMany<TelegramAccount>
 */
trait HasTeleframe
{
    public function telegramApps(): MorphMany
    {
        return $this->morphMany(TelegramApp::class, 'owner');
    }

    public function telegramAccounts(): MorphMany
    {
        return $this->morphMany(TelegramAccount::class, 'owner');
    }
}
