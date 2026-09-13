<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\CodeWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumnType;

final class MirrorModelWriter
{
    public function __construct(private readonly string $outDir) {}

    /** @param list<MirrorTable> $tables @return list<string> */
    public function writeAll(array $tables): array
    {
        @mkdir($this->outDir.'/Models/Mirror', 0777, true);
        $classNames = MirrorClassName::map($this->collectTfNames($tables));
        $paths = [];
        $written = [];
        foreach ($tables as $table) {
            $this->collectTables($table, $paths, $written, $classNames);
        }

        return $paths;
    }

    /** @return list<string> every tfName reachable from the given parents */
    private function collectTfNames(array $tables): array
    {
        $names = [];
        $seen = [];
        $walk = function (MirrorTable $table) use (&$walk, &$names, &$seen): void {
            if (isset($seen[$table->tfName])) {
                return;
            }
            $seen[$table->tfName] = true;
            $names[] = $table->tfName;
            foreach ($table->children as $child) {
                $walk($child);
            }
        };
        foreach ($tables as $table) {
            $walk($table);
        }

        return $names;
    }

    /** Recursively collect the subtree's model paths (deduped by tfName). */
    private function collectTables(MirrorTable $table, array &$paths, array &$written, array $classNames): void
    {
        if (isset($written[$table->tfName])) {
            return;
        }
        $written[$table->tfName] = true;
        $paths[] = $this->writeTable($table, $classNames);
        foreach ($table->children as $child) {
            $this->collectTables($child, $paths, $written, $classNames);
        }
    }

    private function writeTable(MirrorTable $table, array $classNames): string
    {
        $class = $classNames[$table->tfName];
        $base = $table->parentTf === '' ? 'TfMirrorModel' : 'TfChildModel'; // T6a: parent flag is always true
        $body = [];
        $body[] = 'use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\AccountScoped;';
        $body[] = "use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\{$base};";
        $body[] = '';
        $body[] = "final class {$class} extends {$base}";
        $body[] = '{';
        $body[] = '    use AccountScoped;';
        $body[] = '';
        $body[] = "    protected \$table = '{$table->tfName}';";
        if ($table->parentTf !== '') { // T6a: children get parent_id PK
            $body[] = "    protected \$primaryKey = 'parent_id';";
        }
        $body[] = '';
        $body[] = '    protected $guarded = [];';
        $casts = $this->casts($table);
        if ($casts !== []) {
            $body[] = '';
            $body[] = '    protected $casts = [';
            foreach ($casts as $c) {
                $body[] = "        '{$c[0]}' => '{$c[1]}',";
            }
            $body[] = '    ];';
        }
        $body[] = '}';
        $path = $this->outDir.'/Models/Mirror/'.$class.'.php';
        file_put_contents($path, CodeWriter::phpFile(
            'MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror',
            $body,
        ));

        return $path;
    }

    /** @return list<array{string,string}> cast column => type */
    private function casts(MirrorTable $table): array
    {
        $casts = [];
        foreach ($table->columns as $col) {
            if ($col->type === MirrorColumnType::Boolean) {
                $casts[] = [$col->name, 'boolean'];
            } elseif ($col->type === MirrorColumnType::BigInt || $col->type === MirrorColumnType::Integer) {
                $casts[] = [$col->name, 'integer'];
            } elseif ($col->type === MirrorColumnType::Double) {
                $casts[] = [$col->name, 'float'];
            }
        }

        return $casts;
    }
}
