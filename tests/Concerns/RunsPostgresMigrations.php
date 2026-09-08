<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use PDO;

/**
 * Postgres track plumbing (Night W3): real PG connection config, a
 * disposable schema-per-test-class (SET search_path — tests never share
 * state, no dropAllTables dance), and the FULL generated migration set
 * (637 files, 3678 tables) — the surface sqlite cannot carry: native
 * uuid columns, DEFERRABLE INITIALLY DEFERRED cross-type FKs, and the
 * full-set DDL proof the curated dial never exercises.
 *
 * Connection truth is env-driven so the same code serves the local
 * peer-auth unix socket (user `me`, db teleframe_night_test, no password)
 * and the CI postgres:17 service (TCP + password):
 *   TELECLIENT_PG_HOST      default /var/run/postgresql
 *   TELECLIENT_PG_PORT      default 5432
 *   TELECLIENT_PG_DATABASE  default teleproto_night_test
 *   TELECLIENT_PG_USER      default me
 *   TELECLIENT_PG_PASSWORD  default empty (peer auth)
 */
trait RunsPostgresMigrations
{
    /** @var string|null schema owned by the current test class (null: none yet) */
    protected static ?string $pgSchema = null;

    /** @var class-string<self>|null the test class that owns $pgSchema (trait statics are shared by subclassing) */
    protected static ?string $pgSchemaOwner = null;

    /** @return array{host:string, port:string, database:string, user:string, password:string} */
    protected static function pgConnectionConfig(): array
    {
        return [
            'host' => (string) (getenv('TELECLIENT_PG_HOST') ?: '/var/run/postgresql'),
            'port' => (string) (getenv('TELECLIENT_PG_PORT') ?: '5432'),
            'database' => (string) (getenv('TELECLIENT_PG_DATABASE') ?: 'teleproto_night_test'),
            'user' => (string) (getenv('TELECLIENT_PG_USER') ?: 'me'),
            'password' => (string) (getenv('TELECLIENT_PG_PASSWORD') ?: ''),
        ];
    }

    protected static function pgDsn(): string
    {
        $cfg = self::pgConnectionConfig();

        return 'pgsql:host=' . $cfg['host'] . ';port=' . $cfg['port'] . ';dbname=' . $cfg['database'];
    }

