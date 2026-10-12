<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Builder;

/**
 * Bootable trait applied to every generated model so the
 * {@see AccountScope} global scope is active by default.
 *
 * Provides query helpers to override the default scope:
 * - {@see forAccount()} sets a temporary account context and re-filters
 * - {@see acrossAccounts()} drops the global scope entirely
 * - Query scopes: `scopeForAccount`, `scopeAcrossAccounts`
 */
trait AccountScoped
{
    public static function bootAccountScoped(): void
    {
        static::addGlobalScope(AccountScope::class, new AccountScope());
    }

    // ── Query scopes ────────────────────────────────────────────────

    /**
     * Drop the global scope and re-filter to a specific account.
     */
    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query
            ->withoutGlobalScope(AccountScope::class)
            ->where($query->getModel()->qualifyColumn('account_id'), $accountId);
    }

    /**
     * Drop the global AccountScope so the query sees all accounts.
     */
    public function scopeAcrossAccounts(Builder $query): Builder
    {
        return $query->withoutGlobalScope(AccountScope::class);
    }

    // ── Static forwarding helpers ───────────────────────────────────

    /**
     * Query scoped to a specific account (convenience wrapper).
     */
    public static function forAccount(int $accountId): Builder
    {
        return static::query()
            ->withoutGlobalScope(AccountScope::class)
            ->where((new static())->qualifyColumn('account_id'), $accountId);
    }

    /**
     * Query that sees all accounts (convenience wrapper).
     */
    public static function acrossAccounts(): Builder
    {
        return static::query()->withoutGlobalScope(AccountScope::class);
    }
}
