# TDLib Operational Patterns — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add TDLib-inspired operational patterns (pts watermark persistence, safe-delete, query builders, media bitmask, FTS, TTL janitor, stream dedup) on top of the existing domain-table schema, making the ingest pipeline production-ready for multi-account polling with complex relational queries.

**Architecture:** TDLib uses ~7 domain tables with BLOB+extracted columns, upsert-only writes, explicit deletes, and a TTL cleanup loop. Our Postgres+JSONB schema already exceeds TDLib's BLOB approach (queryable JSONB, GIN indexes). This plan adds the missing operational surface: persistent pts state for restart-safe polling, safe-delete events, rich query builders for the domain models, a `media_flags` bitmask (TDLib's `index_mask` pattern), Postgres tsvector full-text search, a periodic TTL janitor, and Redis stream dedup for the bus path.

**Tech Stack:** PHP 8.2+, Laravel 11, Redis 7 (XADD/XREADGROUP + HSET for pts), PostgreSQL 17 (tsvector + GIN, partial indexes), Eloquent query builders, `Illuminate\Support\Facades\DB`.

**Spec:** `docs/superpowers/specs/2026-09-10-telegram-mirror-schema-design.md`

## Global Constraints

- **Zero regex in `src/` outside allow-list**: `preg_*()` banned in `src/Core/*`, `src/Teleframe/*`; allowed only in `src/Laravel/*`, `src/Schema/*`, `src/Bot/*`.
- **Session strings are credentials**: `.env` never committed; tests never require real credentials.
- **Redis wire keys are opaque state**: do not rename `tg:stream:updates` / group `teleclient` / `tg:bus:reload` / DL / `tg:bus:routes` (forward compat with archived teleclient).
- **Public bind keys stay fixed**: `teleclient.backfill.scope-resolver`, `teleclient.backfill.ingester`, `teleclient.backup.vault-factory`.
- **Generated artifacts never hand-edit**: `generated/**`, `schema/methods-*.json`, `src/{Core,Bot}/Methods/Generated/*.php`.
- **Gate before done**: `composer verify`, `php bin/standalone-smoke.php`, `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg`.
- **Tests mirror `src/`**: one test class per file, namespace mirrors `src/`.

## Existing Infrastructure (already delivered)

| Component | File | Status |
|---|---|---|
| `UpdateIngestor` | `src/Teleframe/Ingest/UpdateIngestor.php` | Done — domain-table upsert with `PayloadWalker` |
| `UpdateStored` event | `src/Teleframe/Ingest/Events/UpdateStored.php` | Done — `TlAnchorModel + accountId` |
| `UpdateStoredHandler` | `src/Teleframe/Handler/Subscriptions/UpdateStoredHandler.php` | Done — bridges event → handler pipeline |
| `UpdateStoredCentrifugoListener` | `src/Laravel/Realtime/UpdateStoredCentrifugoListener.php` | Done — per-account + per-chat Centrifugo fan-out |
| `CentrifugoBridge` interface | `src/Teleframe/Realtime/CentrifugoBridge.php` | Done |
| `ChannelNames` | `src/Teleframe/Realtime/ChannelNames.php` | Done |
| `UpdatePollerService` | `src/Laravel/Services/UpdatePollerService.php` | Done — full pts state machine (in-memory only) |
| `IngestConsumer` | `src/Teleframe/Bus/IngestConsumer.php` | Done — Redis stream consumption with dead-letter |
| `StreamSchema` | `src/Teleframe/Bus/StreamSchema.php` | Done — wire constants + encode/decode |
| `RouteTable` | `src/Teleframe/Bus/RouteTable.php` | Done — prefix constructor matching |
| `HandlerSink` | `src/Teleframe/Handler/HandlerSink.php` | Done — bus → handler pipeline bridge |
| `RouteIdempotency` | `src/Teleframe/Ingest/RouteIdempotency.php` | Done — method-response dedup via UUIDv5 |
| `EntityAggregator` | `src/Teleframe/Ingest/EntityAggregator.php` | Done — peer-long → domain model resolution |
| `Update` | `src/Teleframe/Handler/Update.php` | Done — uprate value object |
| `Naming` | `src/Schema/Generator/Naming.php` | Done — domain table/model/column name mapping |

---

### Task 1: Pts Watermark — Redis-Backed Per-Account Sequence State

**Why:** `UpdatePollerService` tracks `{pts, date, qts, seq}` in-memory only. On restart, the poller must resume from the last known watermark. TDLib persists its update state to SQLite; we persist ours to Redis (cheap, fast, shared across workers).

**Files:**
- Create: `src/Teleframe/Ingest/PtsWatermark.php`
- Test: `tests/Ingest/PtsWatermarkTest.php`

**Interfaces:**
- Consumes: Redis via `RedisConnectionContract` (same contract `IngestConsumer` uses)
- Produces: `PtsWatermark` — `get(int $accountId): ?array`, `put(int $accountId, array $state): void`, `touchChannel(int $accountId, int $channelId, int $pts): void`, `getChannelPts(int $accountId, int $channelId): ?int`

**Redis key schema:**
```
tg:pts:{account_id}         → hash {pts, date, qts, seq}
tg:pts:{account_id}:channels → hash {channel_id → pts}
```

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Ingest;

use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Ingest\PtsWatermark;
use PHPUnit\Framework\TestCase;

class PtsWatermarkTest extends TestCase
{
    private LaravelRedisAdapter $redis;
    private PtsWatermark $watermark;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = new LaravelRedisAdapter(
            \Illuminate\Support\Facades\Redis::connection('default')->client()
        );
        $this->watermark = new PtsWatermark($this->redis);

