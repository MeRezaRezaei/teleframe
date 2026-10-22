<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine;

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

class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\QueryEngine\\Policy', 'MeRezaRezaei\\Telegram\\MultiSession\\QueryEngine\\Policy');
class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\QueryEngine\\Policy', 'MeRezaRezaei\\Teleframe\\MultiSession\\QueryEngine\\Policy');
