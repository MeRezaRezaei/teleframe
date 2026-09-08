<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Emits Laravel factories for constructor instance models (deterministic,
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

        $types = $scheme->types();
        ksort($types);
        foreach ($types as $type) {
            if ($type->name === 'Vector t' || $type->constructors() === []) {
                continue;
            }
            $ctors = $type->constructors();
            ksort($ctors);
            foreach ($ctors as $ctor) {
                $modelClass = Naming::ctorModel($type->name, $ctor->name);
                $factoryClass = $modelClass . 'Factory';
                $classes[] = $factoryClass;

                $idx = 0;
                $def = [];
                foreach ($ctor->params() as $param) {
                    if ($param->isFiller || $param->kind() === 'generic' || $param->kind() === 'vector') {
                        continue;
                    }
                    $idx++;
                    $col = Naming::column($param->name);
                    $def[] = "            '{$col}' => " . $this->value($param, $idx) . ',';
                }

                $body = [
                    '/** Factory for ' . $modelClass . ' (' . $ctor->name . '). */',
                    'final class ' . $factoryClass . ' extends Factory',
                    '{',
                    '    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\\' . $modelClass . '> */',
                    '    protected $model = \\' . ModelGenerator::modelFqcn($modelClass) . '::class;',
                    '',
                    '    /** @return array<string, mixed> */',
                    '    public function definition(): array',
                    '    {',
                    '        return [',
                    ...$def,
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
            return "(string) new \Symfony\Component\Uid\UuidV7()";
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
