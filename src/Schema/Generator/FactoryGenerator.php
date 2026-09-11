<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Emits Laravel factories for domain table models (deterministic,
 * param-index-based values — no randomness, reproducible tests).
 */
final class FactoryGenerator
{
    private const NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Factories';

    /** @return array<string,string> class file path (Generated/Factories/X.php) => content */
    public function generate(TlScheme $scheme): array
    {
        $files = [];
        $classes = [];

        // Collect which TL types map to each domain
        $domainTypes = [];
        $types = $scheme->types();
        ksort($types);
        foreach ($types as $type) {
            if ($type->name === 'Vector t' || $type->constructors() === []) {
                continue;
            }
            $classification = Naming::classifyType($type->name);
            if ($classification === null) {
                continue;
            }
            $domain = $classification['domain'];
            $domainTypes[$domain][] = $type;
        }

        // One factory per domain model
        ksort($domainTypes);
        foreach ($domainTypes as $domain => $domainTypeList) {
            $modelClass = Naming::domainModel($domain);
            $factoryClass = $modelClass . 'Factory';
            $classes[] = $factoryClass;

            // Gather all params from all constructors of all types in this domain
            $colDefs = [];
            $idx = 0;
            foreach ($domainTypeList as $type) {
                foreach ($type->constructors() as $ctor) {
                    foreach ($ctor->params() as $param) {
                        if ($param->isFiller || $param->kind() === 'generic' || $param->kind() === 'vector') {
                            continue;
                        }
                        $idx++;
                        $col = Naming::column($param->name);
                        if (!isset($colDefs[$col])) {
                            $colDefs[$col] = "            '{$col}' => " . $this->value($param, $idx) . ',';
                        }
                    }
                    // Use first constructor's params only (they define the domain's columns)
                    break;
                }
            }

            $body = [
                '/** Factory for ' . $modelClass . ' (domain: ' . $domain . '). */',
                'final class ' . $factoryClass . ' extends Factory',
                '{',
                '    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\\' . $modelClass . '> */',
                '    protected $model = \\' . ModelGenerator::modelFqcn($modelClass) . '::class;',
                '',
                '    /** @return array<string, mixed> */',
                '    public function definition(): array',
                '    {',
                '        return [',
                ...array_values($colDefs),
                '        ];',
                '    }',
                '}',
            ];
            $files[$factoryClass . '.php'] = CodeWriter::phpFile(self::NS, [
                'use Illuminate\Database\Eloquent\Factories\Factory;',
                '',
                ...$body,
            ]);
        }

        Naming::assertUnique($classes, 'factory class');
        ksort($files);
        return $files;
    }

    private function value(\MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam $param, int $idx): string
    {
        if ($param->kind() === 'true') {
            return 'true';
        }
        if ($param->kind() === 'nat') {
            return (string) $idx;
        }
        if ($param->kind() === 'ref') {
            return (string) (1000 + $idx);
        }
        return match ($param->baseType()) {
            'int' => (string) $idx,
            'long' => (string) (1000 + $idx),
            'int128', 'int256' => "'" . str_repeat('9', $param->baseType() === 'int128' ? 38 : 77) . "'",
            'double' => sprintf('%.1f', $idx / 10),
            'bytes' => "'" . base64_encode("bytes-{$idx}") . "'",
            default => "'" . $param->name . '-' . $idx . "'",
        };
    }
}
