<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use RuntimeException;
use Throwable;

/**
 * Scripted messages.getHistory stand-in for BackfillWorker tests: replays a
 * fixed sequence of steps, each either a DESC page (list of message arrays)
 * or a Throwable to throw. Records every (offset_id, limit) call. Running
 * past the script throws — a canary against budget overruns.
 */
final class ScriptedFetch
{
    /** @var list<array{int, int}> every (offsetId, limit) invocation */
    public array $calls = [];

    private int $cursor = 0;

    /** @param list<array<string, mixed>|Throwable> $steps */
    public function __construct(private readonly array $steps)
    {
    }

    /**
     * Build a DESC page (newest -> oldest) of minimal message rows.
     *
     * @return list<array<string, mixed>>
     */
    public static function page(int ...$ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[] = ['id' => $id, 'date' => 1_700_000_000 - $id, 'message' => 'm' . $id];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(int $offsetId, int $limit): array
    {
        $this->calls[] = [$offsetId, $limit];

        $step = $this->steps[$this->cursor]
            ?? throw new RuntimeException('ScriptedFetch script exhausted');

        $this->cursor++;

        if ($step instanceof Throwable) {
            throw $step;
        }

        return $step;
    }
}
