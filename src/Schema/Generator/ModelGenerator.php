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

    /** Types whose refs are not FK-targetable (mirrors MigrationGenerator::isFkTargetable). */
    private const NOT_FK_TARGETABLE = ['Object', 'Type', 'TLObject', 'X', 'True'];

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
            $this->anchorModel($type, $scheme, $files, $classes);

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

    /**
     * Task 2.3: reverse hasMany on anchors for incoming object-ref params.
     *
     * @param list<array{0:string,1:string}> $referringTypes  [originType, paramName] pairs
     * @return array{methods: list<string>, uses: list<string>}
     */
    private static function reverseHasMany(TlType $type, array $referringTypes): array
    {
        $methods = [];
        $uses = [];
        if ($referringTypes === []) {
            return ['methods' => $methods, 'uses' => $uses];
        }

        // Build method name for each referring type. Deduplicate collisions
        // by appending the origin type (PascalCase) to the method name.
        $entries = [];
        $methodCount = [];
        foreach ($referringTypes as [$originType, $paramName]) {
            $base = lcfirst(self::pascalParam($paramName));
            if (!isset($methodCount[$base])) {
                $methodCount[$base] = 0;
            }
            $methodCount[$base]++;
            if ($methodCount[$base] > 1) {
                $method = $base . self::pascalParam($originType);
            } else {
                $method = $base;
            }
            $entries[] = [$originType, $paramName, $method];
        }

        // Sort entries by method name for deterministic output.
        usort($entries, static fn (array $a, array $b): int => strcmp($a[2], $b[2]));

        $seenOrigins = [];
        foreach ($entries as [$originType, $paramName, $method]) {
            $shortOrigin = Naming::ctorModel($originType, $originType);
            $originFqcn = self::NS . '\\' . $shortOrigin;
            if (!isset($seenOrigins[$originFqcn])) {
                $uses[] = $originFqcn;
                $seenOrigins[$originFqcn] = true;
            }
            $col = Naming::column($paramName);
            $methods[] = "    public function {$method}(): HasMany";
            $methods[] = '    {';
            $methods[] = "        return \$this->hasMany({$shortOrigin}::class, '{$col}');";
            $methods[] = '    }';
        }

        return ['methods' => $methods, 'uses' => $uses];
    }

    /** @param array<string,string> $files @param-out modified
     * @param list<string> $classes */
    private function anchorModel(TlType $type, TlScheme $scheme, array &$files, array &$classes): void
    {
        $class = Naming::model($type->name);
        $classes[] = $class;
        $table = Naming::anchorTable($type->name);

        // Task 2.3: scan all types for incoming object-ref params targeting this type.
        $referringTypes = [];
        $allTypes = $scheme->types();
        foreach ($allTypes as $otherType) {
            foreach ($otherType->constructors() as $ctor) {
                foreach ($ctor->params() as $param) {
                    if ($param->kind() !== 'ref' || $param->baseType() !== $type->name) {
                        continue;
                    }
                    if ($param->baseType() === 'Peer' || $param->baseType() === 'InputPeer') {
                        continue;
                    }
                    $referringTypes[] = [$otherType->name, $param->name];
                }
            }
        }
        $hasMany = self::reverseHasMany($type, $referringTypes);

        $body = [
            '/** Anchor model for TL type ' . $type->name . ' (spec §4.1). */',
            'final class ' . $class . ' extends TlAnchorModel',
            '{',
            '    use AccountScoped;',
            '',
            "    protected \$table = '{$table}';",
            '',
            '    protected $guarded = [];',
            ...($hasMany['methods'] !== [] ? ['', ...$hasMany['methods']] : []),
            '}',
        ];
        $uses = [
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;',
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;',
            '',
        ];
        if ($hasMany['methods'] !== []) {
            $uses[] = 'use Illuminate\Database\Eloquent\Relations\HasMany;';
        }
        foreach ($hasMany['uses'] as $use) {
            $uses[] = 'use ' . $use . ';';
        }
        $uses[] = '';

        $files[$class . '.php'] = CodeWriter::phpFile(self::NS, [...$uses, ...$body]);
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
        $belongsToMethods = [];
        $belongsToUses = [];
        $hasPeerRef = false;

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

            // Task 2.2: Peer/InputPeer ref → PeerResolution trait (no relation method).
            if ($param->kind() === 'ref' && in_array($param->baseType(), ['Peer', 'InputPeer'], true)) {
                $hasPeerRef = true;
                continue;
            }

            // Task 2.1: object-ref params → belongsTo.
            if ($param->kind() === 'ref' && $this->isFkTargetable($param->baseType(), $param)) {
                $col = Naming::column($param->name);
                $method = lcfirst(self::pascalParam($param->name));
                $base = $param->baseType();
                $shortName = Naming::model($base);
                $targetClass = self::NS . '\\' . $shortName;
                $belongsToMethods[] = "    public function {$method}(): BelongsTo";
                $belongsToMethods[] = '    {';
                $belongsToMethods[] = "        return \$this->belongsTo({$shortName}::class, '{$col}');";
                $belongsToMethods[] = '    }';
                $belongsToUses[] = $targetClass;
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
            '    use AccountScoped;',
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
            ...($belongsToMethods !== [] ? ['', ...$belongsToMethods] : []),
            '}',
        ];
        $uses = [
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;',
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;',
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;',
        ];
        if ($hasPeerRef) {
            $uses[] = 'use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;';
        }
        if ($childMethods !== []) {
            $uses[] = 'use Illuminate\Database\Eloquent\Relations\HasMany;';
        }
        if ($belongsToMethods !== []) {
            $uses[] = 'use Illuminate\Database\Eloquent\Relations\BelongsTo;';
        }
        foreach ($childUses as $use) {
            $uses[] = 'use ' . self::NS . '\\' . $use . ';';
        }
        foreach ($belongsToUses as $use) {
            $uses[] = 'use ' . $use . ';';
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
            '    use AccountScoped;',
            '',
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
            'use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;',
            '',
            ...$body,
        ]);
    }

    private static function isFkTargetable(string $baseType, \MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam $param): bool
    {
        if ($param->isAny()) {
            return false;
        }
        if (str_contains($baseType, '<')) {
            return false;
        }
        return !in_array($baseType, self::NOT_FK_TARGETABLE, true);
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
