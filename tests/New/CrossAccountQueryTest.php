<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\New;

use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * Cross-account query patterns over the generated tables (plan Task 3.1):
 * the same nested tree ingested into two accounts is queryable across
 * accounts by its canonical peer long, while the default AccountScope keeps
 * each account isolated and AccountContext::for() scopes a block to one
 * tenant. Consumes the canonical HasNestedUpdateFixtures payloads.
 */
final class CrossAccountQueryTest extends IngestTestCase
{
    use HasNestedUpdateFixtures;

    private const ACCOUNT = self::FIXTURE_ACCOUNT;

    private const CHANNEL_ID = self::FIXTURE_CHANNEL_ID;

    private function ingestTree(int $accountId = self::ACCOUNT): void
    {
        $ingestor = new UpdateIngestor();
        $ingestor->ingest(self::channelPayload(), $accountId);
        $ingestor->ingest(self::userPayload(), $accountId);
        $ingestor->ingest(self::updateNewMessagePayload(), $accountId);
    }

    public function test_shared_channel_message_visible_across_accounts_by_canonical_peer(): void
    {
        $this->ingestTree(self::ACCOUNT);
        $this->ingestTree(8);

        $accounts = TlMessageMessage::acrossAccounts()
            ->where('peer_id', PeerIdTool::channelLong(self::CHANNEL_ID))
            ->distinct()
            ->orderBy('account_id')
            ->pluck('account_id')
            ->all();

        self::assertSame([self::ACCOUNT, 8], $accounts);
    }

    public function test_account_context_isolates_default_scope(): void
    {
        $this->ingestTree(self::ACCOUNT);
        $this->ingestTree(8);

        AccountContext::for(self::ACCOUNT, static function (): void {
            self::assertSame(
                1,
                TlMessageMessage::query()->where('peer_id', PeerIdTool::channelLong(self::CHANNEL_ID))->count(),
                'default scope must hide account 8\'s row',
            );
            self::assertSame(
                1,
                TlMessageMessage::forAccount(8)->where('peer_id', PeerIdTool::channelLong(self::CHANNEL_ID))->count(),
                'forAccount(8) must expose exactly account 8\'s row',
            );
        });
    }

    public function test_account_context_for_code(): void
    {
        $this->ingestTree(self::ACCOUNT);
        $this->ingestTree(8);

        AccountContext::for(self::ACCOUNT, static function (): void {
            $message = TlMessageMessage::query()
                ->where('peer_id', PeerIdTool::channelLong(self::CHANNEL_ID))
                ->sole();

            self::assertSame(self::ACCOUNT, (int) $message->account_id);

            // Resolve the channel peer via the classMap seam (instance holds
            // the canonical tl_id; the default map targets anchor models).
            $peer = $message->resolvePeerModel('peer_id', ['channel' => TlChatChannel::class]);

            self::assertInstanceOf(TlChatChannel::class, $peer);
            self::assertSame(self::CHANNEL_ID, (int) $peer->tl_id);
        });
    }
}