        // Clean slate
        $this->redis->del('tg:pts:100');
        $this->redis->del('tg:pts:100:channels');
    }

    public function test_get_returns_null_for_unknown_account(): void
    {
        $this->assertNull($this->watermark->get(999));
    }

    public function test_put_and_get_roundtrip(): void
    {
        $state = ['pts' => 42, 'date' => 1700000000, 'qts' => 0, 'seq' => 5];
        $this->watermark->put(100, $state);

        $loaded = $this->watermark->get(100);
        $this->assertSame($state, $loaded);
    }

    public function test_channel_pts_roundtrip(): void
    {
        $this->watermark->touchChannel(100, 200, 10);
        $this->watermark->touchChannel(100, 200, 15);
        $this->watermark->touchChannel(100, 200, 12); // monotonic: stays 15

        $this->assertSame(15, $this->watermark->getChannelPts(100, 200));
        $this->assertNull($this->watermark->getChannelPts(100, 999));
    }

    public function test_channel_pts_is_per_account_isolated(): void
    {
        $this->watermark->touchChannel(100, 200, 10);
        $this->watermark->touchChannel(200, 200, 50);

        $this->assertSame(10, $this->watermark->getChannelPts(100, 200));
        $this->assertSame(50, $this->watermark->getChannelPts(200, 200));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Ingest/PtsWatermarkTest.php`
Expected: FAIL — class `PtsWatermark` not found

- [ ] **Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;

/**
 * Redis-backed per-account pts watermark (TDLib §updates state persistence).
 *
 * Stores the four-valued sequence state {pts, date, qts, seq} and the
 * per-channel pts map so the poller can resume across restarts. Keys
 * expire after 30 days — a dead account's watermark naturally reclaims.
 */
final class PtsWatermark
{
    private const TTL_SECONDS = 30 * 24 * 3600; // 30 days

    public function __construct(
        private readonly RedisConnectionContract $redis,
    ) {}

    /**
     * Load the persisted sequence state for an account, or null on first poll.
     *
     * @return array{pts: int, date: int, qts: int, seq: int}|null
     */
    public function get(int $accountId): ?array
    {
        $raw = $this->redis->hgetall(self::stateKey($accountId));

        if ($raw === [] || !isset($raw['pts'])) {
            return null;
        }

        return [
            'pts'  => (int) ($raw['pts'] ?? 0),
            'date' => (int) ($raw['date'] ?? 0),
            'qts'  => (int) ($raw['qts'] ?? 0),
            'seq'  => (int) ($raw['seq'] ?? 0),
        ];
    }

    /**
     * Persist the sequence state for an account.
     *
     * @param array{pts: int, date: int, qts: int, seq: int} $state
     */
    public function put(int $accountId, array $state): void
    {
        $key = self::stateKey($accountId);
        $this->redis->hset($key, [
            'pts'  => (string) $state['pts'],
            'date' => (string) $state['date'],
            'qts'  => (string) $state['qts'],
            'seq'  => (string) $state['seq'],
        ]);
        $this->redis->expire($key, self::TTL_SECONDS);
    }

    /**
     * Record a channel pts observation (monotonic — only advances forward).
     */
    public function touchChannel(int $accountId, int $channelId, int $pts): void
    {
        $key = self::channelKey($accountId);
        $current = $this->redis->hget($key, (string) $channelId);

        if ($current !== null && (int) $current >= $pts) {
            return; // already ahead
        }

        $this->redis->hset($key, [(string) $channelId => (string) $pts]);
        $this->redis->expire($key, self::TTL_SECONDS);
    }

    /**
     * Last observed pts for a channel, or null.
     */
    public function getChannelPts(int $accountId, int $channelId): ?int
    {
        $val = $this->redis->hget(self::channelKey($accountId), (string) $channelId);

        return $val !== null ? (int) $val : null;
    }

    private static function stateKey(int $accountId): string
    {
        return "tg:pts:{$accountId}";
    }

    private static function channelKey(int $accountId): string
    {
        return "tg:pts:{$accountId}:channels";
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Ingest/PtsWatermarkTest.php`
Expected: 4 tests PASS

- [ ] **Step 5: Commit**

```bash
git add src/Teleframe/Ingest/PtsWatermark.php tests/Ingest/PtsWatermarkTest.php
git commit -m "feat(ingest): Redis-backed pts watermark for restart-safe polling"
```

---

### Task 2: Safe-Delete Operations — TDLib's Explicit-Delete Model

**Why:** TDLib treats deletes as first-class operations: `updateDeleteMessages`, `updateDeleteChannelMessages` remove rows from the domain table and fire side-effects. Our current `UpdateIngestor::ingest()` handles inserts/upserts but has no delete path. This task adds a `SafeDelete` service that:
1. Reads the message IDs from a delete update
2. Soft-deletes the matching rows (sets `deleted_at`)
3. Fires a new `MessagesDeleted` event so consumers can react

TDLib uses `INSERT OR REPLACE` for everything — deletes are explicit row removals, not tombstones. We follow the same pattern: explicit delete with an event, not a soft-delete flag. However, we use soft-delete (`deleted_at`) so the data is recoverable and the event path can query what was deleted before the row vanishes.

**Files:**
- Create: `src/Teleframe/Ingest/SafeDelete.php`
- Create: `src/Teleframe/Ingest/Events/MessagesDeleted.php`
- Test: `tests/Ingest/SafeDeleteTest.php`

**Interfaces:**
- Consumes: `TlMessage` model (generated), `DB::table()`, `Dispatcher`
- Produces: `SafeDelete::deleteMessages(int $accountId, array $messageIds, ?int $channelId): int` — returns count of deleted rows; `MessagesDeleted` event

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\MessagesDeleted;
use MeRezaRezaei\Teleframe\Ingest\SafeDelete;
use Tests\TestCase;

class SafeDeleteTest extends TestCase
{
    private SafeDelete $safeDelete;

    protected function setUp(): void
    {
        parent::setUp();
        $this->safeDelete = new SafeDelete();
        Event::fake([MessagesDeleted::class]);
    }

    public function test_delete_messages_removes_matching_rows(): void
    {
        // Seed test data
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

    public function test_delete_messages_fires_event_with_model_ids(): void
    {
        DB::table('tf_messages')->insert([
            ['peer_id' => 2001, 'message_id' => 5, 'account_id' => 2, 'constructor_id' => 0, 'tl_data' => '{}', 'created_at' => now()],
        ]);

        $this->safeDelete->deleteMessages(2, [5]);

        Event::assertDispatched(MessagesDeleted::class, function (MessagesDeleted $event): bool {
            return $event->accountId === 2
                && $event->messageIds === [5]
                && $event->peerId === null; // user-path (no channel)
        });
    }

    public function test_delete_nonexistent_messages_is_noop(): void
    {
        $deleted = $this->safeDelete->deleteMessages(1, [99999]);
        $this->assertSame(0, $deleted);
        Event::assertNotDispatched(MessagesDeleted::class);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Ingest/SafeDeleteTest.php`
Expected: FAIL — class `SafeDelete` not found

- [ ] **Step 3: Write the event**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Events;

/**
 * Fired after messages are explicitly deleted from tf_messages
 * (TDLib updateDeleteMessages / updateDeleteChannelMessages path).
 */
final class MessagesDeleted
{
    /**
     * @param list<int> $messageIds Telegram message IDs that were deleted
     */
    public function __construct(
        public readonly int $accountId,
        public readonly array $messageIds,
        public readonly ?int $peerId = null,
    ) {}
}
```

- [ ] **Step 4: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Events\MessagesDeleted;

/**
 * TDLib-style explicit delete operations: remove messages from tf_messages
 * by their Telegram message IDs. Fires MessagesDeleted so consumers
 * (Centrifugo, Redis bus, handler pipeline) can react to deletions.
 */
final class SafeDelete
{
    public function __construct(
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * Delete messages by their Telegram IDs.
     *
     * @param list<int> $messageIds
     * @return int Number of rows actually deleted
     */
    public function deleteMessages(int $accountId, array $messageIds, ?int $peerId = null): int
    {
        if ($messageIds === []) {
            return 0;
        }

        $deleted = DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->whereIn('message_id', $messageIds)
            ->delete();

        if ($deleted > 0) {
            $this->events?->dispatch(new MessagesDeleted(
                $accountId,
                $messageIds,
                $peerId,
            ));
        }

        return $deleted;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Ingest/SafeDeleteTest.php`
Expected: 3 tests PASS

- [ ] **Step 6: Commit**

```bash
git add src/Teleframe/Ingest/SafeDelete.php src/Teleframe/Ingest/Events/MessagesDeleted.php tests/Ingest/SafeDeleteTest.php
git commit -m "feat(ingest): SafeDelete with MessagesDeleted event for explicit deletes"
```

---

### Task 3: Domain Query Builders — Complex Relational Queries

**Why:** TDLib never JOINs across domains — cross-domain resolution is in application code (which validates our `EntityAggregator` pattern). But within a domain, TDLib runs rich queries: message history by peer, dialog list, user lookup by username. We need typed Eloquent query builders so consumers can run complex ORM queries against the domain tables without hand-crafting `DB::table()` everywhere.

**Files:**
- Create: `src/Teleframe/Ingest/Queries/MessageQuery.php`
- Create: `src/Teleframe/Ingest/Queries/DialogQuery.php`
- Create: `src/Teleframe/Ingest/Queries/UserQuery.php`
- Modify: `src/Schema/Generated/Models/TlMessage.php` (add `newQuery()` override)
- Modify: `src/Schema/Generated/Models/TlDialog.php` (add `newQuery()` override)
- Modify: `src/Schema/Generated/Models/TlUser.php` (add `newQuery()` override)
- Test: `tests/Ingest/Queries/MessageQueryTest.php`

**Interfaces:**
- Consumes: `TlMessage`, `TlDialog`, `TlUser` models (generated), `PeerIdTool`
- Produces: `MessageQuery`, `DialogQuery`, `UserQuery` — typed Eloquent builders

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Queries\MessageQuery;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use Tests\TestCase;

class MessageQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Seed messages for different peers and accounts
        $peer = PeerIdTool::userLong(500);
        DB::table('tf_messages')->insert([
            ['peer_id' => $peer, 'message_id' => 1, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{"_":"message","message":"hello"}', 'message_text' => 'hello', 'created_at' => now()->subHour(2)],
            ['peer_id' => $peer, 'message_id' => 2, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{"_":"message","message":"world"}', 'message_text' => 'world', 'created_at' => now()->subMinute(30)],
            ['peer_id' => $peer, 'message_id' => 3, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{"_":"message","message":"latest"}', 'message_text' => 'latest', 'created_at' => now()],
            // Different account
            ['peer_id' => $peer, 'message_id' => 1, 'account_id' => 2, 'constructor_id' => 0, 'tl_data' => '{"_":"message","message":"other"}', 'message_text' => 'other', 'created_at' => now()],
        ]);
    }

    public function test_for_account_scopes_by_account_id(): void
    {
        $results = (new MessageQuery())->forAccount(1)->get();
        $this->assertCount(3, $results);
    }

    public function test_for_peer_filters_by_peer_id(): void
    {
        $peer = PeerIdTool::userLong(500);
        $results = (new MessageQuery())->forAccount(1)->forPeer($peer)->get();
        $this->assertCount(3, $results);
    }

    public function test_latest_returns_most_recent_first(): void
    {
        $results = (new MessageQuery())->forAccount(1)->latest(2)->get();
        $this->assertCount(2, $results);
        // Most recent first
        $this->assertSame('latest', $results[0]->message_text);
        $this->assertSame('world', $results[1]->message_text);
    }

    public function test_since_filters_by_timestamp(): void
    {
        $cutoff = now()->subMinutes(45)->timestamp;
        $results = (new MessageQuery())->forAccount(1)->since($cutoff)->get();
        $this->assertCount(2, $results); // world + latest
    }

    public function test_before_id_keyset_pagination(): void
    {
        $peer = PeerIdTool::userLong(500);
        $results = (new MessageQuery())
            ->forAccount(1)
            ->forPeer($peer)
            ->beforeId(2, 2) // message_id < 2, limit 2
            ->get();
        $this->assertCount(1, $results); // only message_id=1
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Ingest/Queries/MessageQueryTest.php`
Expected: FAIL — class `MessageQuery` not found

- [ ] **Step 3: Write MessageQuery**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;

/**
 * Typed query builder for tf_messages: message history, keyset pagination,
 * and peer-scoped retrieval. Follows TDLib's "no JOINs, query within domain"
 * principle.
 */
final class MessageQuery extends Builder
{
    public function __construct(TlMessage $model)
    {
        parent::__construct($model);
    }

    /** Scope to one account's messages. */
    public function forAccount(int $accountId): self
    {
        return $this->where('account_id', $accountId);
    }

    /** Scope to messages belonging to one peer (chat/user/channel). */
    public function forPeer(int $peerId): self
    {
        return $this->where('peer_id', $peerId);
    }

    /** Most-recent-N ordering (TDLib default: descending message_id). */
    public function latest(int $limit = 20): self
    {
        return $this->orderByDesc('message_id')->limit($limit);
    }

    /** Messages newer than a unix timestamp. */
    public function since(int $unixTimestamp): self
    {
        return $this->where('created_at', '>=', date('Y-m-d H:i:s', $unixTimestamp));
    }

    /**
     * Keyset pagination (TDLib pattern): fetch messages before a given
     * message_id, avoiding OFFSET. Cursor = (message_id).
     */
    public function beforeId(int $messageId, int $limit = 50): self
    {
        return $this->where('message_id', '<', $messageId)
            ->orderByDesc('message_id')
            ->limit($limit);
    }

    /**
     * Keyset pagination forward: fetch messages after a given message_id.
     */
    public function afterId(int $messageId, int $limit = 50): self
    {
        return $this->where('message_id', '>', $messageId)
            ->orderBy('message_id')
            ->limit($limit);
    }
}
```

- [ ] **Step 4: Write DialogQuery**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialog;

/**
 * Typed query builder for tf_dialogs: dialog list with pin/folder/unread
 * filtering and keyset pagination.
 */
final class DialogQuery extends Builder
{
    public function __construct(TlDialog $model)
    {
        parent::__construct($model);
    }

    public function forAccount(int $accountId): self
    {
        return $this->where('account_id', $accountId);
    }

    /** Pinned dialogs first, then by top_message_id descending. */
    public function ordered(): self
    {
        return $this->orderByDesc('is_pinned')
            ->orderByDesc('top_message_id');
    }

    /** Only pinned dialogs. */
    public function pinned(): self
    {
        return $this->where('is_pinned', true);
    }

    /** Only dialogs with unread messages. */
    public function withUnread(): self
    {
        return $this->where('unread_count', '>', 0);
    }

    /** Scope to a folder. */
    public function inFolder(int $folderId): self
    {
        return $this->where('folder_id', $folderId);
    }
}
```

- [ ] **Step 5: Write UserQuery**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;

/**
 * Typed query builder for tf_users: user lookup by username, phone,
 * contact status, and bot flag.
 */
final class UserQuery extends Builder
{
    public function __construct(TlUser $model)
    {
        parent::__construct($model);
    }

    public function forAccount(int $accountId): self
    {
        return $this->where('account_id', $accountId);
    }

    /** Find by exact username (case-insensitive via Postgres ILIKE). */
    public function byUsername(string $username): self
    {
        return $this->whereRaw('LOWER(username) = LOWER(?)', [$username]);
    }

    /** Find by phone number. */
    public function byPhone(string $phone): self
    {
        return $this->where('phone', $phone);
    }

    /** Only bots. */
    public function bots(): self
    {
        return $this->where('is_bot', true);
    }

    /** Only non-bot users. */
    public function humans(): self
    {
        return $this->where('is_bot', false);
    }

    /** Only contacts. */
    public function contacts(): self
    {
        return $this->where('is_contact', true);
    }

    /** Only self. */
    public function self(): self
    {
        return $this->where('is_self', true);
    }
}
```

- [ ] **Step 6: Wire query builders into models**

Add to `src/Schema/Generated/Models/TlMessage.php` (inside the class body):

```php
    public function newEloquentBuilder($query): \MeRezaRezaei\Teleframe\Ingest\Queries\MessageQuery
    {
        return new \MeRezaRezaei\Teleframe\Ingest\Queries\MessageQuery($query);
    }
```

Add to `src/Schema/Generated/Models/TlDialog.php` (inside the class body):

```php
    public function newEloquentBuilder($query): \MeRezaRezaei\Teleframe\Ingest\Queries\DialogQuery
    {
        return new \MeRezaRezaei\Teleframe\Ingest\Queries\DialogQuery($query);
    }
```

Add to `src/Schema/Generated/Models/TlUser.php` (inside the class body):

```php
    public function newEloquentBuilder($query): \MeRezaRezaei\Teleframe\Ingest\Queries\UserQuery
    {
        return new \MeRezaRezaei\Teleframe\Ingest\Queries\UserQuery($query);
    }
```

- [ ] **Step 7: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Ingest/Queries/MessageQueryTest.php`
Expected: 5 tests PASS

- [ ] **Step 8: Commit**

```bash
git add src/Teleframe/Ingest/Queries/MessageQuery.php src/Teleframe/Ingest/Queries/DialogQuery.php src/Teleframe/Ingest/Queries/UserQuery.php
git commit -m "feat(ingest): typed query builders for domain models (keyset pagination, peer/peer scoping)"
```

---

### Task 4: Media Flags Bitmask — TDLib's `index_mask` Pattern

**Why:** TDLib uses a 30-bit `index_mask` integer where each bit represents "has photo", "has video", etc., with partial indexes on each bit. This lets us filter messages by media type with a single integer comparison + partial index instead of scanning JSONB. We add a `media_flags` column to `tf_messages` and extract flags during ingest.

**Bitmask definitions (30 bits available):**
```
Bit 0 (1):     has_photo
Bit 1 (2):     has_video
Bit 2 (4):     has_document
Bit 3 (8):     has_sticker
Bit 4 (16):    has_voice
Bit 5 (32):    has_video_note
Bit 6 (64):    has_location
Bit 7 (128):   has_contact
Bit 8 (256):   has_poll
Bit 9 (512):   has_inline_result
Bit 10 (1024): has_invoice
```

**Files:**
- Create: `src/Teleframe/Ingest/MediaFlags.php`
- Modify: migration (generated by regeneration, or manual add-column for the `tf_messages` table)
- Modify: `src/Teleframe/Ingest/UpdateIngestor.php` (call `MediaFlags::fromPayload()` in `writeNode()`)
- Test: `tests/Ingest/MediaFlagsTest.php`

**Interfaces:**
- Consumes: raw TL payload array
- Produces: `MediaFlags::fromPayload(array $payload): int` — bitmask integer; `MediaFlags::has(int $flags, string $flag): bool`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\MediaFlags;
use PHPUnit\Framework\TestCase;

class MediaFlagsTest extends TestCase
{
    public function test_empty_payload_returns_zero(): void
    {
        $this->assertSame(0, MediaFlags::fromPayload(['_' => 'message']));
    }

    public function test_photo_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaPhoto'],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'photo'));
        $this->assertFalse(MediaFlags::has($flags, 'video'));
    }

    public function test_video_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaVideo'],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'video'));
    }

    public function test_document_with_sticker(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => [
                '_' => 'messageMediaDocument',
                'document' => [
                    '_' => 'document',
                    'attributes' => [
                        ['_' => 'documentAttributeSticker'],
                    ],
                ],
            ],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'document'));
        $this->assertTrue(MediaFlags::has($flags, 'sticker'));
        $this->assertFalse(MediaFlags::has($flags, 'photo'));
    }

    public function test_voice_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaAudio', 'audio' => ['_' => 'audio', 'voice' => true]],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'voice'));
    }

    public function test_location_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaGeo'],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'location'));
    }

    public function test_poll_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaPoll'],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'poll'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Ingest/MediaFlagsTest.php`
Expected: FAIL — class `MediaFlags` not found

- [ ] **Step 3: Write implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

/**
 * TDLib-inspired index_mask pattern: a 30-bit bitmask where each bit
 * represents "has media type X". Stored as an integer column on tf_messages
 * with partial GIN/btree indexes per flag for fast filtering.
 *
 * Bit assignments:
 *   0 (0x001) photo
 *   1 (0x002) video
 *   2 (0x004) document
 *   3 (0x008) sticker
 *   4 (0x010) voice
 *   5 (0x020) video_note
 *   6 (0x040) location
 *   7 (0x080) contact
 *   8 (0x100) poll
 *   9 (0x200) inline_result
 *  10 (0x400) invoice
 */
final class MediaFlags
{
    private const FLAGS = [
        'photo'          => 1 << 0,
        'video'          => 1 << 1,
        'document'       => 1 << 2,
        'sticker'        => 1 << 3,
        'voice'          => 1 << 4,
        'video_note'     => 1 << 5,
        'location'       => 1 << 6,
        'contact'        => 1 << 7,
        'poll'           => 1 << 8,
        'inline_result'  => 1 << 9,
        'invoice'        => 1 << 10,
    ];

    /**
     * Extract media flags from a raw TL message payload.
     */
    public static function fromPayload(array $payload): int
    {
        $media = $payload['media'] ?? null;
        if (!is_array($media) || !isset($media['_'])) {
            return 0;
        }

        $ctor = (string) $media['_'];
        $flags = 0;

        return match ($ctor) {
            'messageMediaPhoto' => self::FLAGS['photo'],
            'messageMediaGeo', 'messageMediaGeoLive' => self::FLAGS['location'],
            'messageMediaContact' => self::FLAGS['contact'],
            'messageMediaPoll' => self::FLAGS['poll'],
            'messageMediaInvoice' => self::FLAGS['invoice'],
            'messageMediaWebPage' => isset($media['webpage']['document'])
                ? self::FLAGS['document']
                : (isset($media['webpage']['photo']) ? self::FLAGS['photo'] : 0),
            'messageMediaDocument' => self::documentFlags($media),
            'messageMediaAudio' => isset($media['audio']['voice']) && $media['audio']['voice']
                ? self::FLAGS['voice']
                : self::FLAGS['document'],
            'messageMediaVideo' => self::FLAGS['video'],
            'messageMediaDice' => 0,
            default => 0,
        };
    }

    /**
     * Check if a specific flag is set.
     */
    public static function has(int $flags, string $flag): bool
    {
        $bit = self::FLAGS[$flag] ?? 0;
        return $bit !== 0 && ($flags & $bit) !== 0;
    }

    /**
     * All defined flag names.
     *
     * @return array<string, int>
     */
    public static function all(): array
    {
        return self::FLAGS;
    }

    private static function documentFlags(array $media): int
    {
        $flags = self::FLAGS['document'];
        $doc = $media['document'] ?? null;

        if (!is_array($doc) || !isset($doc['attributes'])) {
            return $flags;
        }

        foreach ($doc['attributes'] as $attr) {
            if (!is_array($attr) || !isset($attr['_'])) {
                continue;
            }
            $flags |= match ($attr['_']) {
                'documentAttributeSticker' => self::FLAGS['sticker'],
                'documentAttributeVideo' => self::FLAGS['video'],
                'documentAttributeAudio' => isset($attr['voice']) && $attr['voice']
                    ? self::FLAGS['voice']
                    : 0,
                default => 0,
            };
        }

        return $flags;
    }
}
```

- [ ] **Step 4: Add media_flags column to tf_messages**

Create a migration manually (not generated, since this is a domain-table schema change):

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tf_messages', function (Blueprint $table) {
            $table->integer('media_flags')->default(0)->after('tl_data');
        });

        // Partial indexes for fast media-type filtering (TDLib index_mask pattern)
        DB::statement('CREATE INDEX idx_tf_messages_media_photo ON tf_messages (account_id, peer_id) WHERE (media_flags & 1) != 0');
        DB::statement('CREATE INDEX idx_tf_messages_media_video ON tf_messages (account_id, peer_id) WHERE (media_flags & 2) != 0');
        DB::statement('CREATE INDEX idx_tf_messages_media_document ON tf_messages (account_id, peer_id) WHERE (media_flags & 4) != 0');
        DB::statement('CREATE INDEX idx_tf_messages_media_sticker ON tf_messages (account_id, peer_id) WHERE (media_flags & 8) != 0');
    }

    public function down(): void
    {
        Schema::table('tf_messages', function (Blueprint $table) {
            $table->dropColumn('media_flags');
        });
    }
};
```

- [ ] **Step 5: Wire into UpdateIngestor**

In `src/Teleframe/Ingest/UpdateIngestor.php`, add after `$columns` is built in `writeNode()`:

```php
        // Extract media flags bitmask (TDLib index_mask pattern)
        if ($domain === 'messages') {
            $columns['media_flags'] = MediaFlags::fromPayload($payload);
        }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Ingest/MediaFlagsTest.php`
Expected: 7 tests PASS

- [ ] **Step 7: Commit**

```bash
git add src/Teleframe/Ingest/MediaFlags.php tests/Ingest/MediaFlagsTest.php
git commit -m "feat(ingest): media_flags bitmask (TDLib index_mask) for fast media-type filtering"
```

---

### Task 5: Full-Text Search on Messages — Postgres tsvector + GIN

**Why:** TDLib uses SQLite FTS5 with INSERT/DELETE triggers to maintain a virtual table. Postgres tsvector + GIN index is strictly better: no triggers needed (computed column), native language support, phrase search, and ranking. We add a `search_vector` column to `tf_messages` and populate it from `message_text` during ingest.

**Files:**
- Create: migration (manual, adds `search_vector` column + GIN index)
- Modify: `src/Teleframe/Ingest/UpdateIngestor.php` (populate `search_vector` for messages)
- Create: `src/Teleframe/Ingest/Queries/MessageSearchQuery.php`
- Test: `tests/Ingest/Queries/MessageSearchQueryTest.php`

**Interfaces:**
- Consumes: `message_text` column, Postgres `to_tsvector()`
- Produces: `MessageSearchQuery::search(int $accountId, string $query, ?int $peerId): Builder` — returns ranked results

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Queries\MessageSearchQuery;
use Tests\TestCase;

class MessageSearchQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('tf_messages')->insert([
            [
                'peer_id' => 1001, 'message_id' => 1, 'account_id' => 1,
                'constructor_id' => 0, 'tl_data' => '{}',
                'message_text' => 'hello world',
                'search_vector' => DB::raw("to_tsvector('english', 'hello world')"),
                'created_at' => now(),
            ],
            [
                'peer_id' => 1001, 'message_id' => 2, 'account_id' => 1,
                'constructor_id' => 0, 'tl_data' => '{}',
                'message_text' => 'the quick brown fox',
                'search_vector' => DB::raw("to_tsvector('english', 'the quick brown fox')"),
                'created_at' => now(),
            ],
            [
                'peer_id' => 1001, 'message_id' => 3, 'account_id' => 1,
                'constructor_id' => 0, 'tl_data' => '{}',
                'message_text' => 'hello there friend',
                'search_vector' => DB::raw("to_tsvector('english', 'hello there friend')"),
                'created_at' => now(),
            ],
        ]);
    }

    public function test_search_finds_matching_messages(): void
    {
        $results = (new MessageSearchQuery())->search(1, 'hello');
        $this->assertCount(2, $results);
    }

    public function test_search_scopes_to_account(): void
    {
        DB::table('tf_messages')->insert([
            'peer_id' => 1001, 'message_id' => 10, 'account_id' => 2,
            'constructor_id' => 0, 'tl_data' => '{}',
            'message_text' => 'hello from other account',
            'search_vector' => DB::raw("to_tsvector('english', 'hello from other account')"),
            'created_at' => now(),
        ]);

        $results = (new MessageSearchQuery())->search(1, 'hello');
        $this->assertCount(2, $results); // only account 1
    }

    public function test_search_with_peer_filter(): void
    {
        $results = (new MessageSearchQuery())->search(1, 'hello', peerId: 1001);
        $this->assertCount(2, $results);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Ingest/Queries/MessageSearchQueryTest.php`
Expected: FAIL — class `MessageSearchQuery` not found

- [ ] **Step 3: Write MessageSearchQuery**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Support\Facades\DB;

/**
 * Full-text search on tf_messages using Postgres tsvector + GIN index.
 * Replaces TDLib's FTS5+trigger approach with a strictly superior pattern:
 * computed column (no triggers), native language support, phrase search, ranking.
 */
final class MessageSearchQuery
{
    /**
     * Search messages by full-text query, returning results ranked by
     * ts_rank relevance. Uses Postgres tsquery syntax.
     *
     * @param string $query User search string (auto-converted to tsquery)
     * @return array<int, object> Eloquent model instances
     */
    public function search(int $accountId, string $query, ?int $peerId = null, int $limit = 50): array
    {
        $tsQuery = DB::raw("plainto_tsquery('english', ?)");

        $builder = DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->whereRaw('search_vector @@ plainto_tsquery(\'english\', ?)', [$query])
            ->select('*', DB::raw("ts_rank(search_vector, plainto_tsquery('english', ?)) AS rank", [$query]))
            ->orderByDesc('rank')
            ->limit($limit);

        if ($peerId !== null) {
            $builder->where('peer_id', $peerId);
        }

        return $builder->get()->all();
    }
}
```

- [ ] **Step 4: Add search_vector column migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tf_messages', function (Blueprint $table) {
            $table->addColumn('tsvector', 'search_vector');
        });

        // GIN index for fast full-text search
        DB::statement('CREATE INDEX idx_tf_messages_search ON tf_messages USING GIN (search_vector)');

        // Populate existing rows
        DB::statement("UPDATE tf_messages SET search_vector = to_tsvector('english', COALESCE(message_text, '')) WHERE search_vector IS NULL");
    }

    public function down(): void
    {
        Schema::table('tf_messages', function (Blueprint $table) {
            $table->dropColumn('search_vector');
        });
    }
};
```

- [ ] **Step 5: Populate search_vector during ingest**

In `src/Teleframe/Ingest/UpdateIngestor.php`, add after `media_flags` in `writeNode()`:

```php
        // Populate tsvector for full-text search (Postgres computed column)
        if ($domain === 'messages' && isset($columns['message_text'])) {
            $columns['search_vector'] = DB::raw(
                "to_tsvector('english', COALESCE(?, ''))"
            );
            // Note: binding is unsafe here — use raw SQL for the insert
        }
