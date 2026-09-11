<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5\Ddl;

enum Nf5ColumnType: string
{
    case BigInt   = 'bigint';
    case Integer  = 'integer';
    case Boolean  = 'boolean';
    case String   = 'string';
    case TinyInt  = 'tinyint';
    case Double   = 'double';

    public function sql(): string
    {
        return match ($this) {
            self::BigInt  => 'BIGINT',
            self::Integer => 'INTEGER',
            self::Boolean => 'BOOLEAN',
            self::String  => 'TEXT',
            self::TinyInt => 'TINYINT',
            self::Double  => 'DOUBLE PRECISION',
        };
    }
}
