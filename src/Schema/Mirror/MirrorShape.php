<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\ModelGenerator;
use PDO;
use RuntimeException;

/**
 * The SHAPE OF THE SHIPPED DDL, reflected — the input
 * {@see ModelGenerator} builds
 * Eloquent models from.
 *
 * ## Why this class exists
 *
 * The Eloquent model layer was empty of both data and relations: 12 models
 * for 265 mirror tables, and not one `hasMany`/`belongsTo` in `src/` at all.
 * Meanwhile the shipped migrations declare 476 foreign-key constraints over
 * 794 legs, verified on both engines. The relational truth existed and none
 * of it was reachable through Eloquent.
 *
 * Closing that gap needs a column set, a per-column type, and a foreign-key
 * graph. All three must be READ FROM THE EXECUTED SCHEMA. Two reasons, both
 * paid for already:
 *
 *  - A transcribed table list is a second copy of the DDL, and a second copy
 *    drifts. The coverage number here is whatever the engine says it is, on
 *    the day it is asked.
 *  - A docblock type can disagree with the engine, and SQLite cannot see the
 *    disagreement at all: it reports `integer` for `integer()`, `bigInteger()`
 *    and `tinyInteger()` alike. Measured on this dial, SQLite sees SIX
 *    distinct declared types across 2043 mirror columns while MySQL 8.4
 *    sees SEVENTEEN, because `COLUMN_TYPE` carries width and signedness
 *    that SQLite's affinity rules throw away (`tinyint(1)` vs `tinyint`,
 *    `bigint` vs `bigint unsigned`). A cast derived from SQLite is a guess.
 *
 * So this class reflects, and it reflects per engine:
 * `fromExecutedDdl()` speaks `PRAGMA` to SQLite and `information_schema` to
 * MySQL, driven by the driver's own name — not by a flag someone can forget
 * to set.
 *
 * ## Why there is also an artifact
 *
 * Reflecting MySQL is correct but slow: the shipped dial is 55 migrations and
 * about 100 seconds against a live server, where SQLite is under 10. Eloquent
 * casts cannot be guessed from SQLite, so regeneration needs MySQL-measured
 * types — but `artisan teleframe:regenerate` must not require a live MySQL
 * server, so it must not be the thing that boots one. Hence
 * {@see write()} and {@see load()}: the reflection is measured once by
 * `bin/generate-mirror-shape.php` (which boots BOTH engines, asserts they
 * agree on the foreign-key graph, and records the verdict in the file) and
 * read back afterwards.
 *
 * ## The one rule this class exists to enforce
 *
 * A relation is emitted ONLY from a constraint the engine declares. A
 * matching name prefix is not evidence, and treating it as evidence has
 * already cost this repository real data: `tf_messages_service` shares its
 * prefix with `tf_messages` but is stored as a SIBLING, and inferring the
 * parent from the prefix produced a false relation that discarded every
 * `messageService` update. `foreignKeys()` returns declarations and nothing
 * else, so there is no heuristic available to reach for.
 */
final class MirrorShape
{
    /**
     * @param array<string,array{
     *     columns: array<string,array{type:string,nullable:bool,primary:bool,ordinal:int}>,
     *     primary_key: list<string>,
     *     foreign_keys: list<array{name:string,from:list<string>,to_table:string,to:list<string>,on_delete:string}>
     * }> $tables
     */
    private function __construct(private readonly array $tables) {}

    /**
     * Reflect a LIVE schema through `PDO`, choosing the dialect from the
     * driver's own name.
     *
     * @param  bool  $mirrorOnly  restrict to `tf_`-prefixed tables
     */
    public static function fromExecutedDdl(PDO $pdo, bool $mirrorOnly = true): self
    {
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        return match ($driver) {
            'sqlite' => self::fromSqlite($pdo, $mirrorOnly),
            'mysql' => self::fromMysql($pdo, $mirrorOnly),
            default => throw new RuntimeException("MirrorShape cannot reflect driver [{$driver}]."),
        };
    }

    /**
     * SQLite: `PRAGMA table_info` for columns, `PRAGMA foreign_key_list` for
     * constraints. Foreign-key rows arrive ONE PER COLUMN, so they must be
     * grouped by `id` before they mean anything — counting rows counts legs.
     */
    private static function fromSqlite(PDO $pdo, bool $mirrorOnly): self
    {
        $tables = [];

        foreach (self::sqliteTableNames($pdo, $mirrorOnly) as $table) {
            $columns = [];
            $keyColumns = [];

            foreach ($pdo->query('PRAGMA table_info('.$pdo->quote($table).')')->fetchAll(PDO::FETCH_ASSOC) as $column) {
                $name = (string) $column['name'];
                $columns[$name] = [
                    // SQLite's affinity collapse: this is a class, not a type.
                    // Kept verbatim so callers can SEE the blindness rather
                    // than inherit it — `casts()` must not be built from this.
                    'type' => (string) $column['type'],
                    'nullable' => (int) $column['notnull'] === 0,
                    'primary' => (int) $column['pk'] > 0,
                    'ordinal' => (int) $column['cid'],
                ];

                // `pk` is the column's 1-based position WITHIN the primary
                // key, so it is key order, not a boolean.
                if ((int) $column['pk'] > 0) {
                    $keyColumns[] = ['ordinal' => (int) $column['pk'], 'name' => $name];
                }
            }

            usort($keyColumns, static fn (array $a, array $b): int => $a['ordinal'] <=> $b['ordinal']);

            $primaryKey = [];
            foreach ($keyColumns as $keyColumn) {
                $primaryKey[] = $keyColumn['name'];
            }

            $tables[$table] = [
                'columns' => $columns,
                'primary_key' => $primaryKey,
                'foreign_keys' => self::groupSqliteForeignKeys(
                    $pdo->query('PRAGMA foreign_key_list('.$pdo->quote($table).')')->fetchAll(PDO::FETCH_ASSOC),
                ),
            ];
        }

        ksort($tables);

        return new self($tables);
    }

