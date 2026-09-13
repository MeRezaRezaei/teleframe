<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\CodeWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumnType;

final class MirrorFactoryWriter
{
    public function __construct(private readonly string $outDir) {}

    /** @param list<MirrorTable> $tables @return list<string> */
    public function writeAll(array $tables): array
    {
        @mkdir($this->outDir.'/Factories/Mirror', 0777, true);
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

    /** Recursively collect the subtree's factory paths (deduped by tfName). */
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
        $model = 'MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\\'.$class;
        $body  = [];
        $body[] = "use {$model};";
        $body[] = '';
        $body[] = "class {$class}Factory extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory";
        $body[] = '{';
        $body[] = "    protected \$model = {$class}::class;";
        $body[] = '';
        $body[] = '    public function definition(): array';
        $body[] = '    {';
        $body[] = '        return [';
        foreach ($table->columns as $col) {
            if (in_array($col->name, ['account_id'], true)) {
                continue;
            }
            $body[] = $this->fakerFor($col);
        }
        $body[] = '        ];';
        $body[] = '    }';
        $body[] = '}';
        $path = $this->outDir.'/Factories/Mirror/'.$class.'Factory.php';
        file_put_contents($path, CodeWriter::phpFile(
            'MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror',
            $body,
        ));
        return $path;
    }

    private function fakerFor(\MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumn $col): string
    {
        return match ($col->type) {
            MirrorColumnType::BigInt  => "            '{$col->name}' => fake()->unique()->randomNumber(8),",
            MirrorColumnType::Integer => "            '{$col->name}' => fake()->numberBetween(0, 2147483647),",
            MirrorColumnType::Boolean => "            '{$col->name}' => fake()->boolean(),",
            MirrorColumnType::String  => "            '{$col->name}' => fake()->word(),",
            MirrorColumnType::TinyInt => "            '{$col->name}' => fake()->numberBetween(1, 3),",
            MirrorColumnType::Double  => "            '{$col->name}' => fake()->randomFloat(6, -90, 90),",
        };
    }
}
