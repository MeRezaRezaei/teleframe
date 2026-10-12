<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * A page of messages must land IN FULL.
 *
 * `$first ??= $this->writeMessage(...)` short-circuited the CALL as well as
 * the assignment, so a page stored only its first message and dropped the
 * rest. `messages.getHistory` returns up to 100 per page, so this discarded
 * 99% of fetched history -- silently, with no clue and no error. Measured
 * live on 2026-10-02: a crawl reporting `messages:21` across 6 requests
 * left `COUNT(*) tf_messages = 6`, one row per request.
 *
 * The identical bug was already found and fixed in
 * `writeUpdateContainer()`, 36 lines above `writeEnvelope()` in the same
 * file (UpdateIngestor.php:697-702). This test is the gate that stops it
 * being carried back into `writeEnvelope()`.
 *
 * ## The entry point under test
 *
 * `UpdateIngestor::ingest(array $payload, int $accountId): ?Model`
 * (src/Teleframe/Ingest/UpdateIngestor.php:242) -- TWO arguments, no
 * ConnectionInterface; the connection comes from the Laravel `DB` facade.
 * A `messages.messages` payload whose `messages` member is a list falls
 * through `ingestImpl()`'s ctor branches to `writeEnvelope()`
 * (UpdateIngestor.php:322 -> :698), which is the envelope walker this test
 * measures. `MirrorIngesterFactory`'s `MirrorUpdateIngester` is a different
 * class on a different path and is deliberately NOT used here: the defect
 * lives in `UpdateIngestor::writeEnvelope()`.
 *
 * ## The assertion is N-in N-out, never "at least one"
 *
 * `assertSame([101, 102, 103, 104, 105], $stored)` compares the FULL ordered
 * id list, so it fails both when a message is dropped and when a wrong one
 * is invented. A `assertCount(5, ...)` or `assertGreaterThan(0, ...)` would
 * have passed against the defect.
 */
final class UpdateIngestorWritesEveryEnvelopeMemberTest extends IngestTestCase
{
    private const ACCOUNT = 42;

    /**
     * Tenant 42 (`ACCOUNT`) — the only one.
     *
     * The single arm ingests one `messages.messages` envelope of five messages
     * under `self::ACCOUNT` and asserts every one of them landed. That argument
     * is the caller's Telegram id — `MirrorIngesterFactory.php:160` writes it
     * straight into `tf_messages.account_id` — and the FK there targets
     * `telegram_accounts.user_id`, not the vault row's own `id`. Five rows
     * spanning one tenant, so one tenant is all that has to exist.
     *
     * Production always plants the parent first:
     * `VaultAddAccountCommand.php:80` writes `user_id` at account-add time.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [42];
    }

    public function test_every_message_in_an_envelope_is_written(): void
    {
        $ids = [101, 102, 103, 104, 105];

        $messages = [];
        foreach ($ids as $id) {
            $messages[] = [
                '_' => 'message',
                'id' => $id,
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
                'date' => 1726000000 + $id,
                'message' => "body {$id}",
                'out' => false,
            ];
        }

        $ingester = new UpdateIngestor;
        $ingester->ingest([
            '_' => 'messages.messages',
            'messages' => $messages,
            'chats' => [],
            'users' => [],
        ], self::ACCOUNT);

        $stored = DB::table('tf_messages')
            ->where('account_id', self::ACCOUNT)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($v): int => (int) $v)
            ->all();

        self::assertSame(
            $ids,
            $stored,
            'every message in the envelope must be written, not just the first',
        );
    }
}
