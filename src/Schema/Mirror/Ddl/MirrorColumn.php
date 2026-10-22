<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror\Ddl;

final class MirrorColumn
{
    public function __construct(
        public readonly string $name,
        public readonly MirrorColumnType $type,
        public readonly int $length = 0,   // VARCHAR(n); 0 = TEXT/default
        public readonly bool $peer = false,
        public readonly bool $hex = false,  // bytes field; stored as TEXT
    ) {}
}
