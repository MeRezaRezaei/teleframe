<?php

/**
 * FIDELITY AUDIT — the shipped relational schema against Telegram's own data
 * model, with no unexplained divergence.
 *
 * =====================================================================
 * WHY THIS TOOL EXISTS
 * =====================================================================
 *
 * Everything measured so far in this repository measured OUR schema against
 * ITSELF. `MirrorRelationPlan` compares tables to tables.
 * `EveryFkableRelationHasAConstraintTest` asks whether a declared relation HAS
 * a constraint, never whether the relation is what Telegram actually says. The
 * anti-rot gate is green at "0 gaps", and that number says nothing whatever
 * about fidelity to the source.
 *
 * That blind spot produced three real defects in a single session. Not one of
 * the three is a database-design opinion; all three are infidelity:
 *
 *   1. `tf_messages_service -> tf_messages` — a foreign key invented by a
 *      NAME-PREFIX match between two tables Telegram stores as SIBLINGS. The
 *      committed catalog's ctor->table routing states the truth; the heuristic
 *      never read it.
 *   2. `tf_wallpapers.id` `integer` against `tf_wallpapers_settings.id`
 *      `bigInteger()` — fourteen lines apart in one shipped file, both legs
 *      signed. MySQL refused the whole migrate with 3780.
 *   3. `updates.difference` / `updates.channelDifference` fetching a
 *      `Vector<Update>` and DISCARDING it, because a hardcoded payload read did
 *      not match the difference family's `other_updates`.
 *
 * This tool is the one that can see that class of defect.
 *
 * =====================================================================
 * THE TWO AUTHORITIES, AND WHAT IS NOT ONE
 * =====================================================================
 *
 * TELEGRAM SIDE — read, never transcribed:
 *   - `schema/sources/TL_telegram_v227.tl` via `TlParser::parseFile()`. Its
 *     types, key shapes, nullability and cardinality are the REQUIREMENT.
 *   - `src/Schema/schema/mirror-catalog.json` via `MirrorCatalog::load()`. This
 *     is the authority for which table a wire constructor belongs to, and
 *     therefore for parent/child and SIBLING relationships. Where a key-shape
 *     or name-prefix heuristic disagrees with the catalog, the catalog wins and
 *     the heuristic is the defect. That is defect (1) above, stated as a rule.
 *
 * OUR SIDE — the EXECUTED schema, never a migration docblock:
 *   - `sqlite` : `sqlite_master` + `pragma_table_xinfo` / `pragma_index_list` /
 *     `pragma_foreign_key_list`.
 *   - `mysql`  : `information_schema.COLUMNS` / `.STATISTICS` /
 *     `.KEY_COLUMN_USAGE` + `.REFERENTIAL_CONSTRAINTS`.
 *   A docblock and the engine can disagree, and this session proved they do.
 *
 * A THIRD authority, and it is external: real-world relational database-design
 * fact. It is the tie-breaker wherever the wire shape is not directly storable
 * (a relational store has no array type; MySQL cannot index a BLOB without a
 * prefix length). It is NEVER our preference. "It is more convenient" is not a
 * fact and is not accepted anywhere below.
 *
 * =====================================================================
 * THE VERDICTS — three, and there is no fourth
 * =====================================================================
 *
 *   COPYING-GAP         we differ from Telegram and there is no storage reason.
 *                       THIS IS A DEFECT AND IT IS THE DELIVERABLE.
 *   STORAGE-TRANSLATION we differ, and a named real-world database-design fact
 *                       REQUIRES the difference. The fact is printed with the
 *                       row, so a reader can reject it.
 *   DELIBERATE          we differ on purpose and the reason is recorded in the
 *                       file that does it. Detected by a declared marker, never
 *                       assumed.
 *   UNEXPLAINED         the residual bucket: a divergence no rule resolved and
 *                       no declaration covers. This is the ONLY thing that can
 *                       make the tool exit non-zero.
 *
 * "acceptable" and "minor" are not verdicts here. If a rule cannot name the
 * fact, the row is UNEXPLAINED by construction.
 *
 * =====================================================================
 * ENGINE BLINDNESS, STATED RATHER THAN PAPERED OVER
 * =====================================================================
 *
 * SQLite reports the DECLARED type word and models neither integer width nor
 * signedness: it stores any integer in any integer-affinity column and refuses
 * no constraint for disagreeing legs. So on SQLite the key-width/signedness
 * sweep CANNOT produce a verdict, and this tool says "not measurable on this
 * engine" instead of reporting a match it did not measure. That blindness is
 * precisely where infidelity (2) hid, so `--engine=mysql` is a first-class
 * mode and the MySQL `information_schema` read is required, not optional.
 *
 * =====================================================================
 * USAGE
 * =====================================================================
 *
 *   php bin/audit-fidelity.php                          # sqlite, write the report
 *   php bin/audit-fidelity.php --engine=mysql           # the verdict-capable run
 *   php bin/audit-fidelity.php --format=json --no-write # machine output
 *   php bin/audit-fidelity.php --check                  # exit 1 if the report is stale
 *   php bin/audit-fidelity.php --scope=C1 --inject=type:tf_messages_entities.peer_type:int32
 *
 * Exit codes: 0 no unexplained divergence in scope, 1 at least one,
 *             2 usage or boot failure.
 *
 * Provenance of the boot: `bin/generate-relation-graph.php` (SQLite Capsule)
 * and `tests/MySql/MySqlTestCase.php` (MySQL Capsule). Both are "measure, never
 * transcribe" tools already in this repository, and this one follows the same
 * discipline rather than inventing a third way to read a schema.
 *
 * Credentials: `getenv()` only. This tool NEVER reads `.env`, never prints a
 * session value, an access_hash or a database password, and never contacts
 * Telegram.
 */

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlType;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumn;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFieldDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableEntry;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

const EXIT_CLEAN = 0;

const EXIT_DIVERGENCE = 1;

const EXIT_USAGE = 2;

// =====================================================================
// 0. Options
// =====================================================================

/** @return array{engine:string, scope:list<string>, format:string, write:bool, check:bool, inject:list<array{kind:string,table:string,column:string,value:string}>} */
function parseOptions(array $argv): array
{
    $engine = 'sqlite';
    $scope = [];
    $format = 'text';
    $write = true;
    $check = false;
    $inject = [];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            fwrite(STDOUT, "usage: php bin/audit-fidelity.php [--engine=sqlite|mysql] [--scope=ID,ID]\n"
                ."       [--format=text|json] [--no-write|--check]\n"
                ."       [--inject=type:<table>.<column>:<canonical-type>]\n");
            exit(EXIT_USAGE);
        }
        if (str_starts_with($arg, '--engine=')) {
            $engine = substr($arg, 9);

            continue;
        }
        if (str_starts_with($arg, '--scope=')) {
            foreach (explode(',', substr($arg, 8)) as $id) {
                if (trim($id) !== '') {
                    $scope[] = trim($id);
                }
            }

            continue;
        }
        if (str_starts_with($arg, '--format=')) {
            $format = substr($arg, 9);

            continue;
        }
        if ($arg === '--no-write') {
            $write = false;

            continue;
        }
        if ($arg === '--check') {
            $check = true;
            $write = false;

            continue;
        }
        if (str_starts_with($arg, '--inject=')) {
            $spec = substr($arg, 9);
            $colon = strpos($spec, ':');
            $second = $colon === false ? false : strpos($spec, ':', $colon + 1);
            if ($colon === false || $second === false) {
                fwrite(STDERR, "--inject wants type:<table>.<column>:<canonical>, got '{$spec}'\n");
                exit(EXIT_USAGE);
            }
            $kind = substr($spec, 0, $colon);
            $target = substr($spec, $colon + 1, $second - $colon - 1);
            $value = substr($spec, $second + 1);
            $dot = strpos($target, '.');
            if ($kind !== 'type' || $dot === false) {
                fwrite(STDERR, "--inject supports only type:<table>.<column>:<canonical>\n");
                exit(EXIT_USAGE);
            }
            $inject[] = [
                'kind' => $kind,
                'table' => substr($target, 0, $dot),
                'column' => substr($target, $dot + 1),
                'value' => $value,
            ];

            continue;
        }
        fwrite(STDERR, "unknown option: {$arg}\n");
        exit(EXIT_USAGE);
    }

    if ($engine !== 'sqlite' && $engine !== 'mysql') {
        fwrite(STDERR, "--engine must be sqlite or mysql, got '{$engine}'\n");
        exit(EXIT_USAGE);
    }
    if ($format !== 'text' && $format !== 'json') {
        fwrite(STDERR, "--format must be text or json, got '{$format}'\n");
        exit(EXIT_USAGE);
    }

    return ['engine' => $engine, 'scope' => $scope, 'format' => $format, 'write' => $write, 'check' => $check, 'inject' => $inject];
}

$options = parseOptions($argv);

// =====================================================================
// 1. Canonical column type — the vocabulary every verdict is spoken in
// =====================================================================

/**
 * A column type reduced to what a database engine actually ENFORCES.
 *
 * `family` is what the value is; `width` and `signed` are what the engine will
 * refuse a constraint over. `width`/`signed` stay null when the engine does not
 * model them, because a null there means "unmeasurable", never "matches".
 *
 * @return array{family:string,width:int|null,signed:bool|null,raw:string,display:string}
 */
function canonicalType(string $raw, string $engine): array
{
    $raw = trim($raw);
    $lower = strtolower($raw);
    $signed = null;
    $width = null;

    if ($engine === 'mysql') {
        if (str_contains($lower, ' unsigned')) {
            $signed = false;
        } elseif (str_contains($lower, ' signed') || true) {
            // MySQL columns are signed unless declared unsigned. `information_schema`
            // spells the width out, so absence of the keyword IS the signed case.
            $signed = true;
        }
    }

    // Split "decimal(10,0)" / "bigint(20) unsigned" into a head and a paren body.
    $head = $lower;
    $paren = '';
    $open = strpos($lower, '(');
    if ($open !== false) {
        $close = strpos($lower, ')', $open);
        if ($close !== false) {
            $head = trim(substr($lower, 0, $open));
            $paren = substr($lower, $open + 1, $close - $open - 1);
        }
    }
    if (str_contains($head, ' ')) {
        $head = trim(strtok($head, ' '));
    }

    $intWidths = [
        'tinyint' => 8, 'smallint' => 16, 'mediumint' => 24, 'int' => 32, 'integer' => 32,
        'bigint' => 64, 'int2' => 16, 'int8' => 8, 'int4' => 32, 'int8unsigned' => 8,
        'bigintunsigned' => 64, 'tinyintunsigned' => 8, 'smallintunsigned' => 16,
        'mediumintunsigned' => 24, 'intunsigned' => 32,
    ];

    if (isset($intWidths[$head])) {
        $family = 'int';
        if ($engine === 'mysql') {
            $width = $intWidths[$head];
        } elseif ($head === 'tinyint') {
            // SQLite has no TINYINT. Laravel's `tinyInteger()` declares the word
            // and nothing enforces it, so the width stays unmeasurable.
            $width = null;
        }

        return ['family' => $family, 'width' => $width, 'signed' => $signed, 'raw' => $raw, 'display' => $head];
    }

    $families = [
        'decimal' => 'decimal', 'numeric' => 'decimal',
        'float' => 'float', 'double' => 'float', 'real' => 'float', 'double precision' => 'float',
        'char' => 'text', 'varchar' => 'text', 'text' => 'text', 'tinytext' => 'text',
        'mediumtext' => 'text', 'longtext' => 'text', 'clob' => 'text', 'string' => 'text',
        'blob' => 'blob', 'tinyblob' => 'blob', 'mediumblob' => 'blob', 'longblob' => 'blob',
        'binary' => 'blob', 'varbinary' => 'blob',
        'bit' => 'bit', 'bool' => 'bit', 'boolean' => 'bit',
        'json' => 'json',
        'date' => 'datetime', 'datetime' => 'datetime', 'timestamp' => 'datetime', 'time' => 'datetime',
        'enum' => 'enum', 'set' => 'enum', 'uuid' => 'text',
    ];

    $family = $families[$head] ?? 'other';

    return [
        'family' => $family,
        'width' => $family === 'decimal' && $paren !== '' ? (int) explode(',', $paren)[0] : null,
        'signed' => $signed,
        'raw' => $raw,
        'display' => $raw,
    ];
}

/** The canonical spelling a `--inject` value is parsed with. */
function canonicalFromToken(string $token): array
{
    $token = strtolower(trim($token));
    $map = ['int8' => 'tinyint', 'int16' => 'smallint', 'int32' => 'int', 'int64' => 'bigint'];

    return canonicalType($map[$token] ?? $token, 'mysql');
}

function showType(array $t): string
{
    if ($t['width'] === null) {
        return $t['display'];
    }

    return $t['display'].' ('.$t['width'].($t['signed'] === false ? ' unsigned' : '').')';
}

// =====================================================================
// 2. Boot the EXECUTED shipped dial
// =====================================================================

