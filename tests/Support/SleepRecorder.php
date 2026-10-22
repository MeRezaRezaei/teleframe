<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

/**
 * Recording fake for BackfillWorker's injected sleep: captures every
 * (capped) FLOOD_WAIT duration instead of blocking.
 */
final class SleepRecorder
{
    /** @var list<int> slept seconds, in order */
    public array $seconds = [];

    public function __invoke(int $seconds): void
    {
        $this->seconds[] = $seconds;
    }
}