```

Better approach — add a trigger instead of application-level population (Postgres is better at this):

```sql
CREATE OR REPLACE FUNCTION tf_messages_search_vector_update() RETURNS trigger AS $$
BEGIN
    NEW.search_vector := to_tsvector('english', COALESCE(NEW.message_text, ''));
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tf_messages_search_vector_trigger
    BEFORE INSERT OR UPDATE OF message_text ON tf_messages
    FOR EACH ROW
    EXECUTE FUNCTION tf_messages_search_vector_update();
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Ingest/Queries/MessageSearchQueryTest.php`
Expected: 3 tests PASS

- [ ] **Step 7: Commit**

```bash
git add src/Teleframe/Ingest/Queries/MessageSearchQuery.php tests/Ingest/Queries/MessageSearchQueryTest.php
git commit -m "feat(ingest): Postgres tsvector full-text search on tf_messages"
```

---

### Task 6: TTL Janitor — Periodic Expiry Sweep

**Why:** TDLib runs a periodic loop: `DELETE FROM table WHERE expires_at <= now() LIMIT N`, using a double limit on full batches. We need the same pattern for `tf_stories` (expire_date) and future TTL'd entities. The janitor is a standalone service that a daemon or scheduler can invoke.

**Files:**
- Create: `src/Teleframe/Ingest/TtlJanitor.php`
- Test: `tests/Ingest/TtlJanitorTest.php`

**Interfaces:**
- Consumes: `DB::table()`, configurable table/TTL column mappings
- Produces: `TtlJanitor::sweep(): array{table: string, deleted: int}[]`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\TtlJanitor;
use Tests\TestCase;

class TtlJanitorTest extends TestCase
{
    public function test_sweep_removes_expired_rows(): void
    {
        // Seed stories — some expired, some not
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
        // Seed 5 expired rows
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
        $this->assertSame(2, $storyResult['deleted']); // limited to 2
    }

    public function test_sweep_returns_empty_when_no_expired(): void
    {
        $janitor = new TtlJanitor();
        $results = $janitor->sweep();
        $this->assertSame([], $results);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Ingest/TtlJanitorTest.php`
