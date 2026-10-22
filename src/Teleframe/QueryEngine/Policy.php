<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\QueryEngine;

final class Policy
{
    public function __construct(
        public int $cooldownMs = 0,
        public int $dedupWindowMs = 0,
        public int $maxExecutions = -1,
        public bool $breakCycles = true,
        public array $cycleBreakers = [],
    ) {}
}