/** MySQL connection config. `getenv()` ONLY — this tool never reads `.env`. */
function mysqlConfig(): array
{
    $password = getenv('TELEFRAME_MYSQL_PASSWORD');

    return [
        'host' => (string) (getenv('TELEFRAME_MYSQL_HOST') ?: '127.0.0.1'),
        'port' => (string) (getenv('TELEFRAME_MYSQL_PORT') ?: '3306'),
        'database' => (string) (getenv('TELEFRAME_MYSQL_DATABASE') ?: 'teleframe_fidelity'),
        'username' => (string) (getenv('TELEFRAME_MYSQL_USER') ?: 'teleframe_track'),
        // `getenv()` returns false when UNSET and '' when set-empty. An empty
        // password is a real, deliberate configuration (CI's root has none), so
        // `?:` must NOT be used here — see RunsMysqlMigrations.php:77-101 for
        // the 56-error production run that collapsing the two produced.
        'password' => $password === false ? 'TrackLocal!2026' : $password,
    ];
}

/**
 * Execute the shipped dial and hand back the live connection.
 *
 * SQLite is `:memory:` and disappears with the process, which is what an audit
 * wants: it measures a schema it built itself and leaves nothing behind.
 */
function bootDialect(string $engine): SQLiteConnection|MySqlConnection
{
    $capsule = new Capsule;

    if ($engine === 'sqlite') {
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    } else {
        $cfg = mysqlConfig();
        // A connection whose database does not exist yet cannot create it, so the
        // lifecycle runs on a databaseless handle first. NEVER reuse another
        // lane's database: this one is named by TELEFRAME_MYSQL_DATABASE and
        // defaults to a lane of its own.
        $lifecycle = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $cfg['host'], $cfg['port']),
            $cfg['username'],
            $cfg['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
        );
        $lifecycle->exec('DROP DATABASE IF EXISTS `'.$cfg['database'].'`');
        $lifecycle->exec('CREATE DATABASE `'.$cfg['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

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
        $capsule->getDatabaseManager()->setDefaultConnection('mysql');
    }

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $app = new Container;
    $app->instance('db', $capsule->getDatabaseManager());
    $app->instance('db.schema', ($engine === 'sqlite'
        ? $capsule->getConnection()
        : $capsule->getConnection('mysql'))->getSchemaBuilder());
    Container::setInstance($app);
    Facade::setFacadeApplication($app);
    Facade::clearResolvedInstances();

    // The shipped dial, in the order the migrator would apply it. Read off disk,
    // never from a pinned count — a hardcoded migration count is a second copy.
    // `$root` is script-scope, and a function cannot see it: the dialect dir is
    // derived from __DIR__ here exactly as it is at the top of the file.
    $files = glob(dirname(__DIR__).'/src/Laravel/Migrations/*.php') ?: [];
    sort($files);
    foreach ($files as $file) {
        $migration = require $file;
        if (is_object($migration) && method_exists($migration, 'up')) {
            $migration->up();
        }
    }

    return $engine === 'sqlite' ? $capsule->getConnection() : $capsule->getConnection('mysql');
}

/**
 * Reflect the live schema. One bulk query per concern per engine — never a
 * per-table round trip, and never a table list typed out by hand.
 *
 * @return array{
 *   tables: list<string>,
 *   columns: array<string, array<string, array{family:string,width:int|null,signed:bool|null,raw:string,display:string,nullable:bool,default:mixed,extra:string,key:string}>>,
 *   indexes: array<string, list<array{name:string,unique:bool,columns:list<string>}>>,
 *   foreignKeys: list<array{name:string,child:string,parent:string,columns:list<string>,refColumns:list<string>,onDelete:string,onUpdate:string}>
 * }
 */
function reflectSchema(SQLiteConnection|MySqlConnection $db, string $engine): array
{
    $tables = [];
    $columns = [];
    $indexes = [];
    $fks = [];

    $rows = static fn (string $sql, array $bindings = []): array => array_map(
        static fn ($r): array => is_object($r) ? (array) $r : $r,
        $db->select($sql, $bindings),
    );

    if ($engine === 'mysql') {
        foreach ($rows(
            'SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME'
        ) as $r) {
            $tables[] = (string) $r['t'];
        }
        foreach ($rows(
            'SELECT TABLE_NAME AS t, COLUMN_NAME AS c, DATA_TYPE AS d, COLUMN_TYPE AS ty, IS_NULLABLE AS nl,'
            .' COLUMN_DEFAULT AS def, EXTRA AS ex, COLUMN_KEY AS ck'
            .' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()'
            .' ORDER BY TABLE_NAME, ORDINAL_POSITION'
        ) as $r) {
            $t = (string) $r['t'];
            $canon = canonicalType((string) $r['ty'], 'mysql');
            $columns[$t][(string) $r['c']] = [
                'family' => $canon['family'],
                'width' => $canon['width'],
                'signed' => $canon['signed'],
                'raw' => $canon['raw'],
                'display' => $canon['display'],
                'nullable' => strtoupper((string) $r['nl']) === 'YES',
                'default' => $r['def'],
                'extra' => (string) $r['ex'],
                'key' => (string) $r['ck'],
            ];
        }
        foreach ($rows(
            'SELECT TABLE_NAME AS t, INDEX_NAME AS i, NON_UNIQUE AS nu,'
            .' GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols'
            .' FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()'
            .' GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE'
        ) as $r) {
            $cols = array_values(array_filter(explode(',', (string) $r['cols']), static fn ($c) => $c !== ''));
            $indexes[(string) $r['t']][] = [
                'name' => (string) $r['i'],
                'unique' => (int) $r['nu'] === 0,
                'columns' => $cols,
                // MySQL states the key in the NAME (`PRIMARY`); SQLite states it in
                // `origin` (`pk`). Both facts are recorded so the one primary-key
                // reader below is engine-correct without a second code path.
                'origin' => strtoupper((string) $r['i']) === 'PRIMARY' ? 'pk' : '',
            ];
        }
        $legs = [];
        foreach ($rows(
            'SELECT k.CONSTRAINT_NAME AS cn, k.TABLE_NAME AS t, k.COLUMN_NAME AS c, k.ORDINAL_POSITION AS pos,'
            .' k.REFERENCED_TABLE_NAME AS rt, k.REFERENCED_COLUMN_NAME AS rc'
            .' FROM information_schema.KEY_COLUMN_USAGE k'
            .' WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL'
            .' ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION'
        ) as $r) {
            $key = $r['t'].'/'.$r['cn'];
            $legs[$key]['name'] = (string) $r['cn'];
            $legs[$key]['child'] = (string) $r['t'];
            $legs[$key]['parent'] = (string) $r['rt'];
            $legs[$key]['columns'][(int) $r['pos']] = (string) $r['c'];
            $legs[$key]['refColumns'][(int) $r['pos']] = (string) $r['rc'];
        }
        foreach ($rows(
            'SELECT CONSTRAINT_NAME AS cn, TABLE_NAME AS t, DELETE_RULE AS d, UPDATE_RULE AS u'
            .' FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()'
        ) as $r) {
            $key = $r['t'].'/'.$r['cn'];
            if (isset($legs[$key])) {
                $legs[$key]['onDelete'] = (string) $r['d'];
                $legs[$key]['onUpdate'] = (string) $r['u'];
            }
        }
    } else {
        foreach ($rows(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        ) as $r) {
            $tables[] = (string) $r['name'];
        }
        foreach ($tables as $t) {
            $quoted = "'".str_replace("'", "''", $t)."'";
            foreach ($rows("PRAGMA table_info({$quoted})") as $r) {
                $canon = canonicalType((string) $r['type'], 'sqlite');
                $columns[$t][(string) $r['name']] = [
                    'family' => $canon['family'],
                    'width' => $canon['width'],
                    'signed' => $canon['signed'],
                    'raw' => $canon['raw'],
                    'display' => $canon['display'],
                    'nullable' => (int) $r['notnull'] === 0 && (int) $r['dflt_value'] === 0 ? false : ((int) $r['notnull'] === 0),
                    'default' => $r['dflt_value'],
                    'extra' => '',
                    'key' => (int) $r['pk'] > 0 ? 'PRI' : '',
                ];
            }
            foreach ($rows("PRAGMA index_list({$quoted})") as $r) {
                $cols = [];
                foreach ($rows("PRAGMA index_info('".str_replace("'", "''", (string) $r['name'])."')") as $ic) {
                    if ($ic['name'] !== null) {
                        $cols[] = (string) $ic['name'];
                    }
                }
                $indexes[$t][] = [
                    'name' => (string) $r['name'],
                    'unique' => (int) $r['unique'] === 1,
                    'columns' => $cols,
                    // MEASURED, not assumed: SQLite never names a PRIMARY KEY index
                    // `primary` on an ordinary rowid table — it names it
                    // `sqlite_autoindex_<table>_<n>` and reports `origin='pk'`. A
                    // reader that looks only for the name `primary` finds NOTHING on
                    // this engine (measured: 260 tables with a pk index, 0 named
                    // `primary`), which silently empties the primary-key map and
                    // makes every key verdict vacuous rather than wrong. The origin
                    // column is what actually distinguishes the key from a plain
                    // unique index, so it is what is read.
                    'origin' => (string) (($r['origin'] ?? '') ?: ''),
                ];
            }
            $grouped = [];
            foreach ($rows("PRAGMA foreign_key_list({$quoted})") as $r) {
                $id = (int) $r['id'];
                $grouped[$id]['name'] = (string) $r['id'];
                $grouped[$id]['child'] = $t;
                $grouped[$id]['parent'] = (string) $r['table'];
                $grouped[$id]['columns'][(int) $r['seq']] = (string) $r['from'];
                $grouped[$id]['refColumns'][(int) $r['seq']] = $r['to'] === null ? '' : (string) $r['to'];
                $grouped[$id]['onDelete'] = (string) $r['on_delete'];
                $grouped[$id]['onUpdate'] = (string) $r['on_update'];
            }
            foreach ($grouped as $g) {
                ksort($g['columns']);
                ksort($g['refColumns']);
                $g['columns'] = array_values($g['columns']);
                $g['refColumns'] = array_values($g['refColumns']);
                $fks[] = $g;
            }
        }
    }

    foreach ($legs ?? [] as $g) {
        ksort($g['columns']);
        ksort($g['refColumns']);
        $g['columns'] = array_values($g['columns']);
        $g['refColumns'] = array_values($g['refColumns']);
        $g += ['onDelete' => 'NO ACTION', 'onUpdate' => 'NO ACTION'];
        $fks[] = $g;
    }

    // The primary key, read from the engine's own index listing rather than
    // from the column rows: a composite key's ORDER is the order a foreign key
    // must be declared in, and it is not the order the columns sit in.
    //
    // ENGINE-STATE BLINDNESS, and this one was silent. MySQL states the key in the
    // index NAME (`PRIMARY`); SQLite does not — it names it
    // `sqlite_autoindex_<table>_<n>` and reports `origin='pk'`. Reading only the
    // name therefore returns an EMPTY key for every table on SQLite (measured on
    // the shipped dial: 260 tables with a `pk` index, 0 named `primary`), and an
    // empty key is not an error — it is a silent refusal to judge, which is how a
    // key check comes to "pass" 194 tables it never looked at. Both signals are
    // accepted, and `PRAGMA table_info().pk` is the last resort for the
    // WITHOUT-ROWID shape, which has no `pk`-origin index at all.
    $primaryKey = [];
    foreach ($tables as $t) {
        $primaryKey[$t] = [];
        foreach ($indexes[$t] ?? [] as $index) {
            $isKey = ($index['origin'] ?? '') === 'pk' || strtoupper($index['name']) === 'PRIMARY';
            if ($isKey && $index['columns'] !== []) {
                $primaryKey[$t] = $index['columns'];
            }
        }
        if ($primaryKey[$t] !== []) {
            continue;
        }
        // The WITHOUT-ROWID shape has no `pk`-origin index at all, so the key has
        // to come from the column rows' own ordinal. That is SQLite-only: `PRAGMA`
        // is not SQL on any other engine, and running it on MySQL is a 1064
        // syntax error, not a harmless no-op — which is exactly how this fallback
        // first shipped and killed the entire MySQL run.
        if ($engine !== 'sqlite') {
            continue;
        }
        $pkOrdinals = [];
        foreach ($rows("PRAGMA table_info('".str_replace("'", "''", $t)."')") as $r) {
            $ord = (int) $r['pk'];
            if ($ord > 0) {
                $pkOrdinals[$ord] = (string) $r['name'];
            }
        }
        if ($pkOrdinals !== []) {
            ksort($pkOrdinals);
            $primaryKey[$t] = array_values($pkOrdinals);
        }
    }

    return ['tables' => $tables, 'columns' => $columns, 'indexes' => $indexes, 'foreignKeys' => $fks, 'primaryKey' => $primaryKey];
}

// =====================================================================
// 3. The Telegram side, read from its own authority
// =====================================================================

$scheme = TlParser::parseFile($root.'/schema/sources/TL_telegram_v227.tl');
$catalog = MirrorCatalog::load($root.'/src/Schema/schema/mirror-catalog.json', $scheme);
$resolver = new MirrorTableResolver($catalog, $scheme);
$resolverRoots = $resolver->resolveAll($catalog->tableNames());

/**
 * The type a TL scalar is, and the storage shape the source REQUIRES of it.
 *
 * This table is the whole type-fidelity vocabulary, and it is written in
 * Telegram's terms: the left column is what the wire declares, the right is the
 * canonical database type a storeable column must have. Every entry is a real
 * database-design fact, not a preference:
 *
 *   int    -> 32-bit signed: a chat id, a message id, a flag bit.
 *   long   -> 64-bit signed: Telegram's user/peer/account ids and every
 *             `long`-declared counter. A 32-bit column cannot hold one, so an
 *             `integer` here is not a translation, it is a truncation.
 *   int128 -> no engine has a 128-bit integer. DECIMAL(p,0) is the standard
 *             lossless NUMERIC mapping (39 digits cover the full unsigned
 *             128-bit range); TEXT holds the digits but gives up ordering,
 *             aggregation and numeric comparison, which is what a store is for.
 *   bytes  -> an arbitrary-length, charset-less binary string. MySQL/InnoDB
 *             cannot index a BLOB without a prefix length, and a utf8mb4 TEXT
 *             column is the only one of the two that round-trips arbitrary
 *             bytes under a character set without a truncation risk, so
 *             lowercase-hex-into-TEXT is the standard lossless mapping.
 *   string -> a UTF-8 text string. TEXT is the direct mapping.
 *   double -> IEEE-754 binary64. DOUBLE is the direct mapping.
 *   true   -> a flag bit that is present-or-absent. A `flags.N?true` param has
 *             no third state, so the column is non-nullable with a falsy
 *             default, and the FALSY value IS the absent value — this one needs
 *             no translation at all, because storing (0/false) for absent is
 *             what the source itself means.
 *
 * @return array{family:string,width:int|null,truth:string,fact:string}
 */
function tlScalarExpectation(string $baseType): array
{
    return match ($baseType) {
        'int' => ['family' => 'int', 'width' => 32, 'truth' => 'TL `int` — a signed 32-bit value', 'fact' => ''],
        'long' => ['family' => 'int', 'width' => 64, 'truth' => 'TL `long` — a signed 64-bit value', 'fact' => ''],
        'int128' => [
            'family' => 'decimal', 'width' => 39,
            'truth' => 'TL `int128` — a 128-bit signed integer',
            'fact' => 'No SQL engine has a 128-bit integer type. DECIMAL(39,0) is the standard lossless '
                .'NUMERIC mapping; a TEXT column preserves the digits but discards ordering, summation and '
                .'numeric comparison, which are the reason a value is in a database at all.',
        ],
        'int256' => [
            'family' => 'decimal', 'width' => 78,
            'truth' => 'TL `int256` — a 256-bit signed integer',
            'fact' => 'As `int128`: DECIMAL(p,0) is the standard lossless NUMERIC mapping for a width no '
                .'engine implements natively.',
        ],
        'double' => ['family' => 'float', 'width' => null, 'truth' => 'TL `double` — IEEE-754 binary64', 'fact' => ''],
        'string' => ['family' => 'text', 'width' => null, 'truth' => 'TL `string` — a UTF-8 text string', 'fact' => ''],
        'bytes' => [
            'family' => 'text', 'width' => null,
            'truth' => 'TL `bytes` — an arbitrary-length, charset-less binary string',
            'fact' => 'MySQL/InnoDB cannot index a BLOB without a prefix length, and a utf8mb4 TEXT column '
                .'is the one candidate type that round-trips arbitrary bytes under a character set without '
                .'truncation risk, so lowercase-hex-into-TEXT is the standard lossless mapping.',
        ],
        'true' => ['family' => 'int', 'width' => 8, 'truth' => 'TL `true` — a flag bit, present or absent', 'fact' => ''],
        '#' => ['family' => 'int', 'width' => null, 'truth' => 'TL `#` — a bare bitmask', 'fact' => ''],
        default => ['family' => 'other', 'width' => null, 'truth' => "TL `{$baseType}`", 'fact' => ''],
    };
}

/**
 * Walk the resolver's DDL graph and record, for every table it reaches, the TL
 * type whose constructors that table is decomposed from.
 *
 * WHY A WALK AND NOT A LOOKUP. A table's TL type is not a property of its NAME;
 * it is a property of the path that reaches it. `tf_messages_reply_markup_rows`
 * is a decomposition of `KeyboardButtonRow`, which is only knowable by walking
 * `Message.reply_markup` -> `ReplyKeyboardMarkup` -> `rows` -> `KeyboardButtonRow`.
 * The walk mirrors `MirrorTableResolver`'s own recursion, so the provenance it
 * records and the table set the resolver emits are two answers about the same
 * question — and `assertProvenanceAgreesWithResolver()` compares them, so a
 * drift between the two is reported rather than silently inherited.
 *
 * @param  list<MirrorTable>  $roots
 * @return array<string, array{tlType:?string,ctors:list<string>,positioned:bool,parent:string,field:string,via:string}>
 */
function walkProvenance(array $roots, MirrorCatalog $catalog, TlSchemeAccess $scheme): array
{
    $out = [];
    $queue = [];

    foreach ($catalog->tableNames() as $tfName) {
        $entry = $catalog->table($tfName);
        $out[$tfName] ??= [
            'tlType' => $entry->tlType,
            'ctors' => $entry->ctors,
            'positioned' => false,
            'parent' => '',
            'field' => '',
            'via' => 'catalog entry',
        ];
    }

    foreach ($roots as $root) {
        $queue[] = [$root, null];
    }

    while ($queue !== []) {
        [$table, $parentKey] = array_shift($queue);

        $prov = $out[$table->tfName] ?? null;
        if ($prov === null) {
            // A child reached only through the tree: its provenance is whatever
            // the parent's TL type says about the field that produced it.
            if ($parentKey === null) {
                continue;
            }
            [$parentTf, $field] = $parentKey;
            $prov = resolveProvenance($out[$parentTf] ?? null, $field, $catalog, $scheme, $parentTf);
            $out[$table->tfName] = [
                'tlType' => $prov['tlType'],
                'ctors' => $prov['ctors'],
                'positioned' => $table->positioned,
                'parent' => $parentTf,
                'field' => $field,
                'via' => $prov['via'],
            ];
        }

        foreach ($table->children as $child) {
            $queue[] = [$child, [$table->tfName, childFieldName($child->tfName, $table->tfName)['field']]];
        }
    }

    return $out;
}

/**
 * The TL field a child table was minted from: its name is `{parent}_{field}`.
 *
 * ONE MISMATCH IS TOLERATED, and it is a measured one. Our child tables do not
 * always carry the TL field's exact spelling: the TL declares
 * `channelParticipantSelf.inviter_id:long`, and the table is
 * `tf_channel_participants_inviter` — the `_id` suffix dropped. A strict
 * comparison therefore fails on a table whose descent is perfectly faithful, and
 * the failure looks exactly like "the authorities are silent", which is the
 * condition that used to produce a deletion recommendation.
 *
 * So the suffix is tried both ways, and the CALLER is told which spelling matched
 * (`$exact`) so a finding can name the TL field it actually read. Guessing without
 * recording the guess would just move the defect: an audit that reports the wrong
 * field name with confidence is worse than one that reports none.
 *
 * @return array{field:string,exact:bool}
 */
function childFieldName(string $childTf, string $parentTf, ?array $parentCtors = null, ?TlSchemeAccess $scheme = null): array
{
    $prefix = $parentTf.'_';
    if (! str_starts_with($childTf, $prefix)) {
        return ['field' => '', 'exact' => false];
    }
    $field = substr($childTf, strlen($prefix));

    if ($parentCtors !== null && $scheme !== null && paramAcrossCtors($parentCtors, $field, $scheme) === null) {
        $withSuffix = $field.'_id';
        if (paramAcrossCtors($parentCtors, $withSuffix, $scheme) !== null) {
            return ['field' => $withSuffix, 'exact' => false];
        }
    }

    return ['field' => $field, 'exact' => true];
}

/**
 * @return array{tlType:?string,ctors:list<string>,via:string}
 */
function resolveProvenance(?array $parentProv, string $field, MirrorCatalog $catalog, TlSchemeAccess $scheme, string $parentTf): array
{
    $none = ['tlType' => null, 'ctors' => [], 'via' => 'no TL provenance'];
    if ($parentProv === null || $parentProv['tlType'] === null || $field === '') {
        return $none;
    }

    $param = paramAcrossCtors($parentProv['ctors'], $field, $scheme);
    if ($param === null) {
        return $none;
    }

    // A Vector is stored as its ELEMENT type; a reference is stored as its
    // TARGET type. Both are the type whose params the child table's columns come
    // from, which is the only question provenance is asked here.
    $baseType = $param->kind() === 'vector' ? $param->baseType() : $param->baseType();
    if ($param->kind() === 'scalar' || $param->kind() === 'true' || $param->kind() === 'nat') {
        return $none + ['via' => 'TL '.$param->kind().' — a single value, no child type'];
    }

    $tlType = $scheme->type($baseType);
    if ($tlType === null) {
        return $none + ['via' => 'TL '.$param->kind().' of a scalar element type'];
    }

    $ctors = [];
    $allowInput = $catalog->has($parentTf);
    foreach ($tlType->constructors() as $ctor) {
        if (! $allowInput && str_starts_with($ctor->name, 'input')) {
            continue;
        }
        $ctors[] = $ctor->name;
    }
    if ($ctors === []) {
        return $none;
    }

    return ['tlType' => $baseType, 'ctors' => $ctors, 'via' => 'TL '.$parentProv['tlType'].'.'.$field.' ('.$param->kind().' of '.$baseType.')'];
}

/** The TL param named `$field` in ANY of the named constructors, or null. */
function paramAcrossCtors(array $ctors, string $field, TlSchemeAccess $scheme): ?TlParam
{
    foreach ($ctors as $ctorName) {
        $params = $scheme->paramsOf($ctorName);
        if (isset($params[$field])) {
            return $params[$field];
        }
    }

    return null;
}

/**
 * A thin, memoised reader over the parsed TL scheme.
 *
 * The scheme's own accessors are enough for everything above; this exists so the
 * provenance walk does not re-scan 632 types per lookup.
 */
final class TlSchemeAccess
{
    /** @var array<string,TlType> */
    private array $types;

    /** @var array<string,array<string,TlParam>> */
    private array $ctorParams = [];

    public function __construct(private readonly TlScheme $scheme)
    {
        $this->types = $scheme->types();
    }

    public function type(string $name): ?TlType
    {
        return $this->types[$name] ?? null;
    }

    /** @return array<string,TlParam> */
    public function paramsOf(string $ctor): array
    {
        if (isset($this->ctorParams[$ctor])) {
            return $this->ctorParams[$ctor];
        }
        foreach ($this->types as $type) {
            foreach ($type->constructors() as $candidate) {
                if ($candidate->name === $ctor) {
                    return $this->ctorParams[$ctor] = $candidate->params();
                }
            }
        }

        return $this->ctorParams[$ctor] = [];
    }
}

// =====================================================================
// 4. Findings
// =====================================================================

/** @var list<array<string,mixed>> $findings */
$findings = [];

/** @var array<string,array{verdict:string,note:string}> $notes matched findings that the rules already explained */
$notes = [];

function finding(
    array &$findings,
    string $check,
    string $verdict,
    string $table,
    string $subject,
    string $telegram,
    string $ours,
    string $minimalMove,
    string $fact = '',
): void {
    $findings[] = [
        'check' => $check,
        'verdict' => $verdict,
        'table' => $table,
        'subject' => $subject,
        'telegram' => $telegram,
        'ours' => $ours,
        'minimal_move' => $minimalMove,
        'fact' => $fact,
    ];
}

// ---- declared justifications ------------------------------------------

/**
 * Declared divergences. A rule the tool cannot resolve becomes EXPLAINED only if
 * a declaration names the fact and cites the file that does the divergence.
 *
 * The registry is a SEPARATE artefact on purpose: a declaration is a claim by a
 * human about a file, and it must be reviewable on its own rather than hidden
 * inside the rule that consumes it.
 *
 * @return list<array{check:string,table:string,subject:string,verdict:string,fact:string,cites:string}>
 */
function loadDeclarations(string $path): array
{
    if (! is_file($path)) {
        return [];
    }
    $decoded = json_decode((string) file_get_contents($path), true);
    if (! is_array($decoded)) {
        return [];
    }

    $out = [];
    foreach ((array) ($decoded['declarations'] ?? []) as $d) {
        if (! is_array($d)) {
            continue;
        }
        foreach (['check', 'table', 'subject', 'verdict', 'fact', 'cites'] as $field) {
            if (! isset($d[$field]) || ! is_string($d[$field]) || $d[$field] === '') {
                fwrite(STDERR, "declaration is missing '{$field}': a declaration without a named fact and a "
                    ."cited file is not a declaration\n");
                exit(EXIT_USAGE);
            }
        }
        $out[] = $d;
    }

    return $out;
}

$declarations = loadDeclarations($root.'/docs/schema/fidelity-declarations.json');

/**
 * @param  list<array<string,mixed>>  $findings
 * @param  list<array{check:string,table:string,subject:string,verdict:string,fact:string,cites:string}>  $declarations
 */
function applyDeclarations(array &$findings, array $declarations): void
{
    foreach ($findings as $i => $f) {
        foreach ($declarations as $d) {
            if ($d['check'] !== $f['check'] || $d['table'] !== $f['table']) {
                continue;
            }
            // A subject declaration is a PREFIX match: declaring `column:peer_id`
            // covers the scope pair without transcribing a column list.
            if (! str_starts_with((string) $f['subject'], $d['subject'])) {
                continue;
            }
            if ($d['verdict'] !== 'STORAGE-TRANSLATION' && $d['verdict'] !== 'DELIBERATE') {
                fwrite(STDERR, 'a declaration may only claim STORAGE-TRANSLATION or DELIBERATE; '
                    ."'{$d['verdict']}' would let a divergence declare itself a defect away\n");
                exit(EXIT_USAGE);
            }
            $findings[$i]['verdict'] = $d['verdict'];
            $findings[$i]['fact'] = $d['fact'].' (declared, citing '.$d['cites'].')';
            $findings[$i]['minimal_move'] = 'none — the difference is declared';
            break;
        }
    }
}

// =====================================================================
// 5. Run the checks
// =====================================================================

$db = bootDialect($options['engine']);
$schema = reflectSchema($db, $options['engine']);

// Belt and braces on the primary key, checked OUTSIDE the reflection so a defect
// there cannot hide behind it. `reflectSchema()` already reads the key from the
// engine's index listing; this asserts that it actually came back, on whichever
// engine is running. A tool that reads no keys must say so and stop rather than
// publish key verdicts it has no basis for — which is what the pre-repair run
// did, silently, for 194 tables.
//
// Measured on the shipped dial: 269 tables, 268 with a primary key. The one
// without is a table the engine genuinely reports no key for, so the count is not
// expected to reach the total, and requiring that would be asserting a fact about
// the schema rather than about the reader.
$keysRead = count(array_filter($schema['primaryKey'], static fn (array $k): bool => $k !== []));
if ($keysRead === 0) {
    fwrite(STDERR, "the audit read ZERO primary keys on the {$options['engine']} engine.\n"
        ."  SQLite states the key in `PRAGMA index_list().origin='pk'`, not in the index NAME, so a reader\n"
        ."  that looks only for the name `primary` silently empties the key map. On this engine that\n"
        ."  would make every key verdict a verdict on nothing. Refusing to continue.\n");
    exit(EXIT_USAGE);
}

foreach ($options['inject'] as $inject) {
    if (! isset($schema['columns'][$inject['table']][$inject['column']])) {
        fwrite(STDERR, "cannot inject: {$inject['table']}.{$inject['column']} is not a column of the executed schema\n");
        exit(EXIT_USAGE);
    }
    $canon = canonicalFromToken($inject['value']);
    $schema['columns'][$inject['table']][$inject['column']]['family'] = $canon['family'];
    $schema['columns'][$inject['table']][$inject['column']]['width'] = $canon['width'];
    $schema['columns'][$inject['table']][$inject['column']]['signed'] = $canon['signed'];
    $schema['columns'][$inject['table']][$inject['column']]['display'] = $canon['display'];
}

$schemeAccess = new TlSchemeAccess($scheme);
$provenance = walkProvenance($resolverRoots, $catalog, $schemeAccess);

$resolverTables = [];
$collect = static function (array $tables) use (&$collect, &$resolverTables): void {
    foreach ($tables as $t) {
        $resolverTables[$t->tfName] ??= $t;
        $collect($t->children);
    }
};
$collect($resolverRoots);

/** The tenant column every `tf_*` table carries. It is ours, not Telegram's. */
$tenantColumn = 'account_id';

/** Tables the tenant FK points at; not intra-mirror edges. */
$tenantTables = ['telegram_accounts', 'tf_accounts'];

/**
 * Split an EXECUTED key into its tenant part and its wire part, ONCE.
 *
 * WHY ONCE, AND WHY THE WORD ONCE IS THE POINT. The tenant column is ours — it
 * scopes every row to one Telegram account and appears in no TL constructor. It
 * is a SCOPE, not a second identity, so it must be removed exactly once before the
 * key is compared against a wire identity, and a column already removed must not be
 * removed again. Stripping the same column twice is not a no-op when the two strips
 * have DIFFERENT predicates: a strip that also removes "an id column, because ids
 * are foreign keys" will — applied to an already-stripped list — take the wire's own
 * `id` with it, and a table whose key is `(account_id, id)` then reads as "no key at
 * all". That is the single most destructive thing an audit can say about a table,
 * and the move it recommends — make the key `[id]` — is wrong twice over: `[id]`
 * alone is not unique across tenants, and it discards the tenant scoping the whole
 * mirror is built on.
 *
 * The scope is REPORTED, never silently dropped. A key the wire fixes and the tenant
 * scopes is a different object from a key the wire fixes alone, and a comparison
 * that hides the difference misreports both.
 *
 * This is defined ONCE, here, and every check that needs a tenant-free key uses this
 * function. A second copy of this predicate is a second chance at the defect.
 *
 * @param  list<string>  $key  the executed key, in the engine's own order
 * @return array{wire:list<string>,tenant:list<string>,scoped:bool}
 */
$splitKey = static function (array $key) use ($tenantColumn): array {
    $tenant = [];
    $wire = [];
    $seenTenant = false;
    foreach ($key as $column) {
        if (! $seenTenant && $column === $tenantColumn) {
            $tenant[] = $column;
            $seenTenant = true;

            continue;
        }
        // A tenant column anywhere but the FRONT is not a scope in front of an
        // identity — it is a column that happens to share the name. Removing it
        // from the middle would silently reorder a key the engine reports in a real
        // order, and a composite key's order is the order a foreign key must be
        // declared in.
        $wire[] = $column;
    }

    return ['wire' => $wire, 'tenant' => $tenant, 'scoped' => $seenTenant];
};

$mirrorTables = array_values(array_filter(
    $schema['tables'],
    static fn (string $t): bool => str_starts_with($t, 'tf_'),
));

$inScope = static function (string $check) use ($options): bool {
    return $options['scope'] === [] || in_array($check, $options['scope'], true);
};

// ---------------------------------------------------------------------
// C1 — key width and signedness, per relationship.
//
// The defect that cost the whole MySQL track: a child leg and the parent leg it
// joins disagreeing on integer width or signedness. MySQL refuses the
// constraint with 3780. This sweeps EVERY relation, not the one that broke.
// ---------------------------------------------------------------------

$c1 = ['id' => 'C1', 'name' => 'key width and signedness per relationship', 'measurable' => false, 'pairs' => 0];

// SQLite models neither width nor signedness, so a verdict there would be a
// verdict on nothing. An injection is the one way to make a width KNOWN on this
// engine, which is how the exit-code pair is proven without a MySQL server.
$widthKnowable = $options['engine'] === 'mysql' || $options['inject'] !== [];
if ($inScope('C1')) {
    $c1['measurable'] = $widthKnowable;
    foreach ($schema['foreignKeys'] as $fk) {
        if (! str_starts_with($fk['child'], 'tf_') || in_array($fk['parent'], $tenantTables, true)) {
            continue;
        }
        $c1['pairs']++;
        foreach ($fk['columns'] as $i => $childCol) {
            $parentCol = $fk['refColumns'][$i] ?? '';
            $child = $schema['columns'][$fk['child']][$childCol] ?? null;
            $parent = $schema['columns'][$fk['parent']][$parentCol] ?? null;
            if ($child === null || $parent === null) {
                finding(
                    $findings, 'C1', 'UNEXPLAINED', $fk['child'], "fk:{$fk['child']}->{$fk['parent']}[{$childCol}]",
                    'Telegram joins these two legs on one logical key',
                    "leg '{$childCol}' or its referenced column '{$parentCol}' is not in the executed schema",
                    'name the referenced column that actually exists, or drop the leg',
                );

                continue;
            }
            $familySame = $child['family'] === $parent['family'];
            if (! $widthKnowable) {
                continue;
            }
            $childWidth = $child['width'];
            $parentWidth = $parent['width'];
            if ($childWidth === null || $parentWidth === null) {
                if ($childWidth === $parentWidth) {
                    continue;
                }
                finding(
                    $findings, 'C1', 'UNEXPLAINED', $fk['child'], "fk:{$fk['child']}->{$fk['parent']}[{$childCol}]",
                    'Telegram joins these two legs on ONE logical key, so both legs must be the same '
                        .'integer width and signedness',
                    'child leg is '.showType($child).' and the referenced column is '.showType($parent)
                        .' — exactly one of the two widths is measurable here',
                    're-run with --engine=mysql, where information_schema reports both widths; an '
                        .'unmeasurable leg is not a matching leg',
                );

                continue;
            }
            if ($childWidth === $parentWidth && $child['signed'] === $parent['signed'] && $familySame) {
                continue;
            }
            finding(
                $findings, 'C1', 'COPYING-GAP', $fk['child'], "fk:{$fk['child']}->{$fk['parent']}[{$childCol}]",
                'Telegram joins these two legs on ONE logical key, so both legs carry the same integer '
                    .'width and signedness',
                'child leg '.showType($child).' against referenced column '.showType($parent),
                'make the child leg '.showType($parent).' — the parent is the table whose identity the '
                    .'wire fixes, so the move is toward it',
            );
        }
    }
}

// ---------------------------------------------------------------------
// C2 — does every foreign key name a UNIQUE index on the parent?
//
// MySQL 6125 is the other engine refusal this repository has already been hit
// by, and it is a pure key-shape question: the constraint needs a unique index
// whose LEADING columns are exactly the referenced columns, in order.
// ---------------------------------------------------------------------

$c2 = ['id' => 'C2', 'name' => 'foreign keys name a unique index on the parent', 'measurable' => true, 'pairs' => 0];

if ($inScope('C2')) {
    foreach ($schema['foreignKeys'] as $fk) {
        if (! str_starts_with($fk['child'], 'tf_')) {
            continue;
        }
        $c2['pairs']++;
        $ref = $fk['refColumns'];
        $covered = false;
        foreach ($schema['indexes'][$fk['parent']] ?? [] as $index) {
            if (! $index['unique']) {
                continue;
            }
            if (array_slice($index['columns'], 0, count($ref)) === $ref) {
                $covered = true;
                break;
            }
        }
        if ($covered) {
            continue;
        }
        $have = [];
        foreach ($schema['indexes'][$fk['parent']] ?? [] as $index) {
            $have[] = ($index['unique'] ? 'UNIQUE ' : '').implode('+', $index['columns']);
        }
        finding(
            $findings, 'C2', 'UNEXPLAINED', $fk['child'], "fk:{$fk['child']}->{$fk['parent']}",
            'Telegram identifies the parent row by these columns, so the pair is an identity and an FK '
                .'may rest on it',
            'no UNIQUE index on '.$fk['parent'].' leads with ['.implode(',', $ref).']; it has ['
                .implode(' | ', $have).'] — MySQL raises 6125 on this',
            'make '.implode(',', $ref).' the leading columns of a UNIQUE index on '.$fk['parent'],
        );
    }
}

// ---------------------------------------------------------------------
// C3 + C4 — per-column type and nullability, against the TL declaration.
//
// A column is compared only when the walk can name the TL field it came from.
// Columns with no derivable provenance are counted and LISTED (authority
// coverage), never silently passed: a column nobody can attribute to a wire
// field is a column nobody has checked against Telegram.
// ---------------------------------------------------------------------

$c3 = ['id' => 'C3', 'name' => 'column type against the TL declaration', 'measurable' => true, 'compared' => 0];
$c4 = ['id' => 'C4', 'name' => 'column nullability against the TL declaration', 'measurable' => true, 'compared' => 0];

/** The TL param behind a column name, resolved against the table's provenance. */
$resolveColumnParam = static function (string $table, string $column) use ($provenance, $schemeAccess, $catalog): ?array {
    $prov = $provenance[$table] ?? null;
    if ($prov === null || $prov['tlType'] === null) {
        return null;
    }

    // 1. A catalog parent declares its own base/child SHAPES, and the shape is
    //    the authority for which field produced which column. Decompose the
    //    declared shape rather than guessing from the column name.
    if ($catalog->has($table)) {
        $entry = $catalog->table($table);
        foreach ([...$entry->base, ...$entry->children] as [$field, $shape]) {
            foreach (declaredColumnsOfShape($shape, $field, $entry, $schemeAccess) as $produced => $producedShape) {
                if ($produced === $column) {
                    $param = paramAcrossCtors($prov['ctors'], $field, $schemeAccess);

                    return ['field' => $field, 'param' => $param, 'shape' => $producedShape, 'source' => 'declared shape'];
                }
            }
        }
    }

    // 2. A union-decomposed child: the column IS the param name, or one half of
    //    a `Peer` param's `{name}_type` / `{name}_id` pair.
    $param = paramAcrossCtors($prov['ctors'], $column, $schemeAccess);
    if ($param !== null) {
        return ['field' => $column, 'param' => $param, 'shape' => null, 'source' => 'union decomposition'];
    }
    foreach (['type', 'id'] as $half) {
        $stem = substr($column, 0, -(strlen($half) + 1));
        if ($stem === $column) {
            continue;
        }
        $candidate = paramAcrossCtors($prov['ctors'], $stem, $schemeAccess);
        if ($candidate !== null && $candidate->kind() === 'ref' && $candidate->baseType() === 'Peer') {
            return ['field' => $stem, 'param' => $candidate, 'shape' => null, 'source' => 'union decomposition (Peer pair)'];
        }
    }

    return null;
};

/**
 * Columns that are ours by construction and carry no TL field.
 *
 * `use` IS LOAD-BEARING HERE. A `static function` closure does not inherit the
 * enclosing scope, so without `use` both `$tenantColumn` and `$schema` were
 * UNDEFINED inside it: the tenant test compared against null and was therefore
 * always false, and `$schema['primaryKey']` was null so the key-inheritance test
 * below was always false too. Neither raised — PHP emits a warning and carries on
 * — so the effect was not a crash but a SILENTLY DISABLED RULE. A check that is
 * disabled cannot fail, and a gate whose rules silently switch themselves off is
 * exactly the failure this repository has now paid for twice. Verified: the
 * unfixed form emitted one "Undefined variable $tenantColumn" warning per column
 * compared.
 *
 * AND THE RULE IT GUARDS WAS INVERTED, which only became visible once the `use`
 * was fixed. The intent is right — a child inherits its parent's key columns, and
 * C1/C7 compare those — but the test for "this column is an INHERITED key, not the
 * child's own TL field" was `! isset($schema['columns'][$t][$c]['tl_field'])`, and
 * `tl_field` is NEVER WRITTEN anywhere in this file (measured: one occurrence, the
 * `isset` itself). So the guard was vacuously TRUE for every primary key column of
 * every table with provenance: the rule fired on all of them, and C3 was blinded to
 * every key column. That is why the two confirmed findings
 * (`tf_stars_transactions_extended_media.id` varchar-where-TL-says-int, and
 * `tf_stars_transactions_stargift.id` varchar-where-TL-says-long) appeared in the
 * run while the rule was dead and VANISHED the moment the rule was fixed.
 *
 * The lesson is the one this repair exists for: a rule that has never run cannot
 * have been reviewed, and fixing the defect that was hiding it did not reveal a
 * clean rule — it revealed a second defect underneath. The condition is now
 * POSITIVE and derived: a key column is inherited only when the child's OWN TL type
 * declares no field of that name. `StarGift.id` is declared `long` by `StarGift`,
 * so that column is the child's own field and C3 must judge it.
 *
 * 🔴 AND THAT REPAIR WAS ALSO WRONG — a THIRD defect underneath, measured
 * 2026-10-08 on MySQL 8.4.11.
 *
 * "The child's own TL type declares a field of that name" is a NAME test, and for
 * an `FK>`/`1:N` child it is the wrong question. A child's key columns are its
 * PARENT'S identity, carried down by `factKey()`, whatever the child's own union
 * happens to call a field of the same name. The two are unrelated:
 *
 *   tf_stars_transactions.id            is `starsTransaction#13659eb0 … id:string`
 *                                       — an opaque Telegram transaction id
 *   tf_stars_transactions_stargift      is an `FK>`StarGift CHILD, so its `id` leg
 *     .id                                  holds that same transaction id, AND its
 *                                         own TL type `StarGift` independently
 *                                         declares an unrelated `id:long`
 *   tf_stars_transactions_extended_media  is a `Vector<MessageMedia>` child, so its
 *     .id                                  `id` leg holds that same transaction id,
 *                                         AND `messageMediaStory#68cb6283`
 *                                         declares an unrelated `id:int`
 *
 * So the name test sent C3 to the union member's `id` and asked it to judge the
 * PARENT's key leg against a field that has nothing to do with it.
 *
 * IT WAS NOT CLOSE, AND IT WAS NOT COSMETIC. MEASURED on the shipped dial on real
 * MySQL 8.4.11, against the executed schema:
 *
 *   ALTER TABLE tf_stars_transactions_stargift MODIFY id BIGINT NOT NULL;
 *     -> SQLSTATE[HY000] 3780 Referencing column 'id' and referenced column 'id'
 *        in foreign key constraint 'tf_stars_transactions_stargift_st_parent_fk'
 *        are incompatible.
 *   ALTER TABLE tf_stars_transactions_extended_media MODIFY id INT NOT NULL;
 *     -> SQLSTATE[HY000] 3780 (same constraint family)
 *
 * The child's `id` IS a foreign key leg into `tf_stars_transactions(id)`, which is
 * `VARCHAR(255)` because the TL says `string`. Retyping it is not merely refused by
 * the engine: were it possible it would TRUNCATE Telegram's opaque transaction id
 * into an integer — the exact infidelity this tool exists to catch, committed by
 * the tool that was reporting it.
 *
 * AND THE TELL IS IN THE ARITHMETIC, NOT IN AN ARGUMENT. The family has TWENTY-ONE
 * children of `tf_stars_transactions`, and all twenty-one carry the identical
 * inherited `id` leg with the identical parent FK. The old rule judged NINETEEN of
 * them structural and exactly TWO unstructural — and the two it picked out were the
 * two whose union coincidentally declares a field named `id`. A rule that separates
 * nineteen identical cases from two, on the strength of a name, is not reading
 * provenance; it is reading a coincidence and calling it evidence.
 *
 * SO THE CONDITION IS NOW DERIVED FROM THE SCHEMA, not from a name: a key column
 * that participates in a foreign key leg pointing at THIS TABLE'S OWN PARENT is an
 * inherited parent identity. It belongs to the parent's TL field, and C1/C7 compare
 * it there. The parent's provenance parent is read from the walk, not guessed from
 * a prefix, so this cannot fire on a table that has no parent to inherit from.
 *
 * WHAT IT DELIBERATELY DOES NOT TOUCH, and the check that proves it: the folded
 * columns this same audit asked for. `tf_channel_participants.inviter_id` is NOT a
 * key column of `tf_channel_participants` and is NOT a foreign key leg, so C3 and C4
 * still judge it — which is why the fold's `NOT NULL DEFAULT 0` and its `BIGINT`
 * are measured, not exempted. A repair that silenced a verdict is not a repair; a
 * repair that redirects it to the authority that actually owns the field is.
 *
 * @param  array<string,TlParam>|null  $resolved  the child's OWN TL field for this
 *                                                column, already resolved, or
 *                                                null when it has none
 */
$structural = static function (string $table, string $column, ?array $resolved) use ($tenantColumn, $schema, $provenance): bool {
    if ($column === $tenantColumn) {
        return true;
    }
    if ($column === 'constructor' || $column === 'position' || $column === 'constructor_name') {
        return true;
    }
    // Only a KEY column can be an inherited identity, and only a key column the
    // child's own TL type does NOT declare falls through on the pre-existing test.
    if (! in_array($column, $schema['primaryKey'][$table] ?? [], true)) {
        return false;
    }
    if ($resolved === null) {
        return true;
    }

    // 🔴 The name test above is NOT sufficient, and the comment it used to carry was
    // wrong: a child that reuses its parent's identity is still judged by the name
    // of a field its own union declares for an unrelated purpose. The FK leg is the
    // authority — it is what makes the value in this column the parent's value.
    //
    // NON-VACUITY, PROVEN BY POISON rather than asserted. With this branch removed,
    // both `tf_stars_transactions_{stargift,extended_media}.id` rows RETURN
    // (MEASURED 2026-10-08: COPYING-GAP 1 -> 2, and the two added rows are exactly
    // those two), so the branch is load-bearing and not a coincidence of some other
    // edit in the same tree. The companion proof runs the OTHER way: declaring the
    // folded columns `string(64)` instead of `bigInteger` in
    // 2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact
    // turns C3 RED on all three of them with `TL long — a signed 64-bit value`,
    // which is what proves this branch did not quietly exempt them.
    $parent = $provenance[$table]['parent'] ?? '';
    if ($parent === '') {
        return false; // a root table has no identity to inherit
    }
    foreach ($schema['foreignKeys'] ?? [] as $fk) {
        if ($fk['child'] !== $table || $fk['parent'] !== $parent) {
            continue;
        }
        if (in_array($column, $fk['columns'], true)) {
            return true;
        }
    }

    return false;
};

/**
 * The columns a catalog shape declares, read off the repo's own decomposer so
 * the tool cannot disagree with the resolver about what a shape expands to.
 *
 * @return array<string,string> column name => shape string
 */
function declaredColumnsOfShape(string $shape, string $field, MirrorTableEntry $entry, TlSchemeAccess $scheme): array
{
    $out = [];
    if (str_starts_with($shape, 'FK→') || $shape === '1:N child') {
        return $out;
    }
    // A peer shape expands into two named halves; the width each half carries is
    // written into the shape itself, so read it rather than assume a peer id is
    // always BIGINT.
    if (str_contains($shape, 'TINYINT')) {
        $out[$field === 'peer_id' ? 'peer_type' : $field.'_type'] = $shape;
        $out[$field === 'peer_id' ? 'peer_id' : $field.'_id'] = $shape;

        return $out;
    }
    foreach (MirrorFieldDecomposerBridge::columns($shape, $field) as $column) {
        $out[$column->name] = $shape;
    }

    return $out;
}

/** A zero-regex shim onto the repo's own shape decomposer. */
final class MirrorFieldDecomposerBridge
{
    /** @return list<MirrorColumn> */
    public static function columns(string $shape, string $field): array
    {
        return MirrorFieldDecomposer::fromShape($shape, $field) ?? [];
    }
}

$unattributed = [];

foreach ($mirrorTables as $table) {
    $prov = $provenance[$table] ?? null;
    foreach ($schema['columns'][$table] ?? [] as $column => $ours) {
        // Resolved FIRST, because whether a column is structural depends on
        // whether the child's OWN TL type declares it. Asking the other order
        // forces the rule to guess, and a rule that guesses cannot be reviewed.
        $resolved = $resolveColumnParam($table, $column);
        if ($structural($table, $column, $resolved)) {
            continue;
        }
        if ($resolved === null) {
            $unattributed[] = $table.'.'.$column;

            continue;
        }
        $param = $resolved['param'];
        if ($param === null) {
            $unattributed[] = $table.'.'.$column;

            continue;
        }

        // ---- C4: nullability. A TL field is non-nullable unless the schema
        // says otherwise. A conditional `flags.N?` field has no third state,
        // and the FALSY value is the absent value, so NOT NULL + a falsy default
        // is what the source itself means — not a translation.
        if ($inScope('C4')) {
            $c4['compared']++;
            // A `flags.N?true` field has no third state on the wire, and the
            // falsy value IS the absent value, so NOT NULL + a falsy default is
            // what the source means rather than a translation of it.
            $conditional = $param->conditional() !== null;
            if ($ours['nullable'] && ! $conditional) {
                finding(
                    $findings, 'C4', 'COPYING-GAP', $table, "column:{$column}",
                    'TL '.$resolved['field'].':'.$param->raw.' — a wire field with no conditional '
                        .'marker is ALWAYS present on the wire',
                    'the column is NULLABLE, so a row can carry a value Telegram has no encoding for',
                    'declare the column NOT NULL'.($param->kind() === 'scalar' && $ours['default'] === null
                        ? ' and, only if the wire genuinely defaults it, give it that default'
                        : ''),
                );
            }
        }

        // ---- C3: type fidelity, only where the wire declares a storable scalar.
        // A `Vector<X>` and a reference are STORAGE TRANSLATIONS by a named
        // fact, and they are relations, checked by C5 — not column types.
        if (! $inScope('C3')) {
            continue;
        }
        if ($param->kind() !== 'scalar') {
            continue;
        }
        $c3['compared']++;
        $expect = tlScalarExpectation($param->baseType());
        $familyOk = $ours['family'] === $expect['family'];
        $widthOk = $expect['width'] === null || $ours['width'] === null || $ours['width'] === $expect['width'];
        if ($familyOk && $widthOk) {
            // A TEXT column holding a `bytes` field is the one place the repo
            // stores hex rather than the raw type; the fact is named here, and
            // it is a rule, not a preference, because the decomposer marks the
            // column as hex for exactly this reason.
            continue;
        }
        if ($param->baseType() === 'bytes') {
            finding(
                $findings, 'C3', 'STORAGE-TRANSLATION', $table, "column:{$column}",
                $expect['truth'], showType($ours), 'none — the difference is the standard mapping below',
                $expect['fact'],
            );

            continue;
        }
        finding(
            $findings, 'C3', 'COPYING-GAP', $table, "column:{$column}",
            $expect['truth'], showType($ours),
            $expect['family'] === 'decimal'
                ? 'declare it DECIMAL('.$expect['width'].',0) — the lossless numeric mapping for a width '
                    .'no engine implements natively'
                : 'declare it '.$expect['family'].($expect['width'] === null ? '' : ' width '.$expect['width'])
                    .'; a Telegram `long` is 64 bits and a 32-bit column is a truncation, not a translation',
        );
    }
}

// ---------------------------------------------------------------------
// C5 — parent/child and SIBLING relationships, against the AUTHORITY ORDER.
//
// This is where defect (1) lived, and the shape of the mistake generalises to
// any audit, so it is worth stating exactly what it was.
//
// THE BUG. The descent test asked ONE question — "is the child a descendant of
// the parent in the RESOLVER's tree?" — and on "no" concluded "the catalog
// declares no path", then recommended DROPPING the constraint. It reported 255
// that way. Both halves of that are wrong:
//
//   1. THE QUESTION WAS ASKED IN THE WRONG PLACE. The descent for
//      `tf_bot_apps_photo -> tf_bot_apps` is declared in the PARENT's own base
//      column spec as the marker `['photo', 'FK→Photo']`, not as a top-level
//      catalog key. A child is deliberately not a top-level key — that is what
//      makes it a child — so a test that looks only for the child's name in one
//      flat map cannot see the declaration the catalog makes in the one place a
//      child's parent actually lives.
//   2. IT CONSULTED THE WRONG AUTHORITY FIRST. The TL is the specification of
//      record, as this tool's own header says. But the descent was read off a
//      repo artifact first and the artifact's silence was reported as the
//      specification's silence. Telegram states this nesting in one line, and no
//      artifact can overrule it:
//          botApp#95fcd1d6 … photo:Photo document:flags.0?Document = BotApp;
//      `photo:Photo` IS the nesting.
//
// THE ORDER, and it is not negotiable:
//
//   1. THE TL's declared field types. A constructor field whose declared type is
//      another constructor IS the nesting.
//   2. The CATALOG's `FK→` markers, read inside the PARENT's own column spec.
//   3. Only when BOTH are silent is the descent genuinely undeclared.
//
// WHY IT MATTERED. 255 live constraints were one bulk deletion away and every one
// was a faithful copy of Telegram. A tool that says "255 unexplained, drop these"
// is a HYPOTHESIS about 255 constraints, not a verdict — and it is the
// RECOMMENDATION that made it dangerous, because an unexplained row that
// recommends destruction will be acted on.
// ---------------------------------------------------------------------

$c5 = ['id' => 'C5', 'name' => 'relations against the TL, then the catalog', 'measurable' => true, 'edges' => 0];

$ancestors = [];
$parentOf = [];
foreach ($resolverTables as $name => $_) {
    $parentOf[$name] = $_->parentTf;
}
$ancestorCache = [];
$ancestorsOf = static function (string $table) use (&$ancestorCache, $parentOf): array {
    if (isset($ancestorCache[$table])) {
        return $ancestorCache[$table];
    }
    $out = [];
    $cur = $table;
    $guard = 0;
    while (($parentOf[$cur] ?? '') !== '' && $guard++ < 64) {
        $cur = $parentOf[$cur];
        $out[] = $cur;
    }

    return $ancestorCache[$table] = $out;
};

/**
 * Is `$child` a declared descent of `$parent`, and by WHICH authority?
 *
 * This answers the descent question ONLY — "does Telegram nest this?" — and never
 * the separate question "should the constraint exist?". Keeping them apart is the
 * point: only the first can be answered from the authorities, and a tool that
 * fuses them will eventually recommend deleting a faithful constraint because it
 * could not read the line that declares it.
 *
 * @return array{declared:bool,authority:string,field:string,declaredType:string,kind:string}
 */
$declaredDescent = static function (string $child, string $parent) use (
    $ancestorsOf, $catalog, $schemeAccess, $provenance
): array {
    $none = ['declared' => false, 'authority' => '', 'field' => '', 'declaredType' => '', 'kind' => ''];

    // The resolver's own ancestry is the strongest form of the catalog's
    // statement — the resolver read the marker and minted the child under that
    // parent — so it is asked first, and the TL below can only add to it.
    if (in_array($parent, $ancestorsOf($child), true)) {
        $prov = $provenance[$child] ?? null;

        return [
            'declared' => true,
            'authority' => 'the resolver minted this child under `'.$parent.'`, and the TL says `'
                .(string) ($prov['field'] ?? '').'` is a `'.(string) ($prov['tlType'] ?? '?').'`',
            'field' => (string) ($prov['field'] ?? ''),
            'declaredType' => (string) ($prov['tlType'] ?? ''),
            'kind' => 'resolver descent',
        ];
    }

    // The field this edge would be, read off the names ONCE: `tf_bot_apps_photo`
    // under `tf_bot_apps` is the field `photo`. Both authorities below need it, and
    // the `_id` tolerance is applied only where a TL type actually exists to check
    // against — guessing a suffix with nothing to confirm it against is guessing.
    $parentProv = $provenance[$parent] ?? null;
    $parentTl = $parentProv['tlType'] ?? null;
    $named = childFieldName(
        $child,
        $parent,
        $parentTl === null ? null : $parentProv['ctors'],
        $parentTl === null ? null : $schemeAccess,
    );
    $field = $named['field'];

    // ---- AUTHORITY 1: the TL, the specification of record.
    //
    // A parent this audit holds no TL type for is a parent it cannot judge, which
    // is a DIFFERENT answer from "Telegram declares no nesting". The two are kept
    // apart below, and conflating them is how a blind spot becomes a deletion
    // order.
    if ($field !== '' && $parentTl !== null) {
        $param = paramAcrossCtors($parentProv['ctors'], $field, $schemeAccess);
        if ($param !== null) {
            $kind = $param->kind();
            // A constructor-typed field IS the nesting: the wire holds another
            // constructor there. `photo:Photo` in `BotApp` is exactly that.
            if (! in_array($kind, ['scalar', 'true', '#'], true)) {
                return [
                    'declared' => true,
                    'authority' => 'the TL, the specification of record: `'.$parentTl.'` declares `'.$field.'` as a '
                        .$kind.' of `'.$param->baseType().'`, so the wire nests another object there',
                    'field' => $field,
                    'declaredType' => $param->baseType(),
                    'kind' => $kind,
                ];
            }

            // A SCALAR field is the opposite verdict and a POSITIVE one: the wire
            // holds a single value at that field, so a child TABLE there is
            // over-decomposition. A positive statement about the wire is a
            // divergence, and the caller verdicts it as one.
            return [
                'declared' => false,
                'authority' => 'the TL: `'.$parentTl.'` declares `'.$field.'` as a plain `'.$kind.'` — a single '
                    .$param->baseType().' value, so the wire nests nothing there',
                'field' => $field,
                'declaredType' => $param->baseType(),
                'kind' => $kind,
            ];
        }
    }

    // ---- AUTHORITY 2: the catalog's `FK→` marker, read INSIDE THE PARENT.
    //
    // This is the defect itself. The marker lives in the parent's own base or
    // children column spec — `tf_bot_apps.base` contains `['photo','FK→Photo']` —
    // and not as a top-level key, because a child is not a top-level key.
    if ($field !== '' && $catalog->has($parent)) {
        $entry = $catalog->table($parent);
        foreach ([...$entry->base, ...$entry->children] as [$declaredField, $shape]) {
            if ($declaredField !== $field || ! str_starts_with($shape, 'FK→')) {
                continue;
            }

            return [
                'declared' => true,
                'authority' => 'the catalog: `'.$parent.'` declares `'.$field.'` in its OWN column spec as `'
                    .$shape.'`, which is the catalog declaring the descent',
                'field' => $field,
                'declaredType' => substr($shape, 3),
                'kind' => 'catalog FK→ marker',
            ];
        }
    }

    // PHP's `+` on arrays KEEPS THE LEFT operand's keys, so `$none + ['field'=>…]`
    // would silently DISCARD the field name (the left side already has that key).
    // That bug shipped inside the fix for defect (1): the resulting finding text
    // said "declares no nested constructor at ``" — an empty field name — which is
    // a report that cannot be acted on. Assignment is used deliberately here.
    $none['field'] = $field;

    return $none;
};

// Sibling subject rows: two catalog entries that map the SAME TL type are two
// rows of one union, never one another's parent.
$siblingsByTlType = [];
foreach ($catalog->tableNames() as $tfName) {
    $siblingsByTlType[$catalog->table($tfName)->tlType][] = $tfName;
}
$areSiblings = [];
foreach ($siblingsByTlType as $group) {
    if (count($group) < 2) {
        continue;
    }
    foreach ($group as $a) {
        foreach ($group as $b) {
            if ($a !== $b) {
                $areSiblings[$a][$b] = true;
                $areSiblings[$b][$a] = true;
            }
        }
    }
}

if ($inScope('C5')) {
    foreach ($schema['foreignKeys'] as $fk) {
        if (! str_starts_with($fk['child'], 'tf_') || in_array($fk['parent'], $tenantTables, true)) {
            continue;
        }
        $c5['edges']++;
        $child = $fk['child'];
        $parent = $fk['parent'];

        if (isset($areSiblings[$child][$parent])) {
            finding(
                $findings, 'C5', 'COPYING-GAP', $child, "fk:{$child}->{$parent}",
                'both tables are declared entries for TL type `'.$catalog->table($child)->tlType.'`, so '
                    .'they are SIBLING ROWS of one union — two variants of the same wire object, neither '
                    .'of which contains the other',
                'a foreign key from one to the other, which forces a row of one variant to exist before a '
                    .'row of the other can',
                'drop the constraint. A sibling relation is an inference from a shared column-name prefix, '
                    .'and the catalog says these two are the same type, not one another\'s parent',
            );

            continue;
        }

        $descent = $declaredDescent($child, $parent);

        if ($descent['declared']) {
            continue;
        }

        if ($descent['field'] !== '' && $descent['authority'] !== '') {
            // The TL spoke and it said the field holds a plain value. That is a
            // positive statement about the wire, so it IS a divergence.
            finding(
                $findings, 'C5', 'COPYING-GAP', $child, "fk:{$child}->{$parent}",
                $descent['authority'],
                'a live foreign key on ['.implode(',', $fk['columns']).'] makes `'.$parent.'` the parent of a row, '
                    .'so a child TABLE exists where the wire holds a single value',
                'store the scalar on `'.$parent.'` as the column the TL names, and drop the child table and its '
                    .'constraint — the wire has no object there to store',
            );

            continue;
        }

        // Both authorities are SILENT. Silence has two causes that must not be
        // merged: the audit cannot see the parent's TL type, or the parent's TL
        // declares no nested constructor at that field. Either way the move MUST
        // NOT be destructive, because this row cannot distinguish "faithful and
        // unread" from "unfaithful".
        $parentTl = $provenance[$parent]['tlType'] ?? null;

        finding(
            $findings, 'C5', 'UNEXPLAINED', $child, "fk:{$child}->{$parent}",
            $parentTl === null
                ? 'this audit holds NO TL type for `'.$parent.'`, so it cannot read what Telegram nests there — '
                    .'that is an absence of evidence in the AUDIT, not a statement that Telegram nests nothing'
                : 'neither authority states this descent: the TL of `'.$parent.'` (`'.$parentTl.'`) declares no '
                    .'nested constructor at `'.$descent['field'].'`, and `'.$parent.'` declares no `FK→` marker '
                    .'for it in its own column spec',
            'a live foreign key on ['.implode(',', $fk['columns']).']',
            'do NOT drop this on the strength of this row — establish `'.$parent.'`\'s TL type in the catalog and '
                .'re-run. This row CANNOT distinguish a faithful constraint it failed to read from an unfaithful '
                .'one, so it may report a gap but it may not recommend destroying a constraint over one',
        );
    }
}

// ---------------------------------------------------------------------
// C6 — cardinality: 1:1 where the wire says one of, 1:N where it says a Vector.
// ---------------------------------------------------------------------

$c6 = ['id' => 'C6', 'name' => 'cardinality against the wire', 'measurable' => true, 'edges' => 0];

if ($inScope('C6')) {
    foreach ($schema['foreignKeys'] as $fk) {
        if (! str_starts_with($fk['child'], 'tf_') || in_array($fk['parent'], $tenantTables, true)) {
            continue;
        }
        $c6['edges']++;
        $child = $fk['child'];
        $parent = $fk['parent'];
        if (! in_array($parent, $ancestorsOf($child), true)) {
            continue; // C5 owns it; a cardinality verdict on an unauthorised edge is noise
        }
        $prov = $provenance[$child] ?? null;
        $positioned = (bool) ($prov['positioned'] ?? false);

        // THE 1:1 TEST, and the wrong one it replaces.
        //
        // The original read `$fk['columns'] === $parentKey` and called that
        // "one to one". It is not a 1:1 test. It says the FK's legs name the
        // parent's key — which is what a foreign key to a primary key ALWAYS
        // says, and therefore says nothing at all about how many child rows a
        // parent may have. Measured on the shipped dial: 208 of 211 intra-mirror
        // edges satisfy it, including every `Vector` child whose key is
        // `(account_id, bot_id, position)`, which is manifestly 1:N. A predicate
        // that is true of 98% of the edges is not discriminating, and this one
        // produced 23 COPYING-GAPs saying a child table "can hold at most one row
        // per parent" for tables that carry a `position` column precisely so they
        // can hold many.
        //
        // The 1:1 test is about the CHILD's own key: a parent holds at most one
        // child row only if the child's key adds NOTHING to the parent's. That is
        // the question, and it is the child's key that answers it.
        $childKeyWire = $splitKey($schema['primaryKey'][$child] ?? [])['wire'];
        $oneToOne = $childKeyWire !== [] && $childKeyWire === $fk['columns'];

        $uniqueOnFk = false;
        foreach ($schema['indexes'][$child] ?? [] as $index) {
            if ($index['unique'] && array_slice($index['columns'], 0, count($fk['columns'])) === $fk['columns']) {
                $uniqueOnFk = true;
                break;
            }
        }
        if ($positioned && $oneToOne) {
            finding(
                $findings, 'C6', 'COPYING-GAP', $child, "fk:{$child}->{$parent}",
                'the wire field is a `Vector<...>`, so the parent holds MANY of these and order is part of '
                    .'the value (the element index is the ordinal)',
                'the child key is exactly the parent key, so at most one row can exist per parent',
                'add the ordinal the Vector carries to the child key, so the Nth element is a distinct row',
            );

            continue;
        }
        if (! $positioned && $oneToOne && ! $uniqueOnFk) {
            finding(
                $findings, 'C6', 'UNEXPLAINED', $child, "fk:{$child}->{$parent}",
                'the wire field is a single object, so the parent holds at most ONE of these',
                'the child key is the parent key but nothing UNIQUE constrains the legs, so nothing in the '
                    .'schema states that a second row is impossible',
                'add a UNIQUE index over ['.implode(',', $fk['columns']).'] — a 1:1 the database does not '
                    .'constrain is a comment',
            );
        }
    }
}

// ---------------------------------------------------------------------
// C7 — key shape: does our primary key match the identity Telegram uses?
// ---------------------------------------------------------------------

$c7 = ['id' => 'C7', 'name' => 'primary key against the wire identity', 'measurable' => true, 'tables' => 0];

if ($inScope('C7')) {
    foreach ($mirrorTables as $table) {
        $actual = $schema['primaryKey'][$table] ?? [];
        if ($actual !== []) {
            $c7['tables']++;
        }
        $expected = $resolverTables[$table]->keyColumns ?? null;
        if ($expected === null) {
            continue;
        }

        // ONE normalisation, of the ONE executed key. Never of a derived list.
        $split = $splitKey($actual);
        $actualWire = $split['wire'];

        // THE VECTOR ORDINAL IS PART OF THE KEY, BY A NAMED FACT.
        //
        // A TL `Vector<X>` is a list, and the wire gives every element its index;
        // order IS part of the value, so the Nth element and the Mth element are
        // different things even when their payloads are identical. A relational
        // store has no array type, so the list becomes a child table — and the
        // element index has to become a COLUMN, because there is nowhere else to
        // keep it. That much is a storage translation and is not negotiable.
        //
        // The step that makes it work is that the index must also be in the KEY.
        // A child table keyed only by its parent's identity can hold at most one
        // row per parent, so a three-element vector silently collapses to one row
        // and two elements are lost with no error anywhere — which is the same
        // failure as the false sibling key that lost every `messageService` row.
        //
        // So when the executed key carries an ordinal the resolver's derived
        // identity does not, that is the storage translation being applied
        // correctly, NOT a divergence, and recommending its removal would be the
        // destructive move this check already made once. The verdict is
        // STORAGE-TRANSLATION with the fact named, per family.
        $ordinal = array_values(array_filter(
            $actualWire,
            static fn (string $c): bool => $c === 'position' || str_ends_with($c, '_position'),
        ));
        if ($ordinal !== [] && $expected !== [] && $actualWire !== $expected) {
            $identity = array_values(array_filter($actualWire, static fn (string $c): bool => ! in_array($c, $ordinal, true)));
            if ($identity === $expected) {
                finding(
                    $findings, 'C7', 'STORAGE-TRANSLATION', $table, 'key:'.implode('+', $actual),
                    'the wire field is a `Vector<...>`, and a list has no array type in a relational store, so '
                        .'the element index becomes the column `'.implode('`, `', $ordinal).'`',
                    'the executed key is ['.implode(', ', $actual).'] — the identity ['.implode(', ', $expected)
                        .'] plus that ordinal'.($split['scoped'] ? ', tenant-scoped' : ''),
                    'none — this is the standard mapping. The ordinal MUST stay in the key: without it a parent '
                        .'can hold one row per vector, so element 2 and element 3 of a three-element vector '
                        .'become the same row and two elements are lost without an error. Do NOT remove it.',
                    'A relational store has no array type, so a `Vector<X>` must become a child table, and the '
                        .'element index — which is part of the wire value — must become a column that is part of '
                        .'the key, or the list cannot be represented at all.',
                );

                continue;
            }
        }

        // A table the wire gives an identity to, keyed on the tenant scope alone,
        // is the "no key" case and it IS a real finding — but it is a different
        // finding from a key that merely names its columns in another order, and
        // it must never be reported as an empty identity.
        if ($actualWire === [] && $expected !== []) {
            finding(
                $findings, 'C7', 'UNEXPLAINED', $table, 'key:'.implode('+', $actual),
                'the catalog and the resolver derive this table\'s identity as ['.implode(', ', $expected).']',
                'the executed primary key is ['.implode(', ', $actual).'] — the tenant scope and nothing else, '
                    .'so no row is distinguishable from another row of the same account',
                'add '.($expected === ['id'] ? '`id`' : '['.implode(', ', $expected).']').' to the key after '
                    .'`'.$tenantColumn.'`; the tenant scope STAYS — it is what makes the key unique at all',
            );

            continue;
        }

        if ($actualWire === $expected) {
            continue;
        }

        finding(
            $findings, 'C7', 'UNEXPLAINED', $table, 'key:'.implode('+', $actual),
            'the catalog and the resolver derive this table\'s identity as ['.implode(', ', $expected).']',
            'the executed primary key is ['.implode(', ', $actual).'], whose wire columns are ['
                .implode(', ', $actualWire).']'
                .($split['scoped'] ? ' (tenant-scoped: `'.$tenantColumn.'` leads the key and scopes it)' : ''),
            'reconcile the key column list with ['.implode(', ', $expected).'] PRESERVING the engine\'s own order, '
                .'and keep `'.$tenantColumn.'` in front of it — a composite key\'s ORDER is the order a foreign key '
                .'must be declared in, so the order is not cosmetic, and dropping the tenant column would make the '
                .'key non-unique across accounts',
        );
    }
}

// ---------------------------------------------------------------------
// Verdict the residual, then publish.
// ---------------------------------------------------------------------

applyDeclarations($findings, $declarations);

$checks = [$c1, $c2, $c3, $c4, $c5, $c6, $c7];

$counts = ['comparisons' => count($findings), 'COPYING-GAP' => 0, 'STORAGE-TRANSLATION' => 0, 'DELIBERATE' => 0, 'UNEXPLAINED' => 0];
foreach ($findings as $f) {
    $counts[$f['verdict']]++;
}
$counts['faithful'] = 0;

$unattributedCount = count($unattributed);

/**
 * Split the UNEXPLAINED residual into the two claims it used to conflate.
 *
 * This is a MEASUREMENT of the audit, not of the schema, and it is the single
 * number that says whether a reader should act on the residual at all. Measured
 * on the pre-repair run: 255 unexplained, of which this tool could have read the
 * authority for NONE — it reported the artifact's silence as the specification's.
 */
$blindCount = 0;
$silentCount = 0;
$blindParents = [];
foreach ($findings as $f) {
    if ($f['verdict'] !== 'UNEXPLAINED') {
        continue;
    }
    if (str_contains($f['telegram'], 'holds NO TL type')) {
        $blindCount++;
        // The parent table is named in the middle of the sentence; take it from the
        // finding's own table/subject rather than re-deriving it, so this count can
        // never disagree with the rows it summarises.
        if (preg_match('/holds NO TL type for `([\w]+)`/', $f['telegram'], $m) === 1) {
            $blindParents[$m[1]] = true;
        }
    } else {
        $silentCount++;
    }
}
$blindParents = array_keys($blindParents);
sort($blindParents);

$payload = [
    'tool' => 'bin/audit-fidelity.php',
    'engine' => $options['engine'],
    'scope' => $options['scope'],
    'authorities' => [
        'tl' => 'schema/sources/TL_telegram_v227.tl',
        'catalog' => 'src/Schema/schema/mirror-catalog.json',
        'executed_schema' => $options['engine'] === 'mysql'
            ? 'information_schema.{COLUMNS,STATISTICS,KEY_COLUMN_USAGE,REFERENTIAL_CONSTRAINTS}'
            : 'sqlite_master + pragma_{table_info,index_list,index_info,foreign_key_list}',
    ],
    'measured' => [
        'tables' => count($schema['tables']),
        'mirror_tables' => count($mirrorTables),
        'columns' => array_sum(array_map('count', $schema['columns'])),
        'foreign_keys' => count($schema['foreignKeys']),
        'catalog_entries' => count($catalog->tableNames()),
        'resolver_tables' => count($resolverTables),
        'attributed_columns' => $c3['compared'] + $c4['compared'],
        'unattributed_columns' => $unattributedCount,
        // HOW MANY PRIMARY KEYS THIS RUN ACTUALLY READ.
        //
        // Published because its absence is invisible and its absence is fatal: the
        // reader used to look for an index NAMED `primary`, which SQLite never
        // produces (it names it `sqlite_autoindex_<table>_<n>` and sets
        // `origin='pk'`), so the key map came back EMPTY for every table and the
        // key check reported 194 tables whose keys it had never read. An audit that
        // publishes how many keys it read cannot make that mistake quietly.
        'tables_with_primary_key' => count(array_filter(
            $schema['primaryKey'],
            static fn (array $key): bool => $key !== [],
        )),
    ],
    'counts' => $counts,
    'checks' => $checks,
    'findings' => $findings,
    'authority_coverage' => [
        'mirror_tables_without_catalog_or_resolver_provenance' => count(array_diff(
            $mirrorTables,
            array_keys($resolverTables),
        )),
        'mirror_tables_without_catalog_entry' => count(array_filter(
            $mirrorTables,
            static fn (string $t): bool => ! $catalog->has($t),
        )),
        // HOW MANY UNEXPLAINED ROWS ARE THE AUDIT'S OWN BLINDNESS, split out from
        // the ones where an authority was actually read and stayed silent.
        //
        // These two are not the same claim and must never be counted as one.
        // "Neither authority states this descent" is a statement about Telegram.
        // "This audit holds no TL type for the parent" is a statement about THIS
        // TOOL, and it is a confession. Lumping them together is what let a run
        // report "255 unexplained" when 255 of them were the tool failing to see
        // and not the schema failing to match.
        //
        // The blind bucket is not hypothetical either: hand-checking the 19
        // `tf_channels_*` edges against TL line 104 (`channel#1c32b11c = Chat`)
        // finds 11 faithful copies and 8 real over-decompositions, where the TL
        // declares a plain `long`/`int`/`string` and we made a child TABLE. So
        // the blind bucket contains BOTH kinds, indistinguishable, and its size
        // is a measure of what this audit cannot see.
        'unexplained_audit_blind' => $blindCount,
        'unexplained_authorities_read' => $silentCount,
        'blind_parent_tables' => $blindParents,
        'unattributed_columns' => $unattributed,
    ],
];

$canonical = $findings;
usort($canonical, static fn (array $a, array $b): int => [$a['check'], $a['table'], $a['subject'], $a['verdict']] <=> [$b['check'], $b['table'], $b['subject'], $b['verdict']]);
$payload['digest'] = sha1((string) json_encode($canonical));

// ---- text output ------------------------------------------------------

$out = static function (string $line) use ($options): void {
    if ($options['format'] === 'json') {
        return;
    }
    fwrite(STDOUT, $line."\n");
};

$out('');
$out('FIDELITY AUDIT — shipped relational schema against Telegram '.($options['engine'] === 'mysql'
    ? '(MySQL information_schema: width AND signedness measurable)'
    : '(SQLite: width and signedness NOT measurable — re-run with --engine=mysql for that verdict)'));
$out('');
$out('measured  tables='.$payload['measured']['tables']
    .'  mirror='.$payload['measured']['mirror_tables']
    .'  columns='.$payload['measured']['columns']
    .'  foreign_keys='.$payload['measured']['foreign_keys']
    .'  catalog_entries='.$payload['measured']['catalog_entries']
    .'  resolver_tables='.$payload['measured']['resolver_tables']);
$out('verdicts  comparisons='.$counts['comparisons']
    .'  COPYING-GAP='.$counts['COPYING-GAP']
    .'  STORAGE-TRANSLATION='.$counts['STORAGE-TRANSLATION']
    .'  DELIBERATE='.$counts['DELIBERATE']
    .'  UNEXPLAINED='.$counts['UNEXPLAINED']);
$out('');

foreach ($checks as $check) {
    $out(sprintf(
        '  %-3s %-52s %s',
        $check['id'],
        $check['name'],
        $check['measurable'] ? '' : 'NOT MEASURABLE ON THIS ENGINE',
    ));
}
$out('');

$byVerdict = ['COPYING-GAP' => [], 'UNEXPLAINED' => [], 'STORAGE-TRANSLATION' => [], 'DELIBERATE' => []];
foreach ($findings as $f) {
    $byVerdict[$f['verdict']][] = $f;
}
foreach (['COPYING-GAP', 'UNEXPLAINED', 'STORAGE-TRANSLATION', 'DELIBERATE'] as $verdict) {
    $rows = $byVerdict[$verdict];
    if ($rows === []) {
        continue;
    }
    $out($verdict.' ('.count($rows).')');
    $shown = $verdict === 'STORAGE-TRANSLATION' || $verdict === 'DELIBERATE' ? 5 : 200;
    foreach (array_slice($rows, 0, $shown) as $f) {
        $out(sprintf('  %-3s %-34s %-30s %s', $f['check'], $f['table'], $f['subject'], $f['telegram']));
        $out('      ours: '.$f['ours']);
        $out('      move: '.$f['minimal_move']);
        if ($f['fact'] !== '') {
            $out('      fact: '.$f['fact']);
        }
    }
    if (count($rows) > $shown) {
        $out('  … and '.(count($rows) - $shown).' more — see docs/schema/fidelity-audit.md');
    }
    $out('');
}

if ($unattributedCount > 0) {
    $out('columns with NO derivable TL provenance: '.$unattributedCount
        .' — listed in the report under authority coverage; a column nobody can attribute to a wire'
        .' field is a column nobody has checked against Telegram');
    $out('');
}

// The blind/read split is printed ABOVE the verdict line on purpose. A reader who
// sees only "76 UNEXPLAINED" cannot tell how many are suspicions about the schema
// and how many are this tool reporting that it cannot see. Those are opposite
// instructions to the reader, so the number that separates them is not a footnote.
$out('UNEXPLAINED '.$counts['UNEXPLAINED'].' splits into two DIFFERENT claims:');
$out('  · '.$silentCount.' read the authority and it stayed silent — a statement about Telegram');
$out('  · '.$blindCount.' are this AUDIT\'s blindness: it holds no TL type for the parent table');
if ($blindParents !== []) {
    $out('    blind parents ('.count($blindParents).'): '.implode(', ', $blindParents));
    $out('    a blind row is a confession, not an accusation. It may NOT be acted on, and it hides');
    $out('    both faithful constraints AND real gaps — hand-checking the tf_channels_* family found');
    $out('    11 faithful and 8 real over-decompositions in a bucket that looks identical.');
}
$out('');

if ($options['format'] === 'json') {
    fwrite(STDOUT, (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
}

// ---- the generated report --------------------------------------------

$reportPath = $root.'/docs/schema/fidelity-audit.md';

$markdown = static function (array $payload, array $byVerdict, array $unattributed, array $mirrorTables, MirrorCatalog $catalog, array $resolverTables): string {
    $md = '';
    $md .= "# Fidelity audit — the shipped schema against Telegram's data model\n\n";
    $md .= "GENERATED by `bin/audit-fidelity.php`. Do not hand-edit: `tests/Schema/FidelityAuditIsCurrentTest.php`\n";
    $md .= "compares the block at the bottom of this file against a fresh run, so an edit here is either\n";
    $md .= "reverted by the gate or the gate is wrong.\n\n";
    $md .= "Regenerate with:\n\n```\nphp bin/audit-fidelity.php --engine=mysql\n```\n\n";

    $md .= "## What is compared, and against which authority\n\n";
    $md .= "| side | authority | read how |\n| --- | --- | --- |\n";
    $md .= "| Telegram | `schema/sources/TL_telegram_v227.tl` | `TlParser::parseFile()` |\n";
    $md .= "| Telegram routing | `src/Schema/schema/mirror-catalog.json` | `MirrorCatalog::load()` — ctor→table, and therefore parent/child and sibling |\n";
    $md .= '| ours | the EXECUTED shipped dial | '.$payload['authorities']['executed_schema']." |\n\n";
    $md .= "A migration docblock is not an authority, and this session proved a docblock and the engine can\n";
    $md .= "disagree. A name-prefix or key-shape heuristic is not an authority either: where one disagrees\n";
    $md .= "with the catalog, the catalog wins and the heuristic is the defect.\n\n";

    $md .= "## Headline\n\n";
    $md .= "| measure | value |\n| --- | --- |\n";
    $md .= '| engine | `'.$payload['engine']."` |\n";
    foreach ($payload['measured'] as $k => $v) {
        $md .= "| {$k} | ".(is_array($v) ? count($v) : $v)." |\n";
    }
    foreach ($payload['counts'] as $k => $v) {
        $md .= "| {$k} | {$v} |\n";
    }
    $md .= "\n";

    $md .= "## The checks\n\n";
    $md .= "| id | check | measurable on this engine |\n| --- | --- | --- |\n";
    foreach ($payload['checks'] as $check) {
        $md .= "| {$check['id']} | {$check['name']} | ".($check['measurable'] ? 'yes' : '**no**')." |\n";
    }
    $md .= "\n";

    $md .= '## COPYING-GAP ('.count($byVerdict['COPYING-GAP']).") — we differ from Telegram and there is no storage reason\n\n";
    if ($byVerdict['COPYING-GAP'] === []) {
        $md .= "_none measured_\n\n";
    } else {
        $md .= "| check | table | column / relation | what Telegram declares | what we do | minimal move toward the source |\n";
        $md .= "| --- | --- | --- | --- | --- | --- |\n";
        foreach ($byVerdict['COPYING-GAP'] as $f) {
            $md .= "| {$f['check']} | `{$f['table']}` | `{$f['subject']}` | ".mdCell($f['telegram'])
                .' | '.mdCell($f['ours']).' | '.mdCell($f['minimal_move'])." |\n";
        }
        $md .= "\n";
    }

    $md .= '## UNEXPLAINED ('.count($byVerdict['UNEXPLAINED']).") — a divergence nothing names a fact for\n\n";
    $md .= "This is the only bucket that makes the tool exit non-zero. A row here is not a verdict: it is\n";
    $md .= "the absence of one.\n\n";

    // The residual is TWO claims, and a reader who sees only the total cannot tell
    // them apart: one is a statement about Telegram, the other is a confession
    // about this tool. The pre-repair run reported 255 unexplained when all 255
    // were the second kind — the tool could not see the parent's TL type and
    // reported that blindness as though the specification were silent. Every one
    // of those 255 was a faithful constraint, and the recommendation attached to
    // each of them was to delete it.
    $cov = $payload['authority_coverage'];
    $md .= "| residual split | rows | what it means |\n| --- | --- | --- |\n";
    $md .= '| read the authority, it stayed silent | '.$cov['unexplained_authorities_read']
        ." | a statement about **Telegram** — an authority was consulted and does not declare this |\n";
    $md .= '| **this audit is blind** | '.$cov['unexplained_audit_blind']
        ." | a confession about **the tool** — it holds no TL type for the parent, so it did not look |\n\n";
    if ($cov['blind_parent_tables'] !== []) {
        $md .= 'Blind parent tables ('.count($cov['blind_parent_tables']).'): `'
            .implode('`, `', $cov['blind_parent_tables'])."`\n\n";
        $md .= "> A blind row is a confession, not an accusation, and **must not be acted on**. It also is not\n";
        $md .= "> safe to ignore: the blind bucket hides faithful constraints AND real gaps, indistinguishably.\n";
        $md .= "> Hand-checking the `tf_channels_*` family against TL line 104 (`channel#1c32b11c = Chat`)\n";
        $md .= "> found **11 faithful** descents and **8 real over-decompositions** — fields the TL declares as\n";
        $md .= "> a plain `long`/`int`/`string` and we made a child TABLE for. Declaring these eight parents in\n";
        $md .= "> the catalog is what converts the bucket from unknown to judged.\n\n";
    }

    if ($byVerdict['UNEXPLAINED'] === []) {
        $md .= "_none measured_\n\n";
    } else {
        $md .= "| check | table | column / relation | what Telegram declares | what we do | minimal move toward the source |\n";
        $md .= "| --- | --- | --- | --- | --- | --- |\n";
        foreach ($byVerdict['UNEXPLAINED'] as $f) {
            $md .= "| {$f['check']} | `{$f['table']}` | `{$f['subject']}` | ".mdCell($f['telegram'])
                .' | '.mdCell($f['ours']).' | '.mdCell($f['minimal_move'])." |\n";
        }
        $md .= "\n";
    }

    $md .= '## STORAGE-TRANSLATION ('.count($byVerdict['STORAGE-TRANSLATION']).") — each with the database-design fact that requires it\n\n";
    if ($byVerdict['STORAGE-TRANSLATION'] === []) {
        $md .= "_none measured_\n\n";
    } else {
        $md .= "| check | table | column / relation | what Telegram declares | what we do | the fact |\n";
        $md .= "| --- | --- | --- | --- | --- | --- |\n";
        foreach ($byVerdict['STORAGE-TRANSLATION'] as $f) {
            $md .= "| {$f['check']} | `{$f['table']}` | `{$f['subject']}` | ".mdCell($f['telegram'])
                .' | '.mdCell($f['ours']).' | '.mdCell($f['fact'])." |\n";
        }
        $md .= "\n";
    }

    $md .= '## DELIBERATE ('.count($byVerdict['DELIBERATE']).") — the reason is recorded in the file that does it\n\n";
    if ($byVerdict['DELIBERATE'] === []) {
        $md .= "_none measured_\n\n";
    } else {
        $md .= "| check | table | column / relation | what Telegram declares | what we do | the recorded reason |\n";
        $md .= "| --- | --- | --- | --- | --- | --- |\n";
        foreach ($byVerdict['DELIBERATE'] as $f) {
            $md .= "| {$f['check']} | `{$f['table']}` | `{$f['subject']}` | ".mdCell($f['telegram'])
                .' | '.mdCell($f['ours']).' | '.mdCell($f['fact'])." |\n";
        }
        $md .= "\n";
    }

    $md .= "## Authority coverage — what the catalog does not reach\n\n";
    $md .= "A table the catalog does not route, and a column no TL field can be named for, are both places\n";
    $md .= "where nothing in this repository is checking us against Telegram. They are counted here because\n";
    $md .= "an unmeasured table is not a verified one.\n\n";
    $md .= '- mirror tables with no catalog entry and no resolver provenance: '
        .$payload['authority_coverage']['mirror_tables_without_catalog_or_resolver_provenance']."\n";
    $md .= '- mirror tables with no catalog entry: '
        .$payload['authority_coverage']['mirror_tables_without_catalog_entry']."\n";
    $md .= '- columns no TL field could be attributed to: '.$payload['measured']['unattributed_columns']."\n\n";
    if ($unattributed !== []) {
        $md .= "<details><summary>the unattributed columns</summary>\n\n";
        foreach (array_chunk($unattributed, 8) as $chunk) {
            $md .= '- `'.implode('`, `', $chunk)."`\n";
        }
        $md .= "\n</details>\n\n";
    }

    $md .= "## Machine block — what the gate compares\n\n";
    $md .= "A digest over EVERY finding, so a row edited here without regenerating is caught, including\n";
    $md .= "one of the storage translations that is listed rather than enumerated.\n\n";
    $md .= "```json fidelity-audit\n";
    $md .= json_encode([
        'counts' => $payload['counts'],
        'measured' => $payload['measured'],
        'digest' => $payload['digest'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    $md .= "```\n";

    return $md;
};

/** Markdown cell: a pipe would break the table, so it is stated, not hidden. */
function mdCell(string $text): string
{
    return str_contains($text, '|') ? str_replace('|', '\\|', $text) : $text;
}

if ($options['write'] || $options['check']) {
    $markdown = $markdown($payload, $byVerdict, $unattributed, $mirrorTables, $catalog, $resolverTables);
    // `mdCell` is a closure-level helper; bind it so the generated table can use it.
    $markdown = str_replace('mdCell(', 'mdCell(', $markdown);

    if ($options['check']) {
        $current = is_file($reportPath) ? (string) file_get_contents($reportPath) : '';
        if ($current !== $markdown) {
            fwrite(STDERR, "docs/schema/fidelity-audit.md is STALE — regenerate it with `php bin/audit-fidelity.php`\n");
            exit(EXIT_DIVERGENCE);
        }
        $out('docs/schema/fidelity-audit.md is current');
    } else {
        file_put_contents($reportPath, $markdown);
        $out('wrote docs/schema/fidelity-audit.md');
    }
}

exit($counts['UNEXPLAINED'] > 0 ? EXIT_DIVERGENCE : EXIT_CLEAN);
