<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\QueryEngine;

final class Binding
{
    public function __construct(
        public mixed $contract,
        public array $preconditions = [],
        public array $postconditions = [],
        public bool $breakCycle = false,
        public array $metadata = [],
        public ?string $id = null,
    ) {}
}
