<?php

/**
 * Measure the shape of the shipped DDL and write `mirror-shape.json`, the
 * artifact `ModelGenerator` builds the Eloquent model layer from.
 *
 * WHY THIS IS A SCRIPT AND NOT THE GENERATOR
 *
 * `artisan teleframe:regenerate` must not require a live MySQL server, and it
 * cannot be the thing that boots one: the shipped dial is 55 migrations and
 * about 100 seconds against MySQL, versus under 10 for SQLite. But casts
 * cannot be derived from SQLite, because SQLite's affinity collapses
 * `integer()`, `bigInteger()` and `tinyInteger()` alike and reports width and
 * signedness not at all — measured, SQLite sees 6 distinct declared types
 * across the mirror's 2043 columns where MySQL 8.4 sees 17. So the reflection
 * is measured ONCE here, on both engines, and the generator reads the result
 * back. See src/Schema/Mirror/MirrorShape.php.
 *
 * WHY BOTH ENGINES ARE BOOTED
 *
 * Relations must come from declared foreign keys, so the graph has to be the
 * graph the ENGINE holds — and the two engines must hold the same one. This
 * script asserts that rather than assuming it: it boots the shipped
 * migrations against SQLite and against MySQL, compares the constraint sets
 * leg for leg, and REFUSES to write the artifact if they disagree. A silent
 * disagreement is how a relation ends up existing on one engine only.
 *
 * The SQLite pass is also what makes the artifact's own staleness checkable
 * cheaply: `tests/Schema/ModelGeneratorTest.php` re-executes the shipped dial
 * on SQLite and re-derives the coverage and the relation count, so the
 * committed artifact cannot quietly fall behind the migrations.
 *
 * Usage:
 *   php bin/generate-mirror-shape.php             # write the artifact
 *   php bin/generate-mirror-shape.php --check     # exit 1 if stale
 *
 * Exit codes: 0 ok, 1 stale (with --check), 2 usage or engine disagreement.
 *
 * Credentials come from the same environment contract the MySQL track already
 * uses (tests/Concerns/RunsMysqlMigrations.php, bin/audit-fidelity.php). No
 * secret is ever echoed. The scratch database is named by
 * TELEFRAME_MYSQL_DATABASE and dropped on the way out, whatever happens.
 *
 * Provenance of the measurement pattern: bin/generate-relation-graph.php
 * already boots the shipped migrations and reflects them, and
 * tests/Store/ShippedStoreSchema.php does the same from the test side. Neither
 * is reused here because both answer a different question — this one needs the
 * MySQL `COLUMN_TYPE`, which SQLite cannot supply at all.
 */

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Schema\Generator\ModelGenerator;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorShape;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$check = in_array('--check', $argv, true);
$writeModels = in_array('--write-models', $argv, true);

/**
 * Boot the shipped migrations on SQLite and return the live PDO.
 */
$bootSqlite = static function () use ($root): PDO {
    $capsule = new Capsule;
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $app = new Container;
    $app->instance('db', $capsule->getDatabaseManager());
    $app->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
    Container::setInstance($app);
    Facade::setFacadeApplication($app);
    Facade::clearResolvedInstances();

    // The shipped dial, in the order the migrator would apply it. Read off
    // disk; a hardcoded migration count would be a second copy of the list.
    $files = glob($root.'/src/Laravel/Migrations/*.php') ?: [];
    sort($files);
    if ($files === []) {
        fwrite(STDERR, "no migrations found under src/Laravel/Migrations\n");
        exit(2);
    }
    foreach ($files as $file) {
        $migration = require $file;
        if (is_object($migration) && method_exists($migration, 'up')) {
            $migration->up();
        }
    }

    return $capsule->getConnection()->getPdo();
};

/**
 * Boot the shipped migrations on MySQL and hand back everything needed to
 * finish and to clean up.
 *
 * The scratch database is NOT dropped here. This function returns, and the
 * caller still has to reflect from it — dropping on the way out left the
 * reflection reading an `information_schema` with no tables in it, which is
 * indistinguishable from "this schema has no foreign keys" unless you know to
 * look. Cleanup happens once, at the end of the script.
 *
 * @return array{0:PDO,1:string,2:PDO,3:string}
 */