Expected: FAIL — class `TtlJanitor` not found

- [ ] **Step 3: Write implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Support\Facades\DB;

/**
 * TDLib-style TTL janitor: periodic expiry sweep that removes rows
 * whose TTL column has passed. Uses TDLib's double-limit pattern:
 * DELETE ... WHERE expires_at <= now() LIMIT batchSize, repeated
 * until a batch returns fewer than batchSize rows.
 *
 * Wired into the daemon's main loop or a scheduler:
 *   $janitor->sweep();
 */
final class TtlJanitor
{
    /** Default batch size (TDLib uses 101). */
    private const DEFAULT_BATCH = 101;

    /** Tables with TTL columns that the janitor sweeps. */
    private const TTL_TABLES = [
        'tf_stories' => 'expire_date',
    ];

    public function __construct(
        private readonly int $batchSize = self::DEFAULT_BATCH,
    ) {}

    /**
     * Sweep all configured TTL tables. Returns per-table deletion counts.
     *
     * @return list<array{table: string, deleted: int}>
     */
    public function sweep(): array
    {
        $results = [];
        $now = now();

        foreach (self::TTL_TABLES as $table => $ttlColumn) {
            $totalDeleted = 0;

            // TDLib double-limit pattern: keep deleting in batches until
            // a batch returns fewer than batchSize (meaning we're done).
            do {
                $deleted = DB::table($table)
                    ->where($ttlColumn, '<=', $now)
                    ->limit($this->batchSize)
                    ->delete();

                $totalDeleted += $deleted;
            } while ($deleted === $this->batchSize);

            if ($totalDeleted > 0) {
                $results[] = ['table' => $table, 'deleted' => $totalDeleted];
            }
        }

        return $results;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Ingest/TtlJanitorTest.php`
Expected: 3 tests PASS

- [ ] **Step 5: Commit**

```bash
git add src/Teleframe/Ingest/TtlJanitor.php tests/Ingest/TtlJanitorTest.php
git commit -m "feat(ingest): TTL janitor with TDLib double-limit expiry sweep"
```

---

### Task 7: Redis Stream Dedup — Prevent Duplicate Update Processing

**Why:** The current `IngestConsumer` deduplicates method responses via `RouteIdempotency` (UUIDv5 route tables), but update-kind payloads (`str_starts_with($ctor, 'update')`) bypass routes and always go through `ingest()`. Telegram can deliver the same update twice (pts retransmit, gap recovery), and the Redis stream can redeliver on consumer crash. We need a lightweight Redis SET-based dedup that checks before calling `ingest()`.

**TDLib comparison:** TDLib uses `INSERT OR REPLACE` (upsert) as its implicit dedup — the same message written twice is a no-op. Our `UpdateIngestor::findExisting()` already does upsert, but it hits the DB every time. A Redis Bloom/SET check before the DB write saves significant I/O under high throughput.

**Files:**
- Modify: `src/Teleframe/Bus/IngestConsumer.php` (add dedup check before `ingest()`)
- Create: `src/Teleframe/Bus/UpdateDedup.php`
- Test: `tests/Bus/UpdateDedupTest.php`

**Interfaces:**
- Consumes: Redis via `RedisConnectionContract`
- Produces: `UpdateDedup::seen(int $accountId, string $updateHash): bool`, `UpdateDedup::mark(int $accountId, string $updateHash): void`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\UpdateDedup;
use PHPUnit\Framework\TestCase;

class UpdateDedupTest extends TestCase
{
    private LaravelRedisAdapter $redis;
    private UpdateDedup $dedup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = new LaravelRedisAdapter(
            \Illuminate\Support\Facades\Redis::connection('default')->client()
        );
        $this->dedup = new UpdateDedup($this->redis);

        // Clean slate
        $keys = $this->redis->keys('tg:dedup:*');
        foreach ($keys as $key) {
            $this->redis->del($key);
        }
    }

    public function test_first_time_returns_false(): void
    {
        $this->assertFalse($this->dedup->seen(1, 'hash_abc'));
    }

    public function test_after_mark_returns_true(): void
    {
        $this->dedup->mark(1, 'hash_abc');
        $this->assertTrue($this->dedup->seen(1, 'hash_abc'));
    }

    public function test_dedup_is_per_account(): void
    {
        $this->dedup->mark(1, 'hash_abc');
        $this->assertTrue($this->dedup->seen(1, 'hash_abc'));
        $this->assertFalse($this->dedup->seen(2, 'hash_abc'));
    }

    public function test_keys_expire(): void
    {
        $this->dedup->mark(1, 'hash_abc');
        // Key should exist with a TTL
        $ttl = $this->redis->ttl('tg:dedup:1:hash_abc');
        $this->assertGreaterThan(0, $ttl);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Bus/UpdateDedupTest.php`
Expected: FAIL — class `UpdateDedup` not found

- [ ] **Step 3: Write implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

/**
 * Redis SET-based dedup for update payloads on the ingest path.
 * Prevents duplicate processing when Telegram retransmits updates
 * (pts retransmit, gap recovery) or the Redis stream redelivers
 * on consumer crash.
 *
 * TTL = 1 hour: enough to cover gap-recovery windows, short enough
 * to avoid unbounded Redis memory growth.
 */
final class UpdateDedup
{
    private const TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly RedisConnectionContract $redis,
    ) {}

    /**
     * Has this update hash been seen for this account?
     */
    public function seen(int $accountId, string $updateHash): bool
    {
        return $this->redis->exists(self::key($accountId, $updateHash)) === 1;
    }

    /**
     * Mark an update hash as seen for this account. Sets a TTL to
     * avoid unbounded growth.
     */
    public function mark(int $accountId, string $updateHash): void
    {
        $key = self::key($accountId, $updateHash);
        $this->redis->set($key, '1');
        $this->redis->expire($key, self::TTL_SECONDS);
    }

    private static function key(int $accountId, string $updateHash): string
    {
        return "tg:dedup:{$accountId}:{$updateHash}";
    }
}
```

- [ ] **Step 4: Wire dedup into IngestConsumer**

In `src/Teleframe/Bus/IngestConsumer.php`, add dedup to `handleEntry()`:

```php
    public function __construct(
        private readonly RedisConnectionContract $redis,
        private readonly Teleclient $client,
        ?callable $onStored = null,
        ?callable $onRouted = null,
        private readonly ?UpdateDedup $dedup = null, // NEW
    ) {
```

In `handleEntry()`, before the `try` block that calls `$this->client->ingest()`:

```php
        // Dedup check: skip already-seen updates (pts retransmit, stream redelivery)
        if ($this->dedup !== null) {
            $hash = $this->computeHash($entry);
            if ($this->dedup->seen($entry['account_id'], $hash)) {
                $this->ack($entryId);
                return true;
            }
        }
```

And after successful ingest, add marking:

```php
            $root = $this->client->ingest($entry['update'], $entry['account_id']);

            // Mark as seen after successful ingest
            if ($this->dedup !== null) {
                $hash = $this->computeHash($entry);
                $this->dedup->mark($entry['account_id'], $hash);
            }
```

Add the hash computation method:

```php
    private function computeHash(array $entry): string
    {
        $raw = json_encode($entry['update'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return sha1($entry['account_id'] . "\0" . $raw);
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Bus/UpdateDedupTest.php`
Expected: 4 tests PASS

- [ ] **Step 6: Commit**

```bash
git add src/Teleframe/Bus/UpdateDedup.php src/Teleframe/Bus/IngestConsumer.php tests/Bus/UpdateDedupTest.php
git commit -m "feat(bus): Redis SET dedup for update ingest path (pts retransmit safety)"
```

---

## Verification Gate

After all tasks are complete:

- [ ] **Step 1: Run full test suite**

```bash
composer verify
```

Expected: All tests pass, PHPStan level 5 clean, regeneration idempotent.

- [ ] **Step 2: Standalone smoke**

```bash
php bin/standalone-smoke.php
```

Expected: exit 0 — every module plain-PHP-constructible.

- [ ] **Step 3: Postgres integration (if available)**

```bash
TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg
```

Expected: Postgres truth track passes.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-09-11-tdlib-operational-patterns.md`. Two execution options:

**1. Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration. Tasks 1-7 are independent and can be parallelized in groups.

**2. Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
