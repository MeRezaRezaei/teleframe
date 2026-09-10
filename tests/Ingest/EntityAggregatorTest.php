<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUserEmpty;

/**
 * Plan Task 5: entity aggregation — resolve the anchor for a referenced
 * entity (user/chat/channel) by (tenant, telegram id) with its CURRENT
 * instance loaded (latest constructor, non-deleted); null when absent.
 */
final class EntityAggregatorTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const OTHER_ACCOUNT = 8;

    private const USER_ID = 501558149;

    private const CHANNEL_ID = 1737473577;

    /**
     * @return array<string, mixed>
     */
    private static function userPayload(string $firstName = 'Reza'): array
    {
        return [
            '_' => 'user',
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3),
            'id' => self::USER_ID,
            'access_hash' => -5988024083302710253,
            'first_name' => $firstName,
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function channelPayload(): array
    {
        return [
            '_' => 'channel',
            // verified | megagroup | access_hash
            'flags' => (1 << 7) | (1 << 8) | (1 << 13),
            'verified' => true,
            'megagroup' => true,
            'id' => self::CHANNEL_ID,
            'access_hash' => -7779317524312221622,
            'title' => 'Teleframe Café',
            'photo' => ['_' => 'chatPhotoEmpty'],
            'date' => 1712345678,
        ];
    }

    private function ingestUser(int $accountId = self::ACCOUNT, string $firstName = 'Reza'): TlUserUser
    {
        $root = (new UpdateIngestor())->ingest(self::userPayload($firstName), $accountId);
        assert($root instanceof TlUserUser);

        return $root;
    }

    public function test_finds_user_anchor_with_current_instance(): void
    {
        $written = $this->ingestUser();

        $anchor = (new EntityAggregator())->user(self::ACCOUNT, self::USER_ID);

        self::assertInstanceOf(TlUser::class, $anchor);
        self::assertSame((int) $written->id, (int) $anchor->id, 'resolves the anchor the ingestor wrote');
        self::assertSame(self::ACCOUNT, (int) $anchor->account_id);
        self::assertSame(0x31774388, $anchor->constructor_id);

        // CURRENT instance is loaded through the instance relation.
        $instance = $anchor->currentInstance;
        self::assertInstanceOf(TlUserUser::class, $instance);
        self::assertSame(self::USER_ID, $instance->tl_id);
        self::assertSame('Reza', $instance->first_name);
        self::assertSame('RezaRezaei', $instance->username);
    }

    public function test_unknown_user_is_null(): void
    {
        $this->ingestUser();

        self::assertNull((new EntityAggregator())->user(self::ACCOUNT, 999999));
    }

    public function test_user_lookup_is_tenant_scoped(): void
    {
        $this->ingestUser();

        self::assertNull((new EntityAggregator())->user(self::OTHER_ACCOUNT, self::USER_ID), 'account 8 has no such user');

        $this->ingestUser(self::OTHER_ACCOUNT, 'Other Reza');

        $a = (new EntityAggregator())->user(self::ACCOUNT, self::USER_ID);
        $b = (new EntityAggregator())->user(self::OTHER_ACCOUNT, self::USER_ID);
        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertNotSame((int) $a->id, (int) $b->id, 'one anchor per tenant');
        self::assertSame('Reza', $a->currentInstance->first_name);
        self::assertSame('Other Reza', $b->currentInstance->first_name);
    }

    public function test_current_instance_follows_latest_constructor(): void
    {
        $this->ingestUser(); // user#31774388 under account 7

        // userEmpty#d3bc4b7a for the SAME telegram id arrives later (account
        // deleted upstream) — same anchor, new constructor instance row.
        (new UpdateIngestor())->ingest([
            '_' => 'userEmpty',
            'id' => self::USER_ID,
        ], self::ACCOUNT);

        $anchor = (new EntityAggregator())->user(self::ACCOUNT, self::USER_ID);

        self::assertInstanceOf(TlUser::class, $anchor);
        self::assertInstanceOf(TlUserUserEmpty::class, $anchor->currentInstance, 'latest constructor wins');
        self::assertSame($anchor->constructor_name, 'userEmpty', 'anchor discriminator follows the current constructor');
    }

    public function test_deleted_users_do_not_resolve(): void
    {
        (new UpdateIngestor())->ingest([
            '_' => 'user',
            'flags' => (1 << 5), // deleted (flags.5)
            'deleted' => true,
            'id' => self::USER_ID,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
        ], self::ACCOUNT);

        self::assertNull((new EntityAggregator())->user(self::ACCOUNT, self::USER_ID), 'latest instance is deleted → null');
    }

    public function test_resolves_chat_and_channel_anchors(): void
    {
        (new UpdateIngestor())->ingest(self::channelPayload(), self::ACCOUNT);

        $aggregator = new EntityAggregator();
        $chat = $aggregator->chat(self::ACCOUNT, self::CHANNEL_ID);
        $channel = $aggregator->channel(self::ACCOUNT, self::CHANNEL_ID);

        // channels ARE Chat-namespace anchors (TL truth) — channel() is the
        // intent-revealing alias of chat().
        self::assertInstanceOf(TlChat::class, $chat);
        self::assertInstanceOf(TlChat::class, $channel);
        self::assertSame((int) $chat->id, (int) $channel->id);
        self::assertInstanceOf(TlChatChannel::class, $chat->currentInstance);
        self::assertSame('Teleframe Café', $chat->currentInstance->title);
        self::assertTrue((bool) $chat->currentInstance->verified);

        self::assertNull($aggregator->chat(self::ACCOUNT, 42), 'unknown chat id');
        self::assertNull($aggregator->chat(self::OTHER_ACCOUNT, self::CHANNEL_ID), 'tenant scoped');
    }
}
