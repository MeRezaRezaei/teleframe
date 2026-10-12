<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * Boots the ingest database surface for logic tests (plan Task 1):
 * sqlite :memory: connection + the shipped curated dial (provider-registered
 * migrations/) migrated together with the off-dial root-namespace entity
 * anchors the ingest surface writes to (User first; grows with the walker).
 */
trait RunsMigrations
{
    /**
     * SQLite :memory: for logic tests — plan Tech Stack: PG-only paths
     * (deferrable FKs, uuid native) are exercised by the CI/live gate.
     *
     * ## `foreign_key_constraints => true` IS LOAD-BEARING, NOT A TIDY-UP
     *
     * `Illuminate\Database\Connectors\SQLiteConnector::configureForeignKeyConstraints()`
     * opens with `if (! isset($config['foreign_key_constraints'])) { return; }`
     * — a bare early-out. SQLite's own default is `PRAGMA foreign_keys = 0`,
     * so omitting this key leaves enforcement OFF: no exception, no warning,
     * no log line. Every "no FK clue" reading anywhere in this suite was
     * therefore a reading about a database that was not checking, which is
     * exactly the failure mode the operator named — "it can be used as a sign
     * of failure" — applied to the test suite itself.
     *
     * The key is asserted ON, not merely set: see
     * `tests/Ingest/IngestFkEnforcementIsOnTest.php`. A config key nobody
     * reads is a comment, and this defect already survived once.
     *
     * @param  Application  $app
     */
    protected function defineIngestDatabase($app): void
    {
        $app['config']->set('database.default', 'ingest');
        $app['config']->set('database.connections.ingest', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    /**
     * Migrate the shipped curated dial (112 migrations) plus
     * UpdateIngestor's entity anchors (off-dial root-namespace tables) in
     * one pass. `--path` replaces registered package paths, so the dial
     * dir is part of the path list.
     */
    protected function migrateIngestSurface(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ]);
    }

    /**
     * The tenant ids this test writes under, for `seedIngestTenant()`.
     *
     * ## WHY THIS IS A DECLARATION AND NOT A BLANKET AUTO-SEED
     *
     * The obvious alternative — seed whatever account id the test happens to
     * mention, discovered by scanning its own source — would make tenancy
     * something the harness infers instead of something the test states, and
     * would silently rob any test that means to plant an ORPHAN of the ability
     * to do so. A test that writes under an id it did not declare here fails
     * loudly, which is the behaviour worth having now that the database is
     * actually enforcing.
     *
     * The default is the empty list, so a test that touches no tenant FK needs
     * no change — and a test that starts needing one says so.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [];
    }

    /**
     * Plant the parent row every tenant FK points at, for `$userId`.
     *
     * ## WHY A TEST THAT INGESTS MUST CALL THIS
     *
     * Since `2026_10_04_000700` every tenant-bearing `tf_*` table carries a
     * foreign key to `telegram_accounts`, and the key is `user_id` — the
     * TELEGRAM user id — NOT the vault row's own `id`. That is settled by
     * `docs/decisions.md` D-022 (and D-005 as cited by the migration that
     * shipped the constraint) and stated by the shipped code itself:
     * `MirrorIngesterFactory.php:160` writes the caller's Telegram id straight
     * into `account_id`, and `TeleframeClient::resolveVaultAccount()` resolves
     * a numeric reference by `$vault->accountByUserId($userId)`. So in
     * production the parent row ALWAYS exists first: `VaultAddAccountCommand`
     * writes `user_id` when the account is added, long before any update is
     * ingested. A test that ingests under an account id with no such row is
     * describing a state the ingest surface cannot reach.
     *
     * ## WHY IT IS A CALL AND NOT A BLANKET AUTO-SEED
     *
     * Seeding every account id a test might mention, without being asked, would
     * quietly remove the need to think about tenancy — and the tests that DO
     * assert an orphan is refused would stop being able to plant one. Each test
     * declares the tenants it means to use, so a test that writes under an
     * unplanted id fails loudly instead of being rescued.
     *
     * Idempotent per id, because several tests use two tenants and calling it
     * twice for one of them must not violate `telegram_accounts_user_id_unique`.
     *
     * Synthetic values only — no session string, no bot token.
     */
    /**
     * Plant a PEER the way the mirror writes it: the entity row first, then its
     * access hash, because `tf_users_access_hash (account_id, id) -> tf_users` and
     * `tf_chats_access_hash (account_id, id) -> tf_chats` are composite foreign keys
     * and the hash cannot exist without the entity it hashes.
     *
     * Added because fixtures across tests/Console had been growing these rows by
     * hand, and each one silently skipped a parent until foreign-key enforcement
     * was switched on — at which point the test failed on its own fixture rather
     * than on the behaviour it exists to prove. Centralising the PAIR means the next
     * fixture cannot get only half of it, which is exactly the failure that happened.
     *
     * $accessHash is int|string because a Telegram access_hash is a 64-bit value
     * that does not always fit a PHP int and may be negative on the wire; the
     * column is a string and the fixtures must be able to express that.
     *
     * Synthetic values only — no session string, no real access hash.
     */
    protected function seedIngestPeer(int $accountId, int $id, string $kind = 'chat', int|string $accessHash = 5512378901644): void
    {
        $entity = $kind === 'user' ? 'tf_users' : 'tf_chats';
        $hashes = $kind === 'user' ? 'tf_users_access_hash' : 'tf_chats_access_hash';

        if (! DB::table($entity)->where('account_id', $accountId)->where('id', $id)->exists()) {
            DB::table($entity)->insert([
                'account_id' => $accountId,
                'id'         => $id,
                'constructor' => $kind,
            ]);
        }

        DB::table($hashes)->insert([
            'account_id'  => $accountId,
            'id'          => $id,
            'access_hash' => $accessHash,
        ]);
    }

    protected function seedIngestTenant(int ...$userIds): void
    {
        foreach ($userIds as $userId) {
            $exists = DB::table('telegram_accounts')->where('user_id', $userId)->exists();

            if ($exists) {
                continue;
            }

            DB::table('telegram_accounts')->insert([
                'user_id' => $userId,
                'label' => 'ingest-fixture-'.$userId,
                'type' => 'user',
                'dc_id' => 2,
            ]);
        }
    }
}
