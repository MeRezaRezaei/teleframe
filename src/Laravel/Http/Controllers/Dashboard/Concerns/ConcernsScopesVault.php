<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;

/**
 * Tenant scoping for the dashboard controllers.
 *
 * The vault uses a nullable owner morph (owner_type / owner_id) so it works
 * with ANY host Laravel user model — no hardcoded App\Models\User. The
 * authenticated request user is resolved at call time and the scopes filter
 * by that concrete class + id; unauthenticated requests (a misconfiguration
 * of the dashboard middleware) resolve to NO rows instead of leaking data.
 */
trait ConcernsScopesVault
{
    /** @return Builder<TelegramApp> */
    private function scopedApps(Request $request): Builder
    {
        $user = $request->user();

        return TelegramApp::query()
            ->when($user !== null, fn (Builder $q): Builder => $q->where('owner_type', $user::class)->where('owner_id', (int) $user->getKey()))
            // whereKey(0) matches the impossible id 0 = zero rows (whereRaw
            // would widen the return type to Query\Builder under phpstan).
            ->when($user === null, fn (Builder $q): Builder => $q->whereKey(0));
    }

    /** @return Builder<TelegramAccount> */
    private function scopedAccounts(Request $request): Builder
    {
        $user = $request->user();

        return TelegramAccount::query()
            ->when($user !== null, fn (Builder $q): Builder => $q->where('owner_type', $user::class)->where('owner_id', (int) $user->getKey()))
            ->when($user === null, fn (Builder $q): Builder => $q->whereKey(0));
    }

    /** @return array<string, mixed>|null */
    private function owner(Request $request): ?array
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        return [
            'owner_type' => $user::class,
            'owner_id' => (int) $user->getKey(),
        ];
    }
}
