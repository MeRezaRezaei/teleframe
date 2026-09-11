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

    /** Find by exact username (case-insensitive via Postgres ILIKE). */
    public function byUsername(string $username): self
    {
        return $this->whereRaw('LOWER(username) = LOWER(?)', [$username]);
    }

    /** Find by phone number. */
    public function byPhone(string $phone): self
    {
        return $this->where('phone', $phone);
    }

    /** Only bots. */
    public function bots(): self
    {
        return $this->where('is_bot', true);
    }

    /** Only non-bot users. */
    public function humans(): self
    {
        return $this->where('is_bot', false);
    }

    /** Only contacts. */
    public function contacts(): self
    {
        return $this->where('is_contact', true);
    }

    /** Only self. */
    public function self(): self
    {
        return $this->where('is_self', true);
    }
}
