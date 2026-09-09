<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Eloquent scope that filters by `account_id` matching the
 * current {@see AccountContext}.  When no context is active the scope
 * is a no-op (returns all rows).
 */
final class AccountScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $accountId = AccountContext::current();

        if ($accountId !== null) {
            $builder->where($model->qualifyColumn('account_id'), $accountId);
        }
    }

    public function extend(Builder $builder): void
    {
        // No-op: no additional macros needed.
    }
}
