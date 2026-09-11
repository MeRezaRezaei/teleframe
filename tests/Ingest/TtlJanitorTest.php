<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\TtlJanitor;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

class TtlJanitorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create tf_stories table if it doesn't exist in the test DB
        if (!Schema::hasTable('tf_stories')) {
            Schema::create('tf_stories', function ($table) {
                $table->bigIncrements('id');
                $table->bigInteger('peer_id');
                $table->bigInteger('story_id');
                $table->bigInteger('account_id');
                $table->bigInteger('constructor_id');
                $table->text('tl_data');
                $table->timestamp('expire_date');
                $table->timestamps();
            });
        }
    }

    public function test_sweep_removes_expired_rows(): void
    {
        $expired = now()->subDay();
        $future = now()->addDay();

        DB::table('tf_stories')->insert([
            ['peer_id' => 1, 'story_id' => 1, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'expire_date' => $expired, 'created_at' => now()],
            ['peer_id' => 1, 'story_id' => 2, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'expire_date' => $future, 'created_at' => now()],
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
        for ($i = 100; $i < 105; $i++) {
            DB::table('tf_stories')->insert([
                'peer_id' => $i, 'story_id' => $i, 'account_id' => 1,
                'constructor_id' => 0, 'tl_data' => '{}',
                'expire_date' => $expired, 'created_at' => now(),
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
