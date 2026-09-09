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
     * One relation per (origin type, ctor) — the FK column lives on that
     * ctor's instance table, so the relation targets the ctor model.
     * Method names are made unique with a deterministic collision ladder:
     * plain param name, then +ctor, then +origin, then +index. The caller
     * keys origin types (ksorted) and constructors (ksorted), which keeps
     * the ladder reproducible across regenerations.
     *
     * @param list<array{0:string,1:string,2:string}> $referringTypes [originType, ctorName, paramName] pairs
     * @return array{methods: list<string>, uses: list<string>}
     */
    private static function reverseHasMany(TlType $type, array $referringTypes): array
    {
        if ($referringTypes === []) {
            return ['methods' => [], 'uses' => []];
        }

        $used = [];
        $taken = self::reservedMethodNames();
        $entries = [];
        foreach ($referringTypes as [$originType, $ctorName, $paramName]) {
            $base = lcfirst(self::pascalParam($paramName));
            $method = $base;
            $collides = static fn (string $name): bool => isset($used[$name]) || isset($taken[strtolower($name)]);
            if ($collides($method)) {
                $method = $base . self::pascalParam($ctorName);
            }
            if ($collides($method)) {
                $method = $base . self::pascalParam($originType);
            }
            if ($collides($method)) {
                $method = $base . self::pascalParam($ctorName) . self::pascalParam($originType);
            }
            if ($collides($method)) {
                $method = $method . '_' . count($used);
            }
            $used[$method] = true;
            $entries[] = [$originType, $ctorName, $paramName, $method];
        }

        // Sort entries by method name for deterministic output.
        usort($entries, static fn (array $a, array $b): int => strcmp($a[3], $b[3]));

        $methods = [];
        $uses = [];
        $seenTargets = [];
        foreach ($entries as [$originType, $ctorName, $paramName, $method]) {
            $target = Naming::ctorModel($originType, $ctorName);
            $targetFqcn = self::NS . '\\' . $target;
            if (!isset($seenTargets[$targetFqcn])) {
                $uses[] = $targetFqcn;
                $seenTargets[$targetFqcn] = true;
            }
            $col = Naming::column($paramName);
            $methods[] = "    public function {$method}(): HasMany";
            $methods[] = '    {';
            $methods[] = "        return \$this->hasMany({$target}::class, '{$col}');";
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
                    $referringTypes[] = [$otherType->name, $ctor->name, $param->name];
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
        $imports = [
            'MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel',
            'MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped',
        ];
        if ($hasMany['methods'] !== []) {
            $imports[] = 'Illuminate\Database\Eloquent\Relations\HasMany';
        }
        foreach ($hasMany['uses'] as $use) {
            $imports[] = $use;
        }

        $files[$class . '.php'] = CodeWriter::phpFile(self::NS, [...self::useLines($imports), ...$body]);
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
        $taken = self::reservedMethodNames();
        $usedMethods = [];

        $methodName = static function (string $paramName, array &$used) use ($taken): string {
            $base = lcfirst(self::pascalParam($paramName));
            $method = $base;
            if (isset($taken[strtolower($method)])) {
                $method = $base . 'Attr';
            }
            if (isset($used[$method])) {
                $method = $base . '_' . count($used);
            }
            $used[$method] = true;
            return $method;
        };

        foreach ($ctor->params() as $param) {
            if ($param->isFiller || $param->kind() === 'generic') {
                continue;
            }
            if ($param->kind() === 'vector') {
                $childClass = self::childModelClass($table, $param->name);
                $classes[] = $childClass;
                $this->childModel($childClass, Naming::childTable($table, $param->name), $param, $files);
                $method = $methodName($param->name, $usedMethods);
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
                $method = $methodName($param->name, $usedMethods);
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
            '    use AccountScoped;',
            ...($hasPeerRef ? ['    use PeerResolution;', ''] : ['']),
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
        $imports = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren',
            'MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel',
            'MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped',
        ];
        if ($hasPeerRef) {
            $imports[] = 'MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution';
        }
        if ($childMethods !== []) {
            $imports[] = 'Illuminate\Database\Eloquent\Relations\HasMany';
        }
        if ($belongsToMethods !== []) {
            $imports[] = 'Illuminate\Database\Eloquent\Relations\BelongsTo';
        }
        foreach ($childUses as $use) {
            $imports[] = self::NS . '\\' . $use;
        }
        foreach ($belongsToUses as $use) {
            $imports[] = $use;
        }

        $files[$class . '.php'] = CodeWriter::phpFile(self::NS, [...self::useLines($imports), ...$body]);
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
        $files[$class . '.php'] = CodeWriter::phpFile(self::NS, [...self::useLines([
            'MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel',
            'MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped',
        ]), ...$body]);
    }

    /**
     * Per-file use-import assembly: deduped, ksort-deterministic 'use X;'
     * lines terminated by a single blank line. Every import source for a
     * generated model goes through here (relation targets, trait imports,
     * base model classes) so no FQCN can be imported twice in one file.
     *
     * @param list<string> $imports fully-qualified class names
     * @return list<string> deduped 'use X;' lines in ksort order plus a blank separator
     */
    private static function useLines(array $imports): array
    {
        $set = [];
        foreach ($imports as $import) {
            if ($import !== '') {
                $set[$import] = true;
            }
        }
        ksort($set);
        $lines = [];
        foreach (array_keys($set) as $fqcn) {
            $lines[] = 'use ' . $fqcn . ';';
        }
        $lines[] = '';
        return $lines;
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
        return Naming::pascal($name);
    }

    /** @return array<string,true> lowercase method names a generated method must never shadow */
    private static function reservedMethodNames(): array
    {
        static $taken = null;
        if ($taken === null) {
            $taken = [];
            foreach (array_merge(
                get_class_methods(\Illuminate\Database\Eloquent\Model::class),
                get_class_methods(\MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel::class),
            ) as $methodSig) {
                $taken[strtolower($methodSig)] = true;
            }
        }
        return $taken;
    }
}
