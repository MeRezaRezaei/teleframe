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
        $paths = [];
        foreach ($tables as $table) {
            $paths[] = $this->writeTable($table);
            foreach ($table->children as $child) {
                $paths[] = $this->writeTable($child);
            }
        }
        return $paths;
    }

    private function writeTable(MirrorTable $table): string
    {
        $class = $this->className($table->tfName);
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

    private function className(string $tfName): string
    {
        $parts = explode('_', $tfName);
        $out = '';
        foreach ($parts as $part) {
            $out .= ucfirst($part);
        }
        // T6d: map known plurals BEFORE stripping trailing 's'
        $suffixes = ['Entities' => 'Entity', 'Medias' => 'Media', 'Actions' => 'Action'];
        foreach ($suffixes as $from => $to) {
            if (str_ends_with($out, $from)) {
                return substr($out, 0, -strlen($from)) . $to;
            }
        }
        if (str_ends_with($out, 's') && !str_ends_with($out, 'ss')) {
            $out = substr($out, 0, -1);
        }
        return $out;
    }
}
