<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;

/**
 * A RecurringScheduleClient with the SERVER's semantics, not a recorder.
 *
 * Every other double in this tree records calls; this one stores ONE entry per
 * schedule id, the way Temporal's own CreateSchedule/UpdateSchedule pair
 * behaves. That is the whole point: a re-arm that STACKED would show up as a
 * second registry entry, whereas a recorder would only show a longer list and
 * look fine.
 *
 * Its own file because the repo keeps one class per file outside the Test
 * suffix (tests/Store/Index, tests/Laravel/Store/FakeS3Gateway.php,
 * tests/Notify/RecordingQueue.php are the precedent).
 */
final class StoreScheduleUpsertDouble implements RecurringScheduleClient
{
    /** @var array<string, array{activity: string, interval: int, queue: string}> */
    private array $registry = [];

    /** @var list<string> */
    private array $operations = [];

    /**
     * @param  array<string, mixed>  $args
     */
    public function createOrUpdateSchedule(
        string $scheduleId,
        string $activity,
        int $intervalSeconds,
        string $taskQueue,
        array $args = [],
    ): string {
        $this->operations[] = isset($this->registry[$scheduleId]) ? 'update' : 'create';
        $this->registry[$scheduleId] = [
            'activity' => $activity,
            'interval' => $intervalSeconds,
            'queue' => $taskQueue,
        ];

        return $scheduleId;
    }

    public function startWorkflow(string $workflow, string $taskQueue, array $args): string
    {
        return 'wf-should-not-be-used';
    }

    /** @return list<string> the schedule ids the "server" currently holds */
    public function ids(): array
    {
        return array_keys($this->registry);
    }

    /** @return array<string, int> schedule id => the cadence it was armed with */
    public function intervals(): array
    {
        $out = [];
        foreach ($this->registry as $id => $row) {
            $out[$id] = $row['interval'];
        }

        return $out;
    }

    /**
     * The create/update sequence, in order. 'create' twice in a row for one
     * id is the stacking bug; 'update' is a refresh.
     *
     * @return list<string>
     */
    public function operations(): array
    {
        return $this->operations;
    }
}
