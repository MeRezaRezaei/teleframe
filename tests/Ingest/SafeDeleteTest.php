<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\MessagesDeleted;
use MeRezaRezaei\Teleframe\Ingest\SafeDelete;

/**
 * Phase C re-baseline: explicit-safe message deletion on the CURATED dial.
 * tf_messages is keyed (account_id, id) with NO extracted message_id /
 * constructor_id / tl_data / created_at legacy surface — the Telegram
 * message id IS the `id` column, and deletions match on it exactly (the
 * SafeDelete src seam was aligned in the same re-baseline).
 */
class SafeDeleteTest extends IngestTestCase
{
    private const ACCOUNT = 1;

    private SafeDelete $safeDelete;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([MessagesDeleted::class]);
        $this->safeDelete = new SafeDelete(app('events'));

        DB::table('telegram_accounts')->insert([
            'id' => self::ACCOUNT,
            'label' => 'safe-delete-'.self::ACCOUNT,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    public function test_delete_messages_removes_matching_rows(): void
    {
        DB::table('tf_messages')->insert([
            ['account_id' => 1, 'id' => 10, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 1001, 'date' => 1724852400, 'message' => 'a'],
            ['account_id' => 1, 'id' => 20, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 1001, 'date' => 1724852401, 'message' => 'b'],
            ['account_id' => 1, 'id' => 30, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 1001, 'date' => 1724852402, 'message' => 'c'],
        ]);

        $deleted = $this->safeDelete->deleteMessages(1, [10, 20]);

        self::assertSame(2, $deleted);
        $this->assertDatabaseMissing('tf_messages', ['account_id' => 1, 'id' => 10]);
        $this->assertDatabaseMissing('tf_messages', ['account_id' => 1, 'id' => 20]);
        $this->assertDatabaseHas('tf_messages', ['account_id' => 1, 'id' => 30]);
    }

    public function test_delete_messages_fires_event(): void
    {
        DB::table('tf_messages')->insert([
            ['account_id' => 1, 'id' => 5, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 2001, 'date' => 1724852400, 'message' => 'x'],
        ]);

        $this->safeDelete->deleteMessages(1, [5]);

        Event::assertDispatched(MessagesDeleted::class, function (MessagesDeleted $event): bool {
            return $event->accountId === 1
                && $event->messageIds === [5]
                && $event->peerId === null;
        });
    }

    public function test_delete_nonexistent_messages_is_noop(): void
    {
        $deleted = $this->safeDelete->deleteMessages(1, [99999]);
        self::assertSame(0, $deleted);
        Event::assertNotDispatched(MessagesDeleted::class);
    }
}