    /**
     * MySQL: `information_schema`. `COLUMN_TYPE` is read, never `DATA_TYPE` —
     * `DATA_TYPE` drops width and signedness, which is precisely the
     * information the cast depends on and precisely what SQLite already lost.
     *
     * @param  list<array<string,mixed>>  $rows
     * @return list<array{name:string,from:list<string>,to_table:string,to:list<string>,on_delete:string}>
     */
    private static function groupMysqlForeignKeys(array $rows, string $table): array
    {
        $deleteRule = [];
        foreach ($rows as $row) {
            $name = (string) $row['CONSTRAINT_NAME'];
            if (! isset($deleteRule[$name])) {
                $deleteRule[$name] = (string) ($row['DELETE_RULE'] ?? 'NO ACTION');
            }
        }

        $grouped = [];
        foreach ($rows as $row) {
            $name = (string) $row['CONSTRAINT_NAME'];
            $grouped[$name] ??= [
                'name' => $name,
                'from' => [],
                'to_table' => (string) $row['REFERENCED_TABLE_NAME'],
                'to' => [],
                'on_delete' => $deleteRule[$name] ?? 'NO ACTION',
            ];
            $grouped[$name]['from'][] = (string) $row['COLUMN_NAME'];
            $grouped[$name]['to'][] = (string) $row['REFERENCED_COLUMN_NAME'];
        }

        ksort($grouped);

        return array_values($grouped);
    }

    private static function fromMysql(PDO $pdo, bool $mirrorOnly): self
    {
        $schemaFilter = $mirrorOnly ? " AND TABLE_NAME LIKE 'tf\\_%'" : '';

        $columnRows = $pdo->query(
            'SELECT TABLE_NAME AS t, COLUMN_NAME AS c, COLUMN_TYPE AS ty, IS_NULLABLE AS nl,'
            .' COLUMN_KEY AS k, ORDINAL_POSITION AS o'
            .' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()'
            .$schemaFilter
            .' ORDER BY TABLE_NAME, ORDINAL_POSITION'
        )->fetchAll(PDO::FETCH_ASSOC);

        $tables = [];
        foreach ($columnRows as $row) {
            $table = (string) $row['t'];
            $tables[$table]['columns'][(string) $row['c']] = [
                'type' => (string) $row['ty'],
                'nullable' => (string) $row['nl'] === 'YES',
                'primary' => (string) $row['k'] === 'PRI',
                'ordinal' => (int) $row['o'],
            ];
            $tables[$table]['primary_key'] ??= [];
            $tables[$table]['foreign_keys'] ??= [];
        }

        $primaryRows = $pdo->query(
            'SELECT TABLE_NAME AS t, COLUMN_NAME AS c'
            .' FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()'
            .' AND INDEX_NAME = '.$pdo->quote('PRIMARY')
            .$schemaFilter
            .' ORDER BY TABLE_NAME, SEQ_IN_INDEX'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($primaryRows as $row) {
            if (isset($tables[(string) $row['t']])) {
                $tables[(string) $row['t']]['primary_key'][] = (string) $row['c'];
            }
        }

        $fkRows = $pdo->query(
            'SELECT k.TABLE_NAME AS t, k.CONSTRAINT_NAME AS CONSTRAINT_NAME, k.COLUMN_NAME,'
            .' k.REFERENCED_TABLE_NAME AS REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME AS REFERENCED_COLUMN_NAME,'
            .' r.DELETE_RULE AS DELETE_RULE'
            .' FROM information_schema.KEY_COLUMN_USAGE k'
            .' JOIN information_schema.REFERENTIAL_CONSTRAINTS r'
            .'   ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME'
            .' AND r.TABLE_NAME = k.TABLE_NAME'
            .' WHERE k.TABLE_SCHEMA = DATABASE()'
            .' AND k.REFERENCED_TABLE_NAME IS NOT NULL'
            // Qualified with `k.` because the join brings REFERENTIAL_CONSTRAINTS'
            // own TABLE_NAME into scope, and MySQL rejects the bare name.
            .str_replace('TABLE_NAME', 'k.TABLE_NAME', $schemaFilter)
            .' ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION'
        )->fetchAll(PDO::FETCH_ASSOC);

        $byTable = [];
        foreach ($fkRows as $row) {
            $byTable[(string) $row['t']][] = $row;
        }
        ksort($byTable);

        foreach ($byTable as $table => $rows) {
            if (isset($tables[$table])) {
                $tables[$table]['foreign_keys'] = self::groupMysqlForeignKeys($rows, $table);
            }
        }

        ksort($tables);

        return new self($tables);
    }

    /**
     * @param  list<array<string,mixed>>  $rows
     * @return list<array{name:string,from:list<string>,to_table:string,to:list<string>,on_delete:string}>
     */
    private static function groupSqliteForeignKeys(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $id = (string) $row['id'];
            $grouped[$id] ??= [
                // SQLite exposes no constraint name, so the owning table and
                // the constraint's ordinal in `foreign_key_list` stand in for
                // one. Stable within a schema, never quoted as a real name.
                'name' => 'fk_'.$id,
                'from' => [],
                'to_table' => (string) $row['table'],
                'to' => [],
                'on_delete' => (string) $row['on_delete'],
            ];
            $grouped[$id]['from'][] = (string) $row['from'];
            $grouped[$id]['to'][] = (string) $row['to'];
        }
        ksort($grouped);

        return array_values($grouped);
    }

