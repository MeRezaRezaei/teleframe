<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\CodeWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumn;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumnType;

final class MirrorMigrationWriter
{
    public function __construct(private readonly string $outDir) {}

    /** @param list<MirrorTable> $parents */
    public function writeAll(array $parents, string $dateStamp = '2026_09_11'): array
    {
        @mkdir($this->outDir, 0777, true);
        $allFks = [];
        $paths  = [];
        $emitted = [];

        foreach ($parents as $i => $parent) {
            $up   = [];
            $down = [];
            $this->emitTable($parent, $up, $down, $allFks, $emitted);
            foreach ($parent->children as $child) {
                $this->emitTable($child, $up, $down, $allFks, $emitted);
            }
            $down[] = "Schema::dropIfExists('{$parent->tfName}');";
            $down[] = ''; // blank line before children drops
            foreach ($parent->children as $child) {
                $down[] = "Schema::dropIfExists('{$child->tfName}');";
            }
            $serial   = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $filename = "{$dateStamp}_{$serial}_create_{$parent->tfName}_tables.php";
            $path     = $this->outDir.DIRECTORY_SEPARATOR.$filename;
            file_put_contents($path, CodeWriter::migrationFile($up, $down));
            $paths[] = $path;
        }

        if ($allFks !== []) {
            $paths[] = $this->writeFks($allFks, $dateStamp);
        }

        return $paths;
    }

    /** @param list<string> $up @param list<string> $down @param array $allFks collected FK defs @param array<string,true> $emitted tracks already-created tables */
    private function emitTable(MirrorTable $table, array &$up, array &$down, array &$allFks, array &$emitted): void
    {
        $tbl = $table->tfName;
        // Skip tables already emitted by an earlier parent's migration
        if (isset($emitted[$tbl])) {
            return;
        }
        $emitted[$tbl] = true;
        $up[] = "Schema::create('{$tbl}', function (Blueprint \$table) {";
        foreach ($table->columns as $col) {
            $up[] = $this->columnDef($col);
        }
        // PK: composite (account_id + keyColumns)
        $pkCols = array_merge(['account_id'], $table->keyColumns);
        if ($table->positioned) {
            $pkCols[] = 'position';
        }
        $up[] = "\$table->primary([" . implode(', ', array_map(fn ($c) => "'{$c}'", $pkCols)) . "]);";
        $up[] = "});";
        $up[] = '';
        $down[] = "Schema::dropIfExists('{$tbl}');";
        $down[] = '';

        // Collect FKs for the final FK migration
        // T5d: children inherit the parent's keyColumns, so from-side and to-side
        // are identical: ['account_id', ...keyColumns]. This references the parent's
        // composite PK (account_id + keyColumns) — a FK to keyColumns alone is invalid.
        if ($table->parentTf !== '') {
            $parentTbl = $table->parentTf;
            $fkCols = array_merge(['account_id'], $table->keyColumns);
            $allFks[] = [$tbl, $fkCols, $parentTbl, $fkCols];
        }
    }

    private function columnDef(MirrorColumn $col): string
    {
        $name = $col->name;
        return match ($col->type) {
            MirrorColumnType::BigInt  => "\$table->bigInteger('{$name}')->unsigned();",
            MirrorColumnType::Integer => "\$table->integer('{$name}')->unsigned();",
            MirrorColumnType::Boolean => "\$table->boolean('{$name}')->default(false);",
            MirrorColumnType::String  => $col->length > 0
                                      ? "\$table->string('{$name}', {$col->length});"
                                      : "\$table->text('{$name}');",
            MirrorColumnType::TinyInt => "\$table->unsignedTinyInteger('{$name}');",
            MirrorColumnType::Double  => "\$table->double('{$name}');",
        };
    }

    /** @param array<array{string, list<string>, string, list<string>}> $fks */
    private function writeFks(array $fks, string $dateStamp): string
    {
        $up   = [];
        $down = [];
        foreach ($fks as [$fromTable, $fromCols, $toTable, $toCols]) {
            $hash  = substr(sha1("{$fromTable}:{$toTable}:".implode(',', $fromCols)), 0, 16);
            $fkName = "fk_{$fromTable}_{$toTable}_{$hash}";
            $from = implode(', ', array_map(fn ($c) => "'{$c}'", $fromCols));
            $to   = implode(', ', array_map(fn ($c) => "'{$c}'", $toCols));
            $up[]   = "Schema::table('{$fromTable}', function (Blueprint \$table) {";
            $up[]   = "\$table->foreign([{$from}])->references([{$to}])->on('{$toTable}')->onDelete('cascade');";
            $up[]   = "});";
            $up[]   = '';
            $down[] = "Schema::table('{$fromTable}', function (Blueprint \$table) {";
            $down[] = "\$table->dropForeign('{$fkName}');";
            $down[] = "});";
            $down[] = '';
        }
        $filename = "{$dateStamp}_9999_create_tf_foreign_keys.php";
        $path     = $this->outDir.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, CodeWriter::migrationFile($up, $down));
        return $path;
    }
}
