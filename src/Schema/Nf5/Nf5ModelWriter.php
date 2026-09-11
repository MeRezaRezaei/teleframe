<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\CodeWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5Column;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5ColumnType;

final class Nf5ModelWriter
{
    public function __construct(private readonly string $outDir) {}

    /** @param list<Nf5Table> $tables @return list<string> */
    public function writeAll(array $tables): array
    {
        @mkdir($this->outDir.'/Models/Mirror', 0777, true);
        $paths = [];
        foreach ($tables as $table) {
            $paths[] = $this->writeTable($table);
            foreach ($table->children as $child) {
                $paths[] = $this->writeTable($child);
            }
        }
        return $paths;
    }

    private function writeTable(Nf5Table $table): string
    {
        $class   = $this->className($table->tfName);
        $base    = $table->parentTf === '' ? 'TfMirrorModel' : 'TfChildModel'; // T6a: parent flag is always true
        $body    = [];
        $body[]  = "use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\AccountScoped;";
        $body[]  = "use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\{$base};";
        $body[]  = '';
        $body[]  = "final class {$class} extends {$base}";
        $body[]  = '{';
        $body[]  = '    use AccountScoped;';
        $body[]  = '';
        $body[]  = "    protected \$table = '{$table->tfName}';";
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
    private function casts(Nf5Table $table): array
    {
        $casts = [];
        foreach ($table->columns as $col) {
            if ($col->type === Nf5ColumnType::Boolean) {
                $casts[] = [$col->name, 'boolean'];
            } elseif ($col->type === Nf5ColumnType::BigInt || $col->type === Nf5ColumnType::Integer) {
                $casts[] = [$col->name, 'integer'];
            } elseif ($col->type === Nf5ColumnType::Double) {
                $casts[] = [$col->name, 'float'];
            }
        }
        return $casts;
    }

    /** tf_messages -> TfMessage; tf_message_medias -> TfMessageMedia (pascal). */
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