    /** @return list<string> */
    private static function sqliteTableNames(PDO $pdo, bool $mirrorOnly): array
    {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite\\_%' ESCAPE '\\'";
        if ($mirrorOnly) {
            $sql .= " AND name LIKE 'tf\\_%' ESCAPE '\\'";
        }
        $sql .= ' ORDER BY name';

        return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    // -----------------------------------------------------------------
    // The artifact form. Measured once, read many times.
    // -----------------------------------------------------------------

    /**
     * Serialise for the committed artifact, stamping the provenance a reader
     * needs in order to trust the numbers: which engine produced the types,
     * and what it measured.
     *
     * @param  array<string,string|int|bool>  $provenance
     */
    public function toArray(array $provenance = []): array
    {
        return [
            '_generated' => 'MirrorShape — MEASURED from the shipped DDL, not transcribed.'
                .' Regenerate with: php bin/generate-mirror-shape.php (boots both engines).',
            '_provenance' => $provenance,
            'tables' => $this->tables,
        ];
    }

    /**
     * Read the committed artifact back.
     */
    public static function load(string $path): self
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("MirrorShape artifact not readable: {$path}");
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (! isset($decoded['tables']) || ! is_array($decoded['tables'])) {
            throw new RuntimeException("MirrorShape artifact has no `tables` object: {$path}");
        }

        return new self($decoded['tables']);
    }

    /** Default location of the committed artifact. */
    public static function artifactPath(): string
    {
        return dirname(__DIR__).'/schema/mirror-shape.json';
    }

    // -----------------------------------------------------------------
    // Queries.
    // -----------------------------------------------------------------

    /** @return list<string> every reflected table, ksort order */
    public function tableNames(): array
    {
        return array_keys($this->tables);
    }

    public function has(string $table): bool
    {
        return isset($this->tables[$table]);
    }

    /**
     * @return array<string,array{type:string,nullable:bool,primary:bool,ordinal:int}>
     */
    public function columns(string $table): array
    {
        if (! isset($this->tables[$table])) {
            throw new RuntimeException("Unknown mirror table: {$table}");
        }

        return $this->tables[$table]['columns'];
    }

    /** @return list<string> column names in declaration order */
    public function columnNames(string $table): array
    {
        $columns = $this->columns($table);
        $names = array_keys($columns);
        // Declaration order is what the engine reported; restore it rather
        // than relying on the JSON round trip to have preserved it.
        usort($names, static fn (string $a, string $b): int => $columns[$a]['ordinal'] <=> $columns[$b]['ordinal']);

        return $names;
    }

    public function columnType(string $table, string $column): ?string
    {
        return $this->tables[$table]['columns'][$column]['type'] ?? null;
    }

    /** @return list<string> primary-key columns, in key order */
    public function primaryKey(string $table): array
    {
        return $this->tables[$table]['primary_key'] ?? [];
    }

    /**
     * Constraints the engine DECLARES on `$table`. Nothing else reaches a
     * relation — see the class docblock.
     *
     * @return list<array{name:string,from:list<string>,to_table:string,to:list<string>,on_delete:string}>
     */
    public function foreignKeys(string $table): array
    {
        return $this->tables[$table]['foreign_keys'] ?? [];
    }

    /** @return array{tables:int,columns:int,foreign_keys:int,primary_keys:int} */
    public function counts(): array
    {
        $columns = 0;
        $foreignKeys = 0;
        $primaryKeys = 0;
        foreach ($this->tables as $table) {
            $columns += count($table['columns']);
            $foreignKeys += count($table['foreign_keys']);
            $primaryKeys += $table['primary_key'] === [] ? 0 : 1;
        }

        return [
            'tables' => count($this->tables),
            'columns' => $columns,
            'foreign_keys' => $foreignKeys,
            'primary_keys' => $primaryKeys,
        ];
    }
}
