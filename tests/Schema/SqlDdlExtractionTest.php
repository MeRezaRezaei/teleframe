<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\SqlDdl\SqlDdlEmitter;
use MeRezaRezaei\Teleframe\Schema\Generator\TeleframeSchemeLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * SQL-extraction plan gates (§7): the emitted schema/ddl/*.sql track is
 * exactly the .tl decoded — exact §2 types, natural/serial PKs, partial
 * indexes, per-table fillfactor — deterministic across runs, and compiled
 * against a real Postgres (in a rolled-back transaction, zero side effects).
 */
final class SqlDdlExtractionTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__ . '/../..';
    private const SOURCES = self::PACKAGE_ROOT . '/schema/sources';
    private const TABLES = [
        'tf_users', 'tf_chats', 'tf_channels', 'tf_messages', 'tf_dialogs',
        'tf_updates', 'tf_documents', 'tf_photos', 'tf_sticker_sets',
        'tf_stories', 'tf_wallpapers', 'tf_channel_participants',
    ];

    public function test_regeneration_emits_all_domain_ddl_files(): void
    {
        $out = sys_get_temp_dir() . '/tl-ddl-tree-' . getmypid();
        try {
            (new SchemaRegenerator())->regenerate(self::SOURCES, $out);

            foreach (self::TABLES as $table) {
                $this->assertFileExists("{$out}/schema/ddl/{$table}.sql", "missing {$table}.sql");
            }
            foreach (glob("{$out}/schema/ddl/*.sql") as $file) {
                $this->assertStringContainsString('@generated', (string) file_get_contents($file));
            }
        } finally {
            exec('rm -rf ' . escapeshellarg($out));
        }
    }

    public function test_exact_types_and_key_strategy_contract(): void
    {
        $emitter = new SqlDdlEmitter();
        $ddl = $emitter->generate($this->loadScheme())['tf_users.sql'];

        $this->assertStringContainsString('PRIMARY KEY (account_id, id)', $ddl);

        $this->assertStringContainsString('PRIMARY KEY (account_id, id)', $ddl);
        $this->assertStringContainsString('is_bot BOOLEAN NOT NULL DEFAULT false', $ddl);
        $this->assertStringContainsString('username VARCHAR(32)', $ddl);
        $this->assertStringContainsString('id BIGINT', $ddl);
        $this->assertStringContainsString('tl_data JSONB NOT NULL', $ddl);
        $this->assertStringContainsString(
            'CREATE INDEX tf_users_username_partial_idx ON tf_users (username) WHERE username IS NOT NULL;',
            $ddl,
        );
        $this->assertStringContainsString(') WITH (fillfactor = 90);', $ddl);
        $this->assertStringNotContainsString("\n    text", $ddl); // no bare-text column (header prose may say "text")
        $this->assertStringNotContainsString('bigint unsigned', $ddl);
    }

    public function test_natural_pk_drops_surrogate_and_serial_keeps_bigserial(): void
    {
        $all = (new SqlDdlEmitter())->generate($this->loadScheme());

        $messages = $all['tf_messages.sql'];
        $this->assertStringContainsString('PRIMARY KEY (peer_id, message_id, account_id)', $messages);
        $this->assertStringNotContainsString("id BIGSERIAL", $messages);

        $dialogs = $all['tf_dialogs.sql'];
        $this->assertStringContainsString('PRIMARY KEY (peer_id, account_id)', $dialogs);

        $participants = $all['tf_channel_participants.sql'];
        $this->assertStringContainsString('PRIMARY KEY (channel_id, user_id, account_id)', $participants);

        $updates = $all['tf_updates.sql'];
        $this->assertStringContainsString('id BIGSERIAL PRIMARY KEY', $updates);
        $this->assertStringNotContainsString('PRIMARY KEY (id)', $updates);
        $this->assertStringContainsString(') WITH (fillfactor = 100);', $updates);
        // No per-row updated_at column (append-only log); header prose may name it.
        $this->assertStringNotContainsString("\n    updated_at", $updates);
    }

    public function test_long_not_big_for_int32_and_peer_refs_collapse(): void
    {
        $messages = (new SqlDdlEmitter())->generate($this->loadScheme())['tf_messages.sql'];

        // .tl int32 date stays INTEGER (never timestamptz / bigint); Peer refs → BIGINT.
        $this->assertStringContainsString('date INTEGER', $messages);
        $this->assertStringContainsString('message_id INTEGER', $messages);
        $this->assertStringContainsString('from_id BIGINT', $messages);
    }

    public function test_ddl_is_deterministic_across_two_runs(): void
    {
        $outA = sys_get_temp_dir() . '/tl-ddl-a-' . getmypid();
        $outB = sys_get_temp_dir() . '/tl-ddl-b-' . getmypid();
        try {
            (new SchemaRegenerator())->regenerate(self::SOURCES, $outA);
            (new SchemaRegenerator())->regenerate(self::SOURCES, $outB);
            self::assertSame(self::ddlHashes($outA), self::ddlHashes($outB), 'two runs differ');
        } finally {
            exec('rm -rf ' . escapeshellarg($outA) . ' ' . escapeshellarg($outB));
        }
    }

    public function test_manifest_field_drift_rejected_at_emit(): void
    {
        // userBad declares only id:long — tf_users also promotes access_hash
        // → the manifest drifts from the .tl and MUST be rejected.
        $mini = TeleframeSchemeLoader::parseString(
            "---types---\nuserBad#31774388 id:long = User;\n",
            'drift.tl',
        );
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/tf_users.*access_hash|access_hash.*tf_users/s');
        (new SqlDdlEmitter())->generate($mini);
    }

    /**
     * §7 DDL-compile gate: the emitted SQL must parse on a real Postgres.
     * Runs inside one transaction then rolls back — server state untouched.
     * Skipped when psql is absent or the socket peer cannot connect.
     */
    public function test_ddl_compiles_against_postgres(): void
    {
        if (!is_executable('/usr/bin/psql')) {
            $this->markTestSkipped('psql not available');
        }
        $out = sys_get_temp_dir() . '/tl-ddl-pg-' . getmypid();
        try {
            (new SchemaRegenerator())->regenerate(self::SOURCES, $out);
            $combined = $out . '/combined-ddl.sql';
            $files = glob("{$out}/schema/ddl/*.sql") ?: [];
            sort($files);
            file_put_contents($combined, implode("\n", array_map(
                static fn ($f): string => (string) file_get_contents((string) $f),
                $files,
            )));
            // One transaction, rolled back: parses/compiles the DDL with zero side effects.
            // Peer auth over the unix socket as OS user `me`, night-test db (RunsPostgresMigrations).
            $cmd = "psql -X -v ON_ERROR_STOP=1 -q -d teleproto_night_test -c 'BEGIN;' -f " . escapeshellarg($combined) . " -c 'ROLLBACK;'";
            exec($cmd . ' 2>&1', $lines, $code);
            self::assertSame(
                0,
                $code,
                'postgres rejected the DDL: ' . implode("\n", array_slice($lines, 0, 12)),
            );
        } finally {
            exec('rm -rf ' . escapeshellarg($out));
        }
    }

    private function loadScheme(): \MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme
    {
        return (new SchemaRegenerator())->loadScheme(self::SOURCES);
    }

    /** @return array<string,string> sorted relative path => sha256 over schema/ddl. */
    private static function ddlHashes(string $out): array
    {
        $hashes = [];
        foreach (glob("{$out}/schema/ddl/*.sql") as $file) {
            $hashes[basename($file)] = hash_file('sha256', $file);
        }
        ksort($hashes);
        return $hashes;
    }
}