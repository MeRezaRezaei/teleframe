<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlType;

/**
 * Emits Eloquent models (spec §6.2): anchor, constructor-instance, and
 * vector-child models with lazy child scopes.
 */
final class ModelGenerator
{
    private const NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Models';

    public static function modelFqcn(string $class): string
    {
        return self::NS . '\\' . $class;
    }

    /** @return array<string,string> class file path (Generated/Models/X.php) => content */
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
            $this->anchorModel($type, $files, $classes);

            $ctors = $type->constructors();
            ksort($ctors);
            foreach ($ctors as $ctor) {
                $this->ctorModel($type, $ctor, $files, $classes);
            }
        }

        Naming::assertUnique($classes, 'model class');
        ksort($files);
        return $files;
    }

    /** @param array<string,string> $files @param-out modified
     * @param list<string> $classes */
    private function anchorModel(TlType $type, array &$files, array &$classes): void
    {
        $class = Naming::model($type->name);
        $classes[] = $class;
        $table = Naming::anchorTable($type->name);
        $body = [
            '/** Anchor model for TL type ' . $type->name . ' (spec §4.1). */',
            'final class ' . $class . ' extends TlAnchorModel',
            '{',
            "    protected \$table = '{$table}';",
            '',
            '    protected $guarded = [];',
            '}',
        ];
        $files[$class . '.php'] = CodeWriter::phpFile(self::NS, [
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;',
            '',
            ...$body,
        ]);
    }

    /** @param array<string,string> $files @param-out modified
     * @param list<string> $classes */
    private function ctorModel(TlType $type, \MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor $ctor, array &$files, array &$classes): void
    {
        $class = Naming::ctorModel($type->name, $ctor->name);
        $classes[] = $class;
        $table = Naming::instanceTable($type->name, $ctor->name);

        $casts = [];
        $childMethods = [];
        $childUses = [];
        foreach ($ctor->params() as $param) {
            if ($param->isFiller || $param->kind() === 'generic') {
                continue;
            }
            if ($param->kind() === 'vector') {
                $childClass = self::childModelClass($table, $param->name);
                $classes[] = $childClass;
                $this->childModel($childClass, Naming::childTable($table, $param->name), $param, $files);
                $method = lcfirst(self::pascalParam($param->name));
                $childMethods[] = "    public function {$method}(): HasMany";
                $childMethods[] = '    {';
                $childMethods[] = "        return \$this->tlChild({$childClass}::class);";
                $childMethods[] = '    }';
                $childUses[] = $childClass;
                continue;
            }
            $casts[] = "        '" . Naming::column($param->name) . "' => '" . Naming::cast($param) . "',";
        }

        $body = [
            '/** Constructor model for ' . $ctor->name . ' of ' . $type->name . ' (crc32 ' . sprintf('%08x', $ctor->id) . '). */',
            'final class ' . $class . ' extends TlInstanceModel',
            '{',
            '    use HasFactory, HasTlChildren;',
            '',
            "    protected \$table = '{$table}';",
            '',
            '    protected $guarded = [];',
            '',
            '    /** @var array<string, string> */',
            '    protected $casts = [',
            ...$casts,
            '    ];',
            ...($childMethods !== [] ? ['', ...$childMethods] : []),
            '}',
        ];
        $uses = [
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;',
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;',
            'use Illuminate\Database\Eloquent\Relations\HasMany;',
        ];
        foreach ($childUses as $use) {
            $uses[] = 'use ' . self::NS . '\\' . $use . ';';
        }
        $uses[] = '';

        $files[$class . '.php'] = CodeWriter::phpFile(self::NS, [...$uses, ...$body]);
    }

    /** @param array<string,string> $files @param-out modified */
    private function childModel(string $class, string $table, \MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam $param, array &$files): void
    {
        $element = $param->baseType();
        $elementParam = new \MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam($param->name, $element);
        $casts = [];
        if (in_array($elementParam->kind(), ['scalar', 'nat', 'true'], true)) {
            $casts[] = "        'value' => '" . Naming::cast($elementParam) . "',";
        }
        $body = [
            '/** Vector child rows for param ' . $param->name . ' (table ' . $table . '). */',
            'final class ' . $class . ' extends TlAnchorModel',
            '{',
            "    protected \$table = '{$table}';",
            '',
            '    public $timestamps = false; // child tables carry no timestamps columns',
            '',
            '    protected $guarded = [];',
            '',
            '    /** @var array<string, string> */',
            '    protected $casts = [',
            ...$casts,
            '    ];',
            '}',
        ];
        $files[$class . '.php'] = CodeWriter::phpFile(self::NS, [
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;',
            '',
            ...$body,
        ]);
    }

    public static function childModelClass(string $instanceTable, string $param): string
    {
        $base = substr($instanceTable, 3); // strip tl_
        $words = array_filter(explode('_', $base));
        $pascal = implode('', array_map('ucfirst', $words));
        return 'Tl' . $pascal . ucfirst($param);
    }

    private static function pascalParam(string $name): string
    {
        return implode('', array_map('ucfirst', explode('_', $name)));
    }
}
