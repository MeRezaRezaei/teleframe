<?php

declare(strict_types=1);

/**
 * LIVE GATE — prove the curated NF5 dial actually changes a real MySQL:
 * boot a real Laravel app on a fresh live MySQL db, migrate the full
 * 22-file curated dial, then assert every curated table exists, the
 * Task-8 FK migration applied, the peer pair defaults, and a seeded
 * read-only row round-trips. NOT part of CI; opt-in live proof.
 *
 * Usage: php bin/prove-mysql-curated-dial.php
 * Env:   TELEFRAME_MYSQL_HOST (default 127.0.0.1)
 *        TELEFRAME_MYSQL_PORT (default 3306)
 *        TELEFRAME_MYSQL_USER  (default teleframe_proof)
 *        TELEFRAME_MYSQL_PASS  (default nf5prooflocal)
 *        TELEFRAME_MYSQL_DB    (default teleframe_proof_nf5)
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\Foundation\Application;
use Spatie\LaravelData\LaravelDataServiceProvider;

error_reporting(E_ALL);
require __DIR__.'/../vendor/autoload.php';

$host = getenv('TELEFRAME_MYSQL_HOST') ?: '127.0.0.1';
$port = getenv('TELEFRAME_MYSQL_PORT') ?: '3306';
$user = getenv('TELEFRAME_MYSQL_USER') ?: 'teleframe_proof';
$pass = getenv('TELEFRAME_MYSQL_PASS') ?: 'nf5prooflocal';
$db = getenv('TELEFRAME_MYSQL_DB') ?: 'teleframe_proof_nf5';

$assert = static function (bool $cond, string $msg): void {
    if (! $cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
    echo "  ✓ {$msg}\n";
};

echo "== MySQL live gate: curated dial on {$host}:{$port}/{$db}\n";

// Bootstrap a full Laravel app from the testbench skeleton.
$app = (new Application(
    __DIR__.'/../vendor/orchestra/testbench-core/laravel',
))->createApplication();

// Wire the MySQL connection + provider.
$app['config']->set('database.default', 'mysql');
$app['config']->set('database.connections.mysql', [
    'driver' => 'mysql',
    'host' => $host,
    'port' => (int) $port,
    'database' => $db,
    'username' => $user,
    'password' => $pass,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);
$app->register(LaravelDataServiceProvider::class);
$app->register(TeleframeServiceProvider::class);

// Fresh database each run: drop + recreate. Connect to the system db
// first — a connection whose database no longer exists cannot drop it.
$app['config']->set('database.connections.mysql.database', 'mysql');
DB::purge('mysql');
DB::statement("DROP DATABASE IF EXISTS `{$db}`");
DB::statement("CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "  ✓ fresh database {$db} created (dropped any prior run)\n";
// Swap to a connection whose database already exists.
$app['config']->set('database.connections.mysql.database', $db);
DB::purge('mysql');

// Run the curated dial (migrate --path with realpath).
$migrationDir = dirname(__DIR__).'/src/Laravel/Migrations';
$app->make(Kernel::class)->call('migrate', [
    '--force' => true,
    '--realpath' => true,
    '--path' => $migrationDir,
]);
echo "  ✓ curated dial migrated ({$migrationDir})\n";

// 1. Every curated table exists.
$expected = [
    // 16 curated parents + children.
    'tf_users', 'tf_chats', 'tf_channels', 'tf_dialogs', 'tf_messages',
    'tf_messages_entities', 'tf_messages_media', 'tf_documents', 'tf_photos',
    'tf_web_pages', 'tf_sticker_sets', 'tf_updates', 'tf_channel_participants',
    'tf_stars_transactions', 'tf_bot_infos', 'tf_folders',
    // app-owned.
    'telegram_accounts', 'telegram_apps', 'tg_update_routing', 'tl_user_bindings',
];
foreach ($expected as $table) {
    $assert(DB::getSchemaBuilder()->hasTable($table), "table `{$table}` exists");
}

// 2. Task-8 FK migration was applied.
$fkApplied = DB::table('migrations')->pluck('migration')->contains(
    fn (string $m): bool => str_contains($m, 'create_tf_foreign_keys'),
);
$assert($fkApplied, 'Task-8 FK migration applied (2026_09_14_299999_create_tf_foreign_keys)');

// 3. Curated column shape on a spot-check table: peer pair + defaults.
$peers = DB::select('SHOW COLUMNS FROM `tf_messages` LIKE "peer%"');
$peerMap = [];
foreach ($peers as $col) {
    $peerMap[$col->Field] = ['type' => $col->Type, 'default' => $col->Default];
}
$assert(isset($peerMap['peer_type']), 'tf_messages.peer_type column exists');
$assert(isset($peerMap['peer_id']), 'tf_messages.peer_id column exists');
$assert($peerMap['peer_type']['type'] === 'tinyint', 'peer_type is TINYINT');
$assert((string) $peerMap['peer_id']['default'] === '0', 'peer_id defaults to 0 (curated 0-default semantics)');

// 4. Composite PK (account_id, id).
$pkCols = array_map(
    static fn ($k) => $k->Column_name,
    DB::select('SHOW KEYS FROM `tf_messages` WHERE Key_name = "PRIMARY"'),
);
sort($pkCols);
$assert($pkCols === ['account_id', 'id'], 'tf_messages composite PK (account_id, id)');

// 5. Read-only proof: a seeded account ingests a row and round-trips.
$accountId = DB::table('telegram_accounts')->insertGetId([
    'label' => 'nf5-mysql-proof-account',
    'type' => 'user',
    'user_id' => 1,
    'dc_id' => 2,
    'created_at' => now(),
    'updated_at' => now(),
]);
DB::table('tf_messages')->insert([
    'account_id' => $accountId,
    'id' => 42,
    'peer_type' => 3,
    'peer_id' => -1001234567890, // negative chat id round-trips
    'constructor' => 'message',
    'date' => 1737000000,
    'message' => 'nf5 live-mysql proof row',
]);
$row = DB::table('tf_messages')->where('account_id', $accountId)->where('id', 42)->first();
$assert($row !== null, 'seeded tf_messages row round-trips');
$assert((int) $row->peer_id === -1001234567890, 'negative BIGINT peer_id survives MySQL (signed)');
$assert($row->constructor === 'message', 'constructor column stores the TL name');

// 6. FK clue mechanism actually enforces on MySQL: a child row with an
//    unknown peer parent fires the key constraint (the owner's "FK that
//    fails gives us a clue to the wrong path of ingesting").
try {
    DB::table('tf_messages_media')->insert([
        'account_id' => $accountId,
        'id' => 999,
        'constructor' => 'messageMediaPhoto',
    ]);
    fwrite(STDERR, "FAIL: FK on tf_messages_media→tf_messages did NOT enforce\n");
    exit(1);
} catch (QueryException $e) {
    $msg = (string) $e->getMessage();
    $assert(str_contains($msg, '1452') || str_contains($msg, 'foreign key'), 'orphan child blocked by FK constraint (1452) — clue mechanism live');
}

//    And CASCADE: deleting the parent message removes the entity child.
DB::table('tf_messages')->insert([
    'account_id' => $accountId,
    'id' => 43,
    'peer_type' => 3,
    'peer_id' => -1001234567890,
    'constructor' => 'message',
    'date' => 1737000000,
    'message' => 'nf5 cascade probe',
]);
DB::table('tf_messages_entities')->insert([
    'account_id' => $accountId,
    'id' => 43,
    'position' => 0,
    'constructor' => 'messageEntityBold',
    'offset' => 0,
    'length' => 4,
]);
DB::table('tf_messages')->where('account_id', $accountId)->where('id', 43)->delete();
$orphan = DB::table('tf_messages_entities')->where('account_id', $accountId)->where('id', 43)->exists();
$assert(! $orphan, 'CASCADE delete removed child rows (messages_entities)');

// 7. migrate:status view = all applied, none pending.
$migrator = $app->make('migrator');
$ran = $migrator->getRepository()->getRan();
$files = array_keys($migrator->getMigrationFiles($migrationDir));
$assert(count(array_diff($files, $ran)) === 0, 'migrate:status clean — all 22 curated files ran, 0 pending');
echo '  … ran = '.count($ran).' migrations; pending = '.count(array_diff($files, $ran))."\n";

echo "\n== MYSQL LIVE GATE PASSED ==\n";
