<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlType;

/**
 * Emits Laravel migrations for the single-table-per-constructor mirror (spec §4).
 *
 * File layout (deterministic, ksort order):
 *  - one file per abstract type: one Schema::create per constructor + child tables
 *  - one file with ALL method route tables
 *  - final 9999xx files with cross-type deferred FKs (raw ALTERs), split
 *    into buckets so each migration transaction stays within stock PG's
 *    lock budget (Night W3: one 3070-ALTER file died with `out of shared
 *    memory` / max_locks_per_transaction on a default server)
 *
 * @phpstan-type ForeignKey array{table:string, column:string, target_table:string}
 */
final class MigrationGenerator
{
    public const DATE_TOKEN = '2026_08_28';

    /**
     * FK ALTERs per migration file. Each ALTER locks the altered table and
     * the referenced table for the file's whole transaction: 512 keeps a
     * bucket around ~1k relation locks, safely under stock Postgres's
     * shared lock budget (max_locks_per_transaction 64 x max_connections
     * 100 = 6400 slots), independent of host tuning.
     */
    public const FK_BUCKET_SIZE = 512;

    /** @var list<ForeignKey> */
    private array $deferredFks = [];
    /** @var array<string, string> table => migration filename */
    private array $tableMap = [];
    private string $currentFile = '';
    private string $currentTable = '';
    private TlScheme $scheme;

    /** @return array<string,string> filename => content */
    public function generate(TlScheme $scheme): array
    {
        $this->deferredFks = [];
        $this->tableMap = [];
        $this->scheme = $scheme;
        $files = [];

        $types = $scheme->types();
        ksort($types);
        $seq = 0;
        foreach ($types as $type) {
            if ($type->name === 'Vector t' || $type->constructors() === []) {
                continue; // generic instantiation / referenced-only type: no tables
            }
            $seq++;
            $this->currentFile = sprintf('%s_%06d_create_%s_tables.php', self::DATE_TOKEN, $seq, 'tl_' . Naming::snake($type->name));
            $files[$this->currentFile] = $this->typeMigration($type);
        }

        $this->currentFile = sprintf('%s_%06d_create_tl_route_tables.php', self::DATE_TOKEN, 900000 + $seq);
        $files[$this->currentFile] = $this->routeMigration($scheme);

        foreach ($this->fkMigrations() as $name => $content) {
            $files[$name] = $content;
        }

        Naming::assertUnique(array_keys($this->tableMap), 'table');
        return $files;
    }

    /** @return array{tables: array<string,string>, fk_count: int} */
    public function stats(): array
    {
        return ['tables' => $this->tableMap, 'fk_count' => count($this->deferredFks)];
    }

    /**
     * Index names the PG way: auto-derived "{table}_{col}_index" can
     * collide when one table name is a prefix of another (e.g.
     * tl_update_update_user#phone_call vs tl_update_update_user_phone#call)
     * or when Postgres truncates to 63 bytes — so every emitted index gets
     * an explicit content-addressed name guaranteed unique per (table, col).
     */
    private function indexLine(string $col): string
    {
        return "    \$table->index('{$col}', 'ix_" . substr(sha1($this->currentTable . ':' . $col), 0, 24) . "');";
    }

    private function typeMigration(TlType $type): string
    {
        $up = [];
        $down = [];

        $ctors = $type->constructors();
        ksort($ctors);

        foreach ($ctors as $ctor) {
            $this->constructorTable($type, $ctor, $up, $down);
        }

        return CodeWriter::migrationFile($up, array_reverse($down));
    }

    private function constructorTable(TlType $type, TlConstructor $ctor, array &$up, array &$down): void
    {
        $table = Naming::constructorTable($type->name, $ctor->name);
        $this->currentTable = $table;
        $this->tableMap[$table] = $this->currentFile;

        $up[] = "Schema::create('{$table}', function (Blueprint \$table) {";

        // Determine ID strategy from the constructor's params
        $idStrategy = $this->classifyId($ctor);

        match ($idStrategy) {
            'global' => $up[] = "    \$table->bigInteger('id')->primary();",   // Telegram ID is PK
            'scoped' => $up[] = "    \$table->bigIncrements('id');",           // Surrogate PK
            default  => $up[] = "    \$table->bigIncrements('id');",           // Identity-less: surrogate
        };

        $up[] = "    \$table->bigInteger('constructor_id');";
        $up[] = "    \$table->string('constructor_name', 96);";

        // Emit columns. For global-ID types the raw 'id:long' param is
        // emitted as the reserved-word alias 'tl_id' (via Naming::column)
        // so the Telegram ID lives in a queryable column separate from the
        // auto-increment PK — enabling multi-tenant anchor reuse.
        foreach ($ctor->params() as $param) {
            $this->columnLines($param, $up);
        }

        $up[] = "    \$table->bigInteger('account_id');";
        $up[] = "    \$table->timestamps();";
        $up[] = $this->indexLine('constructor_id');
        $up[] = $this->indexLine('account_id');

        // Scoped-ID types get a composite unique constraint
        if ($idStrategy === 'scoped') {
            $peerCol = $this->findPeerColumn($ctor);
            $uniqueCols = $peerCol !== null ? [$peerCol, 'account_id'] : ['account_id'];
            $up[] = "    \$table->unique(['" . implode("', '", $uniqueCols) . "'], 'ux_" . substr(sha1($table), 0, 20) . "');";
        }

        $up[] = "});";
        $down[] = "Schema::dropIfExists('{$table}');";

        // Vector child tables
        foreach ($ctor->params() as $param) {
            if ($param->kind() === 'vector') {
                $this->childTable($table, $param, $up, $down);
            }
        }
    }

