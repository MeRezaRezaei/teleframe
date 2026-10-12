<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\TtlJanitor;

/**
 * Phase C re-baseline: the TTL janitor against the CURATED dial.
 *
 * The legacy sweeper targeted the generated tf_stories table (expire_date
 * column). That surface was purged with the legacy mirror, and NO curated
 * hand-authored table carries an expiry-sweep column — the janitor's TTL
 * table set is empty by design until the curated dial gains one. The
 * janitor therefore sweeps nothing and reports per-table results []
 * regardless of what the mirror holds.
 */
class TtlJanitorTest extends IngestTestCase
{
    private const ACCOUNT = 1;

    /**
     * Tenant 1 (`ACCOUNT`, spelled as the literal `1` in both arms).
     *
     * `test_sweep_never_touches_curated_rows` inserts two `tf_messages` rows
     * under account 1 — one with a live wire date, one with `date => 999` — so
     * a date-driven sweep would delete exactly one. `tf_messages.account_id` is
     * the Telegram user id and its FK targets `telegram_accounts.user_id`, not
     * the vault row's own `id` (`MirrorIngesterFactory.php:160` writes the
     * caller's Telegram id straight into `account_id`).
     *
     * Production always plants the parent first:
     * `VaultAddAccountCommand.php:80` writes `user_id` at account-add time.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [1];
    }

    public function test_curated_dial_exposes_no_ttl_sweep_surface(): void
    {
        // The legacy sweep target is gone; no curated fact table carries an
        // expiry column for the janitor to run against.
        self::assertFalse(Schema::hasTable('tf_stories'));
        self::assertFalse(Schema::hasColumn('tf_messages', 'expire_date'));
        self::assertFalse(Schema::hasColumn('tf_updates', 'expires_at'));

        $results = (new TtlJanitor)->sweep();
        self::assertSame([], $results, 'no per-table results when no TTL surface exists');
    }

    public function test_sweep_never_touches_curated_rows(): void
    {
        DB::table('tf_messages')->insert([
            ['account_id' => 1, 'id' => 1, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 1, 'date' => 1724852400, 'message' => 'a'],
            ['account_id' => 1, 'id' => 2, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 1, 'date' => 999, 'message' => 'b'],
        ]);

        $results = (new TtlJanitor)->sweep();

        self::assertSame([], $results);
        self::assertSame(2, DB::table('tf_messages')->where('account_id', 1)->count(), 'even long-past wire dates are not expiry-swept');
    }

    public function test_batch_size_acceptance_is_preserved(): void
    {
        // The janitor still accepts the TDLib-style batch-size seam; with an
        // empty sweep set the limit is trivially satisfied.
        $janitor = new TtlJanitor(batchSize: 2);
        self::assertSame([], $janitor->sweep());
    }
}
