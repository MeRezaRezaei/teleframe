<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declarations;

use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Constraint;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Integer;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Relation;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\StringValue;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DataDeclaration;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DeclarationRegistry;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Field;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Declares one DataDeclaration per shipped TL type (plan §3).
 *
 * Mapping (verbatim, plan §3): int/long → Integer, string/bytes →
 * StringValue, flags.N? → optional (no Required), Vector<X> → array,
 * ref → Relation(target, localKey), name = TL type name, source
 * 'tl-mirror:<Name>'. Unknown param kinds → phpType 'mixed', no constraint —
 * never throw.
 */
final class TlMirrorDeclarationFactory
{
    /** @var array<string, string> scalar base-type ⇒ phpType */
    private const SCALAR_PHP = [
        'int' => 'int', 'long' => 'int', 'double' => 'float',
        'string' => 'string', 'bytes' => 'string', 'true' => 'bool',
    ];

    public static function fromScheme(TlScheme $scheme): DeclarationRegistry
    {
        $declarations = [];
        $types = $scheme->types();
        ksort($types);

        foreach ($types as $name => $type) {
            $fields = [];
            $ctors = $type->constructors();
            ksort($ctors);
            foreach ($ctors as $ctor) {
                $params = $ctor->params();
                ksort($params);
                foreach ($params as $param) {
                    if (isset($fields[$param->name])) {
                        continue; // union member: first (sorted) constructor wins
                    }
                    $fields[$param->name] = self::fieldFor($param);
                }
            }
            if ($fields === []) {
                continue; // marker-only union (e.g. 'Vector t') — no field surface
            }
            $declarations[] = new DataDeclaration(
                name: $name,
                source: 'tl-mirror:'.$name,
                description: 'TL mirror declaration for '.$name,
                fields: $fields,
            );
        }

        return DeclarationRegistry::fromDeclarations($declarations);
    }

    /**
     * The curated request/validation surface the emitters dial on:
     * method names from src/Schema/config/curated-methods.json.
     *
     * @return list<string>
     */
    public static function curatedSurface(): array
    {
        $path = __DIR__.'/../../../Schema/config/curated-methods.json';
        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }
        $data = json_decode($json, true);
        if (! is_array($data)) {
            return [];
        }
        $names = [];
        foreach (['mtproto', 'bot-http'] as $key) {
            $list = $data[$key] ?? [];
            if (is_array($list)) {
                foreach ($list as $name) {
                    if (is_string($name) && $name !== '') {
                        $names[] = $name;
                    }
                }
            }
        }
        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }

    private static function fieldFor(TlParam $param): Field
    {
        $optional = $param->conditional() !== null;

        return match ($param->kind()) {
            'scalar' => new Field(
                $param->name,
                self::SCALAR_PHP[$param->baseType()] ?? 'mixed',
                self::scalarConstraints($param->baseType()),
                ! $optional,
            ),
            'vector' => new Field($param->name, 'array', [], ! $optional),
            'ref' => new Field(
                $param->name,
                $param->baseType(),
                [new Relation($param->baseType(), $param->name)],
                ! $optional,
            ),
            default => new Field($param->name, 'mixed', [], ! $optional), // nat / true-token / generic
        };
    }

    /** @return list<Constraint> */
    private static function scalarConstraints(string $base): array
    {
        if (in_array($base, ['int', 'long'], true)) {
            return [new Integer];
        }
        if (in_array($base, ['string', 'bytes'], true)) {
            return [new StringValue];
        }

        return [];
    }
}