    /**
     * Classify a constructor's ID strategy:
     * - 'global': has `id:long` param → Telegram ID is PK
     * - 'scoped': has `id:int` param → surrogate PK + composite unique
     * - null: no ID param → auto-increment
     */
    private function classifyId(TlConstructor $ctor): ?string
    {
        foreach ($ctor->params() as $param) {
            if ($param->kind() === 'scalar' && $param->name === 'id') {
                return $param->baseType() === 'long' ? 'global' : 'scoped';
            }
        }
        return null;
    }

    private function findPeerColumn(TlConstructor $ctor): ?string
    {
        foreach ($ctor->params() as $param) {
            if ($param->kind() === 'ref' && in_array($param->baseType(), ['Peer', 'InputPeer'], true)) {
                return Naming::column($param->name);
            }
        }
        return null;
    }

    private function childTable(string $parentTable, TlParam $param, array &$up, array &$down): void
    {
        $child = Naming::childTable($parentTable, $param->name);
        $this->currentTable = $child;
        $this->tableMap[$child] = $this->currentFile;
        $element = $param->baseType();
        $elementParam = new TlParam($param->name, $element);

        $up[] = "Schema::create('{$child}', function (Blueprint \$table) {";
        $up[] = "    \$table->bigIncrements('id');";
        $fkName = 'fk_' . substr(sha1($child . ':' . $parentTable), 0, 24);
        $up[] = "    \$table->bigInteger('parent_id')->constrained('{$parentTable}', 'id', '{$fkName}')->cascadeOnDelete();";
        $up[] = "    \$table->bigInteger('idx');";
        if (in_array($elementParam->kind(), ['scalar', 'nat', 'true'], true)) {
            $up[] = ltrim($this->scalarColumn($elementParam, 'value', true), ' ');
        } else {
            $up[] = "    \$table->bigInteger('value_id')->nullable();";
            if ($elementParam->kind() === 'ref' && $this->isFkTargetable($elementParam->baseType(), $elementParam)) {
                $this->deferredFks[] = ['table' => $child, 'column' => 'value_id', 'target_table' => $this->resolveFkTarget($elementParam->baseType())];
            }
        }
        $up[] = "    \$table->bigInteger('account_id');";
        $up[] = "    \$table->unique(['parent_id', 'idx'], 'ux_" . substr(sha1($child), 0, 20) . "');";
        $up[] = $this->indexLine('account_id');
        $up[] = "});";
        $down[] = "Schema::dropIfExists('{$child}');";
    }

    private function columnLines(TlParam $param, array &$up): void
    {
        if ($param->isFiller) {
            return;
        }
        $col = Naming::column($param->name);
        // All param columns are nullable: the anchor table (first ctor's
        // table) is shared by every constructor of the type and may be
        // created by one that lacks these columns.  The instance row
        // (same or different table) always fills the real values.
        $nullable = true;
        match ($param->kind()) {
            'nat' => $up[] = "    \$table->bigInteger('{$col}')->nullable();",
            'true' => $up[] = "    \$table->boolean('{$col}')->default(false);",
            'ref' => $this->refColumn($param, $col, $nullable, $up),
            'vector', 'generic' => null, // child tables / not stored
            default => $this->scalarColumnLines($param, $col, $nullable, $up),
        };
    }

    private function scalarColumnLines(TlParam $param, string $col, bool $nullable, array &$up): void
    {
        $line = $this->scalarColumn($param, $col, $nullable);
        $up[] = $line;
        if (str_ends_with($param->name, '_id') && str_contains($line, 'bigInteger')) {
            $up[] = $this->indexLine($col);
        }
    }

