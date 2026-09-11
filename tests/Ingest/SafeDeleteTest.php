<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\Events\MessagesDeleted;
use MeRezaRezaei\Teleframe\Ingest\SafeDelete;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

class SafeDeleteTest extends TestCase
{
    private SafeDelete $safeDelete;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tf_messages table if it doesn't exist in the test DB
        if (!Schema::hasTable('tf_messages')) {
            Schema::create('tf_messages', function ($table) {
                $table->bigIncrements('id');
                $table->bigInteger('message_id');
                $table->bigInteger('peer_id');
                $table->bigInteger('account_id');
                $table->bigInteger('constructor_id');
                $table->text('tl_data');
                $table->timestamps();
            });
        }

        Event::fake([MessagesDeleted::class]);
        $this->safeDelete = new SafeDelete(app('events'));
    }

    public function test_delete_messages_removes_matching_rows(): void
    {
        DB::table('tf_messages')->insert([
            ['peer_id' => 1001, 'message_id' => 10, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'created_at' => now()],
            ['peer_id' => 1001, 'message_id' => 20, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'created_at' => now()],
            ['peer_id' => 1001, 'message_id' => 30, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'created_at' => now()],
        ]);

        $deleted = $this->safeDelete->deleteMessages(1, [10, 20]);

        $this->assertSame(2, $deleted);
        $this->assertDatabaseMissing('tf_messages', ['peer_id' => 1001, 'message_id' => 10, 'account_id' => 1]);
        $this->assertDatabaseMissing('tf_messages', ['peer_id' => 1001, 'message_id' => 20, 'account_id' => 1]);
        $this->assertDatabaseHas('tf_messages', ['peer_id' => 1001, 'message_id' => 30, 'account_id' => 1]);
    }

    public function test_delete_messages_fires_event(): void
    {
        DB::table('tf_messages')->insert([
            ['peer_id' => 2001, 'message_id' => 5, 'account_id' => 2, 'constructor_id' => 0, 'tl_data' => '{}', 'created_at' => now()],
        ]);

        $this->safeDelete->deleteMessages(2, [5]);

        Event::assertDispatched(MessagesDeleted::class, function (MessagesDeleted $event): bool {
            return $event->accountId === 2
                && $event->messageIds === [5]
                && $event->peerId === null;
        });
    }

    public function test_delete_nonexistent_messages_is_noop(): void
    {
        $deleted = $this->safeDelete->deleteMessages(1, [99999]);
        $this->assertSame(0, $deleted);
        Event::assertNotDispatched(MessagesDeleted::class);
    }
}
