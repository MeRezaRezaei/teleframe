<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\IdentityLock;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Support\RecordingConnection;

/**
 * P2 M3: duplicate-anchor guard under concurrency — identity resolution
 * + anchor upsert serialize per (account, identity-class, natural id).
 *
 * Cross-worker: PostgreSQL takes a transaction-scoped advisory lock via
 * raw SQL on the caller's connection; other drivers are a documented
 * no-op. In-process: a depth-counted key map — PHP workers are
 * single-threaded, so the map only has to tolerate nested (reentrant)
 * acquires for the same key without wedging.
 */
final class IdentityLockTest extends TestCase
{
    private const KEY = 'tl_anchor:7:tl_id:501558149';

    protected function tearDown(): void
    {
        IdentityLock::release(self::KEY);
        IdentityLock::release(self::KEY);

        parent::tearDown();
    }

    public function test_pg_takes_advisory_xact_lock_on_the_same_connection(): void
    {
        $connection = new RecordingConnection('pgsql');

        IdentityLock::acquire($connection, self::KEY);

        self::assertSame(
            [['select pg_advisory_xact_lock(hashtext(?))', [self::KEY]]],
            $connection->selects,
            'one raw select on the caller\'s connection, key bound — xact-scoped, released at commit/rollback',
        );
    }

    public function test_sqlite_and_mysql_are_a_documented_no_op(): void
    {
        foreach (['sqlite', 'mysql'] as $driver) {
            $connection = new RecordingConnection($driver);

            IdentityLock::acquire($connection, self::KEY);

            self::assertSame([], $connection->selects, "{$driver} has no advisory locks — no SQL may run");
            self::assertSame(1, IdentityLock::depth(self::KEY), "{$driver} still tracks the in-process key");

            IdentityLock::release(self::KEY);
        }
    }

    public function test_in_process_map_allows_nested_reentrant_acquires(): void
    {
        $connection = new RecordingConnection('sqlite'); // isolate the map from the SQL path

        IdentityLock::acquire($connection, self::KEY);
        IdentityLock::acquire($connection, self::KEY); // nested: same process, same region re-entered

        self::assertSame(2, IdentityLock::depth(self::KEY), 'reentrancy is allowed, depth-counted');

        IdentityLock::release(self::KEY);
        self::assertSame(1, IdentityLock::depth(self::KEY), 'one nesting level left');

        IdentityLock::release(self::KEY);
        self::assertSame(0, IdentityLock::depth(self::KEY), 'key fully released');
        self::assertSame([], $connection->selects);
    }

    public function test_releasing_a_free_key_is_a_no_op(): void
    {
        IdentityLock::release('tl_anchor:9:channel_id:unset-key');

        self::assertSame(0, IdentityLock::depth('tl_anchor:9:channel_id:unset-key'));
    }
}