    private function refColumn(TlParam $param, string $col, bool $nullable, array &$up): void
    {
        $up[] = "    \$table->bigInteger('{$col}')" . ($nullable ? '->nullable()' : '') . ';';
        $target = $param->baseType();
        if ($this->isFkTargetable($target, $param)) {
            $this->deferredFks[] = ['table' => $this->currentTable, 'column' => $col, 'target_table' => $this->resolveFkTarget($target)];
        }
        $up[] = $this->indexLine($col);
    }

    private function isFkTargetable(string $target, TlParam $param): bool
    {
        return !$param->isAny()
            && !str_contains($target, '<')
            && !in_array($target, ['Object', 'Type', 'TLObject', 'X', 'True'], true);
    }

    private function scalarColumn(TlParam $param, string $col, bool $nullable): string
    {
        $db = Naming::dbType($param, precision: true);
        $null = $nullable ? '->nullable()' : '';
        return match ($db) {
            'integer' => "    \$table->integer('{$col}')" . $null . ';',
            'bigint' => "    \$table->bigInteger('{$col}')" . $null . ';',
            'numeric(39,0)' => "    \$table->decimal('{$col}', 39, 0)" . $null . ';',
            'numeric(78,0)' => "    \$table->decimal('{$col}', 78, 0)" . $null . ';',
            'double' => "    \$table->double('{$col}')" . $null . ';',
            'binary' => "    \$table->binary('{$col}')" . $null . ';',
            default => "    \$table->text('{$col}')" . $null . ';',
        };
    }

    /**
     * Resolve a TL type name to its default constructor table name.
     * Looks up the first constructor (ksort order) of the referenced type.
     */
    private function resolveFkTarget(string $typeName): string
    {
        $type = $this->scheme->types()[$typeName] ?? null;
        if ($type === null) {
            return Naming::constructorTable($typeName, $typeName); // fallback
        }
        $ctors = $type->constructors();
        ksort($ctors);
        $firstCtor = reset($ctors);
        return Naming::constructorTable($typeName, $firstCtor->name);
    }

    private function routeMigration(TlScheme $scheme): string
    {
        $up = [];
        $down = [];
        $methods = $scheme->methods();
        ksort($methods);
        foreach ($methods as $method) {
            $ret = $method->returnType;
            if ($ret === 'X' || str_contains($ret, '<') || $ret === 'Vector t') {
                continue; // generic wrappers / vector returns: no stable single anchor
            }
            $route = 'tl_route_' . Naming::snake($method->name);
            $this->currentTable = $route;
            $this->tableMap[$route] = $this->currentFile;
            $up[] = "Schema::create('{$route}', function (Blueprint \$table) {";
            $up[] = "    \$table->bigIncrements('id');";
            $up[] = "    \$table->string('route_id', 36)->unique();";
            $up[] = "    \$table->timestamps();";
            $up[] = "});";
            $down[] = "Schema::dropIfExists('{$route}');";
        }
        return CodeWriter::migrationFile($up, array_reverse($down));
    }

    /**
     * Cross-type FK files, DEFERRABLE INITIALLY DEFERRED (spec §4.5),
     * bucketed (FK_BUCKET_SIZE per file) to bound per-transaction lock
     * counts on Postgres.
     *
     * @return array<string,string> filename => content
     */
    private function fkMigrations(): array
    {
        $files = [];
        foreach (array_chunk($this->deferredFks, self::FK_BUCKET_SIZE) as $i => $bucket) {
            $files[sprintf('%s_%06d_add_tl_foreign_keys.php', self::DATE_TOKEN, 999901 + $i)] = $this->fkMigration($bucket);
        }

        return $files;
    }

    /** @param list<ForeignKey> $fks */
    private function fkMigration(array $fks): string
    {
        $up = ['// Cross-type foreign keys, DEFERRABLE INITIALLY DEFERRED (spec §4.5).'];
        $keys = [];
        foreach ($fks as $fk) {
            $key = Naming::fit($fk['table'] . '_' . $fk['column'] . '_foreign');
            $keys[] = $key;
            $up[] = 'DB::statement(\'ALTER TABLE ' . self::quote($fk['table']) . ' ADD CONSTRAINT ' . $key
                . ' FOREIGN KEY (' . $fk['column'] . ') REFERENCES ' . self::quote($fk['target_table'])
                . ' (id) DEFERRABLE INITIALLY DEFERRED\');';
        }
        $down = array_map(
            static fn (array $fk): string => 'DB::statement(\'ALTER TABLE ' . self::quote($fk['table']) . ' DROP CONSTRAINT IF EXISTS ' . Naming::fit($fk['table'] . '_' . $fk['column'] . '_foreign') . '\');',
            array_reverse($fks),
        );
        return CodeWriter::migrationFile($up, $down);
    }

    private static function quote(string $table): string
    {
        // SQL-standard identifier doubling: "a""b" is an embedded quote,
        // never a terminator.
        return '"' . str_replace('"', '""', $table) . '"';
    }
}
