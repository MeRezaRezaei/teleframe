<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\BackupRunIndexContract;
use MeRezaRezaei\Teleframe\Store\Backup\MemoryBackupRunIndex;
use PHPUnit\Framework\TestCase;

/**
 * MemoryBackupRunIndex (2026-09-24 backup-store remainder design §2) —
 * the array run ledger used by tests, the standalone smoke and any
 * sandbox without a database surface. Contract semantics: upserts replace
 * the {account, object_name} row idempotently, listRuns is newest-first
 * (internal monotonic id, exactly like the Eloquent table's id) with the
 * bucket filter and limit applied, deleteRun forgets one run.
 *
 * Zero regex, no container, no persistence.
 */
final class BackupRunIndexTest extends TestCase
{
    public function test_implements_the_backup_run_index_contract(): void
    {
        self::assertInstanceOf(BackupRunIndexContract::class, new MemoryBackupRunIndex);
    }

    public function test_upsert_list_roundtrip_across_buckets_with_limit(): void
    {
        $index = new MemoryBackupRunIndex;

        $index->upsertRun($this->row('bucket-a', 'appdb-20231114-221320.sql', 'ok', 100));
        $index->upsertRun($this->row('bucket-a', 'appdb-20231114-231320.sql', 'ok', 200));
        $index->upsertRun($this->row('bucket-a', 'appdb-20231115-001320.sql', 'queued', 0));
        $index->upsertRun($this->row('bucket-b', 'appdb-20231115-011320.sql', 'ok', 300));
        $index->upsertRun($this->row('bucket-b', 'appdb-20231115-021320.sql', 'ok', 400));

        // All buckets, newest first (id desc = last upsert first).
        $all = $index->listRuns(7, null, 10);
        self::assertCount(5, $all);
        self::assertSame('appdb-20231115-021320.sql', $all[0]['object_name']);
        self::assertSame('appdb-20231114-221320.sql', $all[4]['object_name']);

        // Bucket filter.
        $onlyA = $index->listRuns(7, 'bucket-a', 10);
        self::assertCount(3, $onlyA);
        foreach ($onlyA as $row) {
            self::assertSame('bucket-a', $row['bucket']);
        }

        // Limit applies AFTER the newest-first order.
        $top2 = $index->listRuns(7, null, 2);
        self::assertSame(['appdb-20231115-021320.sql', 'appdb-20231115-011320.sql'], array_column($top2, 'object_name'));

        // Accounts are isolated.
        self::assertSame([], $index->listRuns(8, null, 10));
    }

    public function test_upsert_is_an_idempotent_replace_of_the_object_name_row(): void
    {
        $index = new MemoryBackupRunIndex;

        $index->upsertRun($this->row('bucket-a', 'appdb-20231114-221320.sql', 'ok', 100));
        $index->upsertRun($this->row('bucket-a', 'appdb-20231114-221320.sql', 'ok', 999));

        // Idempotent by {account, object_name}: one row, latest bytes win.
        $rows = $index->listRuns(7, null, 10);
        self::assertCount(1, $rows);
        self::assertSame(999, $rows[0]['bytes']);

        // The re-upsert assigns a fresh monotonic id (the memory ledger
        // stamps ids, incoming rows carry none) — newest-first ordering
        // stays honest across re-runs of the same object name.
        $id = (int) $rows[0]['id'];
        $index->upsertRun($this->row('bucket-a', 'appdb-20231114-221320.sql', 'ok', 1000));
        $after = $index->listRuns(7, null, 10);
        self::assertCount(1, $after);
        self::assertSame(1000, $after[0]['bytes']);
        self::assertGreaterThan($id, (int) $after[0]['id']);
    }

    public function test_delete_run_forgets_one_object_name(): void
    {
        $index = new MemoryBackupRunIndex;

        $index->upsertRun($this->row('bucket-a', 'appdb-20231114-221320.sql', 'ok', 100));
        $index->upsertRun($this->row('bucket-a', 'appdb-20231115-001320.sql', 'ok', 200));

        $index->deleteRun(7, 'appdb-20231114-221320.sql');

        $rows = $index->listRuns(7, null, 10);
        self::assertCount(1, $rows);
        self::assertSame('appdb-20231115-001320.sql', $rows[0]['object_name']);

        // Deleting an unknown run is a no-op, not an error.
        $index->deleteRun(7, 'ghost.sql');
        self::assertCount(1, $index->listRuns(7, null, 10));
    }

    /** @return array<string, mixed> */
    private function row(string $bucket, string $objectName, string $status, int $bytes): array
    {
        return [
            'account_id' => 7,
            'bucket' => $bucket,
            'driver' => 'mysql',
            'database' => 'appdb',
            'object_name' => $objectName,
            'bytes' => $bytes,
            'chunks' => 1,
            'sha256' => str_repeat('ab', 32),
            'mode' => 'sealed',
            'status' => $status,
            'heartbeat_at_unix' => 1700000000,
        ];
    }
}
