<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlType;

/**
 * Emits Laravel migrations for the class-table-inheritance mirror (spec §4).
 *
 * File layout (deterministic, ksort order):
 *  - one file per abstract type: anchor + its instance tables + child tables
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
     * shared lock budget (max_locks_per_transaction 64 × max_connections
     * 100 = 6400 slots), independent of host tuning.
     */
    public const FK_BUCKET_SIZE = 512;

    /** @var list<ForeignKey> */
    private array $deferredFks = [];
    /** @var array<string, string> table => migration filename */
    private array $tableMap = [];
    private string $currentFile = '';
    private string $currentTable = '';

    /** @return array<string,string> filename => content */
    public function generate(TlScheme $scheme): array
    {
        $this->deferredFks = [];
        $this->tableMap = [];
        $files = [];

        $types = $scheme->types();
        ksort($types);
        $seq = 0;
        foreach ($types as $type) {
            if ($type->name === 'Vector t' || $type->constructors() === []) {
                continue; // generic instantiation / referenced-only type: no tables
            }
            $seq++;
            $this->currentFile = sprintf('%s_%06d_create_%s_table.php', self::DATE_TOKEN, $seq, Naming::anchorTable($type->name));
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
        $anchor = Naming::anchorTable($type->name);
        $this->currentTable = $anchor;
        $up = [];
        $down = [];

        // Anchor table (spec §4.1) — tenant-scoped (roadmap contract:
        // account_id on every anchor; no global singletons by telegram id).
        $up[] = "Schema::create('{$anchor}', function (Blueprint \$table) {";
        $up[] = "    \$table->uuid('id')->primary();";
        $up[] = "    \$table->bigInteger('constructor_id'); // crc32, may exceed signed i32";
        $up[] = "    \$table->string('constructor_name', 96);";
        $up[] = "    \$table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)";
        $up[] = "    \$table->timestamps();";
        $up[] = $this->indexLine('constructor_id');
        $up[] = $this->indexLine('account_id');
        $up[] = "});";
        $down[] = "Schema::dropIfExists('{$anchor}');";
        $this->tableMap[$anchor] = $this->currentFile;

        $ctors = $type->constructors();
        ksort($ctors);
        foreach ($ctors as $ctor) {
            $this->instanceTables($type, $ctor, $up, $down);
        }

        return CodeWriter::migrationFile($up, array_reverse($down));
    }

    private function instanceTables(TlType $type, TlConstructor $ctor, array &$up, array &$down): void
    {
        $anchor = Naming::anchorTable($type->name);
        $instance = Naming::instanceTable($type->name, $ctor->name);
        $this->currentTable = $instance;
        $this->tableMap[$instance] = $this->currentFile;

        $up[] = "Schema::create('{$instance}', function (Blueprint \$table) {";
        $up[] = "    \$table->foreignUuid('id')->primary()->constrained('{$anchor}')->cascadeOnDelete();";
        foreach ($ctor->params() as $param) {
            $this->columnLines($param, $up);
        }
        $up[] = "    \$table->bigInteger('account_id');";
        $up[] = "    \$table->timestamps();";
        $up[] = $this->indexLine('account_id');

        $params = $ctor->params();
        $hasScalarId = false;
        $peerCol = null;
        foreach ($params as $param) {
            if ($param->kind() === 'scalar' && $param->name === 'id') {
                $hasScalarId = true;
            }
            if ($param->kind() === 'ref' && in_array($param->baseType(), ['Peer', 'InputPeer'], true)) {
                $peerCol = Naming::column($param->name);
            }
        }
        if ($hasScalarId) {
            $uniqueCols = ['account_id'];
            if ($peerCol !== null) {
                $uniqueCols[] = $peerCol;
            }
            $uniqueCols[] = Naming::column('id');
            $up[] = "    \$table->unique(['" . implode("', '", $uniqueCols) . "'], 'ux_" . substr(sha1($instance), 0, 20) . "');";
        }

        $up[] = "});";
        $down[] = "Schema::dropIfExists('{$instance}');";

        foreach ($params as $param) {
            if ($param->kind() === 'vector') {
                $this->childTable($instance, $param, $up, $down);
            }
        }
    }

    private function columnLines(TlParam $param, array &$up): void
    {
        if ($param->isFiller) {
            return;
        }
        $col = Naming::column($param->name);
        $nullable = $param->conditional() !== null;
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
        if (in_array($param->baseType(), ['Peer', 'InputPeer'], true)) {
            $up[] = "    \$table->bigInteger('{$col}')" . ($nullable ? '->nullable()' : '') . ';';
            $up[] = $this->indexLine($col);
            return;
        }
        $up[] = "    \$table->uuid('{$col}')" . ($nullable ? '->nullable()' : '') . ';';
        $target = $param->baseType();
        if ($this->isFkTargetable($target, $param)) {
            $this->deferredFks[] = ['table' => $this->currentTable, 'column' => $col, 'target_table' => Naming::anchorTable($target)];
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

    private function childTable(string $instance, TlParam $param, array &$up, array &$down): void
    {
        $child = Naming::childTable($instance, $param->name);
        $this->currentTable = $child;
        $this->tableMap[$child] = $this->currentFile;
        $element = $param->baseType();
        $elementParam = new TlParam($param->name, $element);

        $up[] = "Schema::create('{$child}', function (Blueprint \$table) {";
        $up[] = "    \$table->uuid('id')->primary();";
        $up[] = "    \$table->foreignUuid('parent_id')->constrained('{$instance}')->cascadeOnDelete();";
        $up[] = "    \$table->bigInteger('idx');";
        if (in_array($elementParam->kind(), ['scalar', 'nat', 'true'], true)) {
            $up[] = ltrim($this->scalarColumn($elementParam, 'value', true), ' ');
        } else {
            $up[] = "    \$table->uuid('value_id')->nullable();";
            if ($elementParam->kind() === 'ref' && $this->isFkTargetable($elementParam->baseType(), $elementParam)) {
                $this->deferredFks[] = ['table' => $child, 'column' => 'value_id', 'target_table' => Naming::anchorTable($elementParam->baseType())];
            }
        }
        $up[] = "    \$table->bigInteger('account_id');";
        $up[] = "    \$table->unique(['parent_id', 'idx'], 'ux_" . substr(sha1($child), 0, 20) . "');";
        $up[] = $this->indexLine('account_id');
        $up[] = "});";
        $down[] = "Schema::dropIfExists('{$child}');";
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
            $up[] = "    \$table->uuid('id')->primary();";
            $up[] = "    \$table->uuid('route_id')->unique();";
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