    /** Fresh PDO outside the testbench app — schema lifecycle only. */
    protected static function pgLifecyclePdo(): PDO
    {
        $cfg = self::pgConnectionConfig();
        $pdo = new PDO(self::pgDsn(), $cfg['user'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $pdo->exec('SET search_path TO public');

        return $pdo;
    }

    /** Is the configured Postgres reachable? (env-forced runs skip the probe) */
    public static function pgReachable(): bool
    {
        try {
            self::pgLifecyclePdo();
        } catch (\PDOException) {
            return false;
        }

        return true;
    }

    /** Deterministic per-class schema name (drop-if-exists makes stale runs harmless). */
    protected static function pgSchemaName(): string
    {
        return 'tg_night_' . substr(sha1(static::class), 0, 12);
    }

    /** Create (or reset) this class's schema exactly once per test class. */
    protected static function ensurePostgresSchema(): void
    {
        if (self::$pgSchemaOwner === static::class) {
            return; // already set up for this class
        }
        $pdo = self::pgLifecyclePdo();
        if (self::$pgSchema !== null) {
            // leftover schema from the previous test class in this process
            self::dropSchemaByName($pdo, self::$pgSchema);
        }
        self::$pgSchema = static::pgSchemaName();
        self::dropSchemaByName($pdo, self::$pgSchema); // stale run leftovers
        $pdo->exec('CREATE SCHEMA ' . self::$pgSchema);
        self::$pgSchemaOwner = static::class;
    }

    /** Drop this class's schema (no-op when the track never started, e.g. skips). */
    protected static function dropPostgresSchema(): void
    {
        if (self::$pgSchema === null) {
            return;
        }
        self::dropSchemaByName(self::pgLifecyclePdo(), self::$pgSchema);
        self::$pgSchema = null;
        self::$pgSchemaOwner = null;
    }

    /**
     * Drop a schema in lock-sized batches: one `DROP SCHEMA … CASCADE` over
     * the full mirror (~3.7k tables, ~11k lockable objects) blows stock
     * PG's shared lock budget (max_locks_per_transaction ×
     * max_connections) — the same budget math that buckets the generated
     * FK migrations. FK constraints go FIRST: while the 1535 cross-type
     * FKs stand, every DROP TABLE … CASCADE also locks all referencing
     * tables, chaining across the whole mirror.
     */
    private static function dropSchemaByName(PDO $pdo, string $schema): void
    {
        // 1) Foreign keys, batched, SCHEMA-QUALIFIED (search_path is public
        //    here — unqualified names would silently resolve to nothing).
        $stmt = $pdo->prepare(
            "SELECT format('%I.%I', tn.nspname, t.relname), conname FROM pg_constraint c "
            . 'JOIN pg_class t ON t.oid = c.conrelid '
            . 'JOIN pg_namespace cn ON cn.oid = c.connamespace '
            . 'JOIN pg_namespace tn ON tn.oid = t.relnamespace '
            . "WHERE cn.nspname = ? AND c.contype = 'f'",
        );
        $stmt->execute([$schema]);
        $drops = [];
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as [$table, $constraint]) {
            $drops[] = 'ALTER TABLE ' . $table . ' DROP CONSTRAINT IF EXISTS "' . $constraint . '"';
        }
        foreach (array_chunk($drops, 200) as $batch) {
            $pdo->exec(implode(';', $batch));
        }

        // 2) Tables, batched (bare of FKs, CASCADE now only drops indexes).
        $stmt = $pdo->prepare('SELECT tablename FROM pg_tables WHERE schemaname = ?');
        $stmt->execute([$schema]);
        /** @var list<string> $tables */
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach (array_chunk($tables, 256) as $batch) {
            $pdo->exec('DROP TABLE IF EXISTS ' . implode(',', array_map(
                static fn (string $t): string => '"' . $schema . '"."' . $t . '"',
                $batch,
            )) . ' CASCADE');
        }

        $pdo->exec('DROP SCHEMA IF EXISTS "' . $schema . '" CASCADE');
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::dropPostgresSchema();
    }

    /**
     * Point the testbench app at Postgres with the class schema first on
     * search_path. The pgsql connector issues `set search_path to "…"` on
     * connect, so migrations, Eloquent and raw statements all land in the
     * disposable schema.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function definePostgresDatabase($app): void
    {
        $cfg = self::pgConnectionConfig();

        $app['config']->set('database.default', 'pg');
        $app['config']->set('database.connections.pg', [
            'driver' => 'pgsql',
            'host' => $cfg['host'],
            'port' => $cfg['port'],
            'database' => $cfg['database'],
            'username' => $cfg['user'],
            'password' => $cfg['password'],
            'charset' => 'utf8',
            'search_path' => static::pgSchemaName(),
            'prefix' => '',
        ]);
    }

    /**
     * Migrate the FULL generated set (637 migrations) — not the shipped
     * dial: the PG track exists precisely to prove what the 112-file dial
     * never runs (route tables, the bucketed deferrable-FK files, all
     * off-dial namespaces).
     */
    protected function migrateFullGeneratedSet(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => [dirname(__DIR__, 2) . '/generated/migrations'],
        ]);
    }

    /** User tables in the class schema (excludes the migrations bookkeeping table). */
    protected function pgTableCount(): int
    {
        return (int) \Illuminate\Support\Facades\DB::selectOne(
            'SELECT count(*) AS n FROM pg_tables WHERE schemaname = ?',
            [self::$pgSchema],
        )?->n;
    }
}
