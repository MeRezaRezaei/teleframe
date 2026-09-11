<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

final class Nf5TableEntry
{
    /** @param list<string> $ctors @param list<array{string,string}> $base @param list<string> $bools @param list<array{string,string}> $children */
    public function __construct(
        public readonly string $tfName,
        public readonly string $tlType,
        public readonly array $ctors,
        public readonly array $base,
        public readonly array $bools,
        public readonly array $children,
    ) {}
}
