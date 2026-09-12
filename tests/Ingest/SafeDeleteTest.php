<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\MessagesDeleted;
use MeRezaRezaei\Teleframe\Ingest\SafeDelete;

class SafeDeleteTest extends IngestTestCase
{
    private SafeDelete $safeDelete;

    protected function setUp(): void
    {
        parent::setUp();

        // tf_messages comes from the migrated real DDL (IngestTestCase):
        // bigInteger id PK (no autoincrement — supply explicitly) + date NOT NULL.
        Event::fake([MessagesDeleted::class]);
        $this->safeDelete = new SafeDelete(app('events'));
    }

    public function test_delete_messages_removes_matching_rows(): void
    {
        DB::table('tf_messages')->insert([
            ['id' => 1, 'peer_id' => 1001, 'message_id' => 10, 'account_id' => 1, 'constructor_id' => 0, 'date' => now()->timestamp, 'tl_data' => '{}', 'created_at' => now()],
            ['id' => 2, 'peer_id' => 1001, 'message_id' => 20, 'account_id' => 1, 'constructor_id' => 0, 'date' => now()->timestamp, 'tl_data' => '{}', 'created_at' => now()],
            ['id' => 3, 'peer_id' => 1001, 'message_id' => 30, 'account_id' => 1, 'constructor_id' => 0, 'date' => now()->timestamp, 'tl_data' => '{}', 'created_at' => now()],
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
            ['id' => 1, 'peer_id' => 2001, 'message_id' => 5, 'account_id' => 2, 'constructor_id' => 0, 'date' => now()->timestamp, 'tl_data' => '{}', 'created_at' => now()],
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
