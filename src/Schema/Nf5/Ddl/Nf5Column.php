<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5\Ddl;

final class Nf5Column
{
    public function __construct(
        public readonly string $name,
        public readonly Nf5ColumnType $type,
        public readonly int $length = 0,   // VARCHAR(n); 0 = TEXT/default
        public readonly bool $peer = false,
        public readonly bool $hex = false,  // bytes field; stored as TEXT
    ) {}
}
