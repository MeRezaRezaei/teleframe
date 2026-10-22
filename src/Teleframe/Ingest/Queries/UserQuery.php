<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Typed query builder for tf_users: user lookup by username, phone,
 * contact status, and bot flag.
 */
final class UserQuery extends Builder
{
    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);
    }

    public function forAccount(int $accountId): self
    {
        return $this->where('account_id', $accountId);
    }

    /**
     * Find by exact username, case-insensitively.
     *
     * `username` is a 1:1 FACT and lives on the CHILD table
     * `tf_users_username`, not on `tf_users` — the mirror flattens unions into
     * child tables precisely so a user without a username has no row. Filtering
     * the parent on `username` was therefore a guaranteed SQL error, not an
     * empty result. The correlated EXISTS is what the shape demands.
     *
     * LOWER() on both sides is portable (MySQL, PG, SQLite) where the
     * Postgres-only ILIKE the old docblock advertised is not. It does mean the
     * comparison cannot use a plain B-tree; the child table indexes `username`
     * so the lookup stays cheap on the common exact-prefix path.
     */
    public function byUsername(string $username): self
    {
        /** @var self $builder */
        $builder = $this->whereExists(function (QueryBuilder $child) use ($username): void {
            $child->selectRaw('1')
                ->from('tf_users_username')
                ->whereColumn('tf_users_username.account_id', 'tf_users.account_id')
                ->whereColumn('tf_users_username.id', 'tf_users.id')
                ->whereRaw('LOWER(tf_users_username.username) = LOWER(?)', [$username]);
        });

        return $builder;
    }

    /**
     * Find by phone number. Same 1:1-fact shape as byUsername(), on the
     * `tf_users_phone` child.
     */
    public function byPhone(string $phone): self
    {
        /** @var self $builder */
        $builder = $this->whereExists(function (QueryBuilder $child) use ($phone): void {
            $child->selectRaw('1')
                ->from('tf_users_phone')
                ->whereColumn('tf_users_phone.account_id', 'tf_users.account_id')
                ->whereColumn('tf_users_phone.id', 'tf_users.id')
                ->where('tf_users_phone.phone', $phone);
        });

        return $builder;
    }

    /**
     * Only bots.
     *
     * The column is `bot`, not `is_bot`: the mirror's own
     * 2026_09_14_200001 spells it `bot`, and EntityAggregator already reads it
     * correctly. Three methods here disagreed with the schema and with their
     * own sibling, which is why nothing caught it — the whole class is untested.
     */
    public function bots(): self
    {
        return $this->where('bot', true);
    }

    /** Only non-bot users. */
    public function humans(): self
    {
        return $this->where('bot', false);
    }

    /** Only contacts. */
    public function contacts(): self
    {
        return $this->where('contact', true);
    }

    /** Only self. */
    public function self(): self
    {
        return $this->where('self', true);
    }
}
