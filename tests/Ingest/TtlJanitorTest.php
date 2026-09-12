<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\TtlJanitor;

class TtlJanitorTest extends IngestTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // tf_stories comes from the migrated real DDL (IngestTestCase):
        // bigInteger id PK (no autoincrement — supply explicitly).
    }

    public function test_sweep_removes_expired_rows(): void
    {
        $expired = now()->subDay();
        $future = now()->addDay();

        // expire_date is integer-affinity in the real DDL; store datetime
        // strings (SQLite flexible typing) so TtlJanitor's `<= now()`
        // string comparison behaves instead of int-vs-text always-true.
        DB::table('tf_stories')->insert([
            ['id' => 1, 'peer_id' => 1, 'story_id' => 1, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'expire_date' => $expired->toDateTimeString(), 'created_at' => now()],
            ['id' => 2, 'peer_id' => 1, 'story_id' => 2, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'expire_date' => $future->toDateTimeString(), 'created_at' => now()],
        ]);

        $janitor = new TtlJanitor();
        $results = $janitor->sweep();

        $storyResult = null;
        foreach ($results as $r) {
            if ($r['table'] === 'tf_stories') {
                $storyResult = $r;
                break;
            }
        }

        $this->assertNotNull($storyResult);
        $this->assertSame(1, $storyResult['deleted']);
        $this->assertDatabaseMissing('tf_stories', ['story_id' => 1, 'account_id' => 1]);
        $this->assertDatabaseHas('tf_stories', ['story_id' => 2, 'account_id' => 1]);
    }

    public function test_sweep_respects_limit(): void
    {
        $expired = now()->subDay();
        $id = 100;
        for ($i = 100; $i < 105; $i++) {
            DB::table('tf_stories')->insert([
                'id' => $id++, 'peer_id' => $i, 'story_id' => $i, 'account_id' => 1,
                'constructor_id' => 0, 'tl_data' => '{}',
                'expire_date' => $expired->toDateTimeString(), 'created_at' => now(),
            ]);
        }

        $janitor = new TtlJanitor(batchSize: 2);
        $results = $janitor->sweep();

        $storyResult = null;
        foreach ($results as $r) {
            if ($r['table'] === 'tf_stories') {
                $storyResult = $r;
                break;
            }
        }

        $this->assertNotNull($storyResult);
        // Double-limit loop: 2 + 2 + 1 = 5 total (loop stops when batch < batchSize)
        $this->assertSame(5, $storyResult['deleted']);
    }

    public function test_sweep_returns_empty_when_no_expired(): void
    {
        $janitor = new TtlJanitor();
        $results = $janitor->sweep();
        $this->assertSame([], $results);
    }
}
