<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5Column;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5ColumnType;

/**
 * Renders catalog shape strings into concrete Nf5Column lists, and
 * exposes TL-param-to-shape for the resolver to decompose vector
 * elements and non-catalog FK targets.
 */
final class Nf5FieldDecomposer
{
    private const PEER_SHAPE = 'peer_type TINYINT + peer_id BIGINT';

    /**
     * One shape string → one or two Nf5Columns.
     * Returns null for FK/Vector shapes — resolver handles those.
     *
     * @return list<Nf5Column>|null
     */
    public static function fromShape(string $shape, string $field, bool $hex = false): ?array
    {
        if (str_starts_with($shape, 'FK→') || $shape === '1:N child') {
            return null;
        }
        if ($shape === self::PEER_SHAPE) {
            return [
                new Nf5Column("{$field}_type", Nf5ColumnType::TinyInt, peer: true),
                new Nf5Column("{$field}_id",  Nf5ColumnType::BigInt,  peer: true),
            ];
        }
        return [self::scalarColumn($shape, $field, $hex)];
    }

    /**
     * Catalog shape for a single TL param — used when the resolver
     * must decompose a vector element or a non-mirror FK target's children.
     */
    public static function shapeForParam(TlParam $param): string
    {
        return match ($param->kind()) {
            'true'    => 'BOOLEAN NOT NULL DEFAULT FALSE',
            'scalar'  => self::scalarShape($param->baseType()),
            'ref'     => in_array($param->baseType(), ['Bool', 'True'], true)
                         ? 'BOOLEAN NOT NULL DEFAULT FALSE'
                         : 'FK→' . $param->baseType(),
            'vector'  => '1:N child',
            default   => 'TEXT NOT NULL',
        };
    }

    public static function hexLengthForBytes(TlParam $param): int
    {
        // 0 = unlimited TEXT; callers may override for bounded VARCHAR(n) if needed
        return $param->baseType() === 'bytes' ? 0 : 0;
    }

    /**
     * Parse a scalar shape string into a single Nf5Column.
     *
     * F1 ruling: no regex — string ops only for VARCHAR length extraction.
     * F7 ruling: BOOLEAN branch added before the BigInt fallback.
     */
    private static function scalarColumn(string $shape, string $field, bool $hex): Nf5Column
    {
        // F1: string ops only — no preg_match
        if (str_starts_with($shape, 'VARCHAR(')) {
            $parenPos = strpos($shape, '(');
            $closeParen = strpos($shape, ')', $parenPos);
            $lenStr = substr($shape, $parenPos + 1, $closeParen - $parenPos - 1);
            $length = (int) $lenStr;
            if ($length > 0) {
                return new Nf5Column($field, Nf5ColumnType::String, $length);
            }
        }
        if ($shape === 'TEXT NOT NULL') {
            return new Nf5Column($field, Nf5ColumnType::String);
        }
        if (str_contains($shape, 'DOUBLE')) {
            return new Nf5Column($field, Nf5ColumnType::Double);
        }
        if (str_contains($shape, 'TINYINT')) {
            return new Nf5Column($field, Nf5ColumnType::TinyInt);
        }
        // F7: BOOLEAN branch before BigInt fallback
        if (str_contains($shape, 'BOOLEAN')) {
            return new Nf5Column($field, Nf5ColumnType::Boolean);
        }
        if (str_contains($shape, 'BIGINT')) {
            return new Nf5Column($field, Nf5ColumnType::BigInt, hex: $hex);
        }
        if (str_contains($shape, 'INTEGER')) {
            return new Nf5Column($field, Nf5ColumnType::Integer);
        }
        return new Nf5Column($field, Nf5ColumnType::BigInt);
    }

    private static function scalarShape(string $baseType): string
    {
        return match ($baseType) {
            'int'    => 'INTEGER NOT NULL',
            'long'   => 'BIGINT NOT NULL',
            'bool', 'True' => 'BOOLEAN NOT NULL DEFAULT FALSE',
            'string' => 'TEXT NOT NULL',
            'double' => 'DOUBLE PRECISION NOT NULL',
            'bytes'  => 'TEXT NOT NULL',
            default  => 'TEXT NOT NULL',
        };
    }
}
