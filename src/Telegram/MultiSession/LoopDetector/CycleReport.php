<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession\LoopDetector;

final class CycleReport
{
    public function __construct(
        public array $cycle = [],
        public int $length = 0,
        public int $executionsInWindow = 0,
        public float $severity = 0.0,
        public array $recommendedBreakers = [],
    ) {}
}