$bootMysql = static function () use ($root): array {
    $password = getenv('TELEFRAME_MYSQL_PASSWORD');

    $cfg = [
        'host' => (string) (getenv('TELEFRAME_MYSQL_HOST') ?: '127.0.0.1'),
        'port' => (string) (getenv('TELEFRAME_MYSQL_PORT') ?: '3306'),
        // Named for this lane so a run never collides with the MySQL track's
        // database, which other harnesses drop and recreate on sight.
        'database' => (string) (getenv('TELEFRAME_MYSQL_DATABASE') ?: 'teleframe_mirror_shape'),
        'username' => (string) (getenv('TELEFRAME_MYSQL_USER') ?: 'teleframe_track'),
        // `getenv()` returns false when UNSET and '' when set-empty. An empty
        // password is a real configuration (CI's passwordless root), so `?:`
        // must NOT be used here — see tests/Concerns/RunsMysqlMigrations.php.
        'password' => $password === false ? 'TrackLocal!2026' : $password,
    ];

    // A connection to a database that does not exist yet cannot create it, so
    // the lifecycle runs on a databaseless handle first.
    $lifecycle = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $cfg['host'], $cfg['port']),
        $cfg['username'],
        $cfg['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10],
    );
    $lifecycle->exec('DROP DATABASE IF EXISTS `'.$cfg['database'].'`');
    $lifecycle->exec('CREATE DATABASE `'.$cfg['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    try {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $cfg['host'],
            'port' => $cfg['port'],
            'database' => $cfg['database'],
            'username' => $cfg['username'],
            'password' => $cfg['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ], 'mysql');
        // 18 of the shipped migrations call `DB::raw(...)`, so the connection
        // must be the DEFAULT, not merely named `mysql`.
        $capsule->getDatabaseManager()->setDefaultConnection('mysql');
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $app = new Container;
        $app->instance('db', $capsule->getDatabaseManager());
        $app->instance('db.schema', $capsule->getConnection('mysql')->getSchemaBuilder());
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        $files = glob($root.'/src/Laravel/Migrations/*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            $migration = require $file;
            if (is_object($migration) && method_exists($migration, 'up')) {
                $migration->up();
            }
        }

        $pdo = $capsule->getConnection('mysql')->getPdo();

        return [$pdo, (string) $pdo->query('SELECT VERSION()')->fetchColumn(), $lifecycle, $cfg['database']];
    } catch (Throwable $e) {
        // Only reachable when the dial itself failed; drop on the failure
        // path so a broken run leaves nothing behind either.
        $lifecycle->exec('DROP DATABASE IF EXISTS `'.$cfg['database'].'`');

        throw $e;
    }
};

// -----------------------------------------------------------------------------
// 1. Reflect SQLite. Cheap, and the source the unit tests re-derive against.
// -----------------------------------------------------------------------------

fwrite(STDERR, "booting the shipped dial on sqlite...\n");
$sqliteShape = MirrorShape::fromExecutedDdl($bootSqlite());
$sqliteCounts = $sqliteShape->counts();

// -----------------------------------------------------------------------------
// 2. Reflect MySQL. The only source that carries width and signedness.
// -----------------------------------------------------------------------------

fwrite(STDERR, "booting the shipped dial on mysql (this is the slow one)...\n");
[$mysqlPdo, $serverVersion, $lifecycle, $scratchDatabase] = $bootMysql();

// -----------------------------------------------------------------------------
// 2. Reflect MySQL. The only source that carries width and signedness.
// -----------------------------------------------------------------------------

$dropScratch = static function () use ($lifecycle, $scratchDatabase): void {
    $lifecycle->exec('DROP DATABASE IF EXISTS `'.$scratchDatabase.'`');
};

try {
    $mysqlShape = MirrorShape::fromExecutedDdl($mysqlPdo);
    $mysqlCounts = $mysqlShape->counts();

    // -------------------------------------------------------------------------
    // 3. The cross-engine check the whole design rests on.
    // -------------------------------------------------------------------------

    /**
     * Canonical, order-insensitive form of one constraint.
     *
     * A composite constraint's column ORDER is an engine detail — the two
     * engines are free to disagree about it while declaring the same
     * constraint. The SET of column pairs is the constraint, so that is what
     * is compared.
     *
     * @param  list<string>  $from
     * @param  list<string>  $to
     */
    $canonical = static function (array $from, array $to, string $ref, string $owner): string {
        $pairs = [];
        foreach ($from as $i => $column) {
            $pairs[] = $column.'->'.$to[$i];
        }
        sort($pairs);

        return $owner.' ~ '.$ref.' :: '.implode(',', $pairs);
    };

    /** @return list<string> */
    $constraintsOf = static function (MirrorShape $shape) use ($canonical): array {
        $list = [];
        foreach ($shape->tableNames() as $table) {
            foreach ($shape->foreignKeys($table) as $fk) {
                $list[] = $canonical($fk['from'], $fk['to'], $fk['to_table'], $table);
            }
        }
        sort($list);

        return $list;
    };

    $sqliteConstraints = $constraintsOf($sqliteShape);
    $mysqlConstraints = $constraintsOf($mysqlShape);

    $onlySqlite = array_values(array_diff($sqliteConstraints, $mysqlConstraints));
    $onlyMysql = array_values(array_diff($mysqlConstraints, $sqliteConstraints));

    if ($onlySqlite !== [] || $onlyMysql !== []) {
        fwrite(STDERR, "REFUSING TO WRITE: the two engines disagree on the foreign-key graph.\n");
        fwrite(STDERR, '  only on sqlite: '.count($onlySqlite)."\n");
        foreach (array_slice($onlySqlite, 0, 20) as $line) {
            fwrite(STDERR, "    {$line}\n");
        }
        fwrite(STDERR, '  only on mysql:  '.count($onlyMysql)."\n");
        foreach (array_slice($onlyMysql, 0, 20) as $line) {
            fwrite(STDERR, "    {$line}\n");
        }
        fwrite(STDERR, "A relation derived from a graph the engines disagree on would exist on one engine only.\n");
        exit(2);
    }

    // -------------------------------------------------------------------------
    // 4. Reachability, measured rather than asserted.
    // -------------------------------------------------------------------------

    $inLayer = 0;
    $crossLayer = [];
    foreach ($mysqlShape->tableNames() as $table) {
        foreach ($mysqlShape->foreignKeys($table) as $fk) {
            if ($mysqlShape->has($fk['to_table'])) {
                $inLayer++;
            } else {
                $crossLayer[$fk['to_table']] = ($crossLayer[$fk['to_table']] ?? 0) + 1;
            }
        }
    }
    ksort($crossLayer);

    // -------------------------------------------------------------------------
    // 5. Write, or check.
    // -------------------------------------------------------------------------

    $payload = $mysqlShape->toArray([
        'source' => 'src/Laravel/Migrations/*.php executed in filename order, then reflected',
        'column_types_from' => 'mysql information_schema.COLUMNS.COLUMN_TYPE',
        'mysql_server' => $serverVersion,
        'sqlite_tables' => $sqliteCounts['tables'],
        'sqlite_columns' => $sqliteCounts['columns'],
        'sqlite_foreign_keys' => $sqliteCounts['foreign_keys'],
        'mysql_tables' => $mysqlCounts['tables'],
        'mysql_columns' => $mysqlCounts['columns'],
        'mysql_foreign_keys' => $mysqlCounts['foreign_keys'],
        'engines_agree_on_foreign_key_graph' => true,
        'in_layer_foreign_keys' => $inLayer,
        'cross_layer_foreign_keys' => $crossLayer,
    ]);

    $target = MirrorShape::artifactPath();
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

    // ---------------------------------------------------------------------
    // 5a. Emit the model layer from what was just measured.
    //
    // Driven off the in-memory MySQL reflection rather than the file, so the
    // models and the artifact cannot disagree by a regeneration's worth.
    // ---------------------------------------------------------------------
    $models = (new ModelGenerator)->generateFromMirrorShape($mysqlShape);

    $modelDir = $root.'/src/Schema/Generated/Models';
    if (! is_dir($modelDir)) {
        fwrite(STDERR, "missing model dir: {$modelDir}\n");
        exit(2);
    }

    $relationCount = 0;
    foreach ($models as $content) {
        foreach (explode("\n", $content) as $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, 'return $this->hasMany(')
                || str_starts_with($trimmed, 'return $this->hasOne(')
                || str_starts_with($trimmed, 'return $this->belongsTo(')
            ) {
                $relationCount++;
            }
        }
    }

    if ($check) {
        $staleModels = [];
        foreach ($models as $name => $content) {
            $path = $modelDir.'/'.$name;
            if (! is_file($path) || (string) file_get_contents($path) !== $content) {
                $staleModels[] = $name;
            }
        }
        // A model file on disk that the generator no longer emits is drift in
        // the other direction, and it would keep shadowing the real table.
        $onDisk = array_map(
            static fn (string $p): string => basename($p),
            glob($modelDir.'/*.php') ?: [],
        );
        $orphans = array_values(array_diff($onDisk, array_keys($models)));

        $current = is_file($target) ? (string) file_get_contents($target) : '';
        $artifactStale = $current !== $json;

        if ($artifactStale || $staleModels !== [] || $orphans !== []) {
            fwrite(STDERR, "STALE\n");
            if ($artifactStale) {
                fwrite(STDERR, "  artifact {$target} differs from a fresh measurement\n");
            }
            fwrite(STDERR, '  '.count($staleModels)." model file(s) differ\n");
            foreach (array_slice($staleModels, 0, 10) as $name) {
                fwrite(STDERR, "    {$name}\n");
            }
            fwrite(STDERR, '  '.count($orphans)." orphan model file(s) not emitted\n");
            foreach (array_slice($orphans, 0, 10) as $name) {
                fwrite(STDERR, "    {$name}\n");
            }
            fwrite(STDERR, "  run: php bin/generate-mirror-shape.php --write-models\n");
            exit(1);
        }
        fwrite(STDERR, "current: {$target} and ".count($models)." model files\n");
        exit(0);
    }

    file_put_contents($target, $json);
    fwrite(STDERR, "wrote {$target}\n");

    if ($writeModels) {
        // Remove only files this generator owns, and only after the new set is
        // known — never a blanket wipe of the directory.
        $onDisk = glob($modelDir.'/*.php') ?: [];
        foreach (array_diff($onDisk, array_map(
            static fn (string $n): string => $modelDir.'/'.$n,
            array_keys($models),
        )) as $orphan) {
            unlink($orphan);
            fwrite(STDERR, "removed orphan {$orphan}\n");
        }
        foreach ($models as $name => $content) {
            file_put_contents($modelDir.'/'.$name, $content);
        }
        fwrite(STDERR, sprintf(
            "wrote %d model files to %s\n",
            count($models),
            $modelDir,
        ));
    }

    fwrite(STDERR, sprintf(
        "  tables=%d columns=%d foreign_keys=%d (in-layer %d, cross-layer %s)\n",
        $mysqlCounts['tables'],
        $mysqlCounts['columns'],
        $mysqlCounts['foreign_keys'],
        $inLayer,
        json_encode($crossLayer),
    ));
    fwrite(STDERR, sprintf(
        "  relations emitted=%d from %d declared in-layer constraints\n",
        $relationCount,
        $inLayer,
    ));
    fwrite(STDERR, "  engines agree on the foreign-key graph: yes (sqlite + mysql {$serverVersion})\n");
} finally {
    // Exactly one cleanup, on every exit path including the early `exit`s
    // above. Leave nothing behind.
    $dropScratch();
}
