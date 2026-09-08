<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use Illuminate\Database\Connection;

/**
 * Driver-stubbed connection recording raw SQL calls (P2 M3 tests):
 * IdentityLock's advisory-lock statement must run on the caller's
 * connection, inside the caller's transaction, with the lock key bound —
 * this records enough to assert exactly that without a database.
 */
final class RecordingConnection extends Connection
{
    /** @var list<array{0: string, 1: list<mixed>}> [sql, bindings] per call */
    public array $selects = [];

    public function __construct(string $driver)
    {
        parent::__construct(null, 'testing', '', ['name' => 'testing', 'driver' => $driver]);
    }

    public function select($query, $bindings = [], $useReadPdo = true): array
    {
        $this->selects[] = [$query, $bindings];

        return [];
    }

    public function statement($query, $bindings = []): bool
    {
        $this->selects[] = [$query, $bindings];

        return true;
    }
}
