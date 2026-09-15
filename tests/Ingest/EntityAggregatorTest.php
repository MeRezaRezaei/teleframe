<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChat;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUser;

/**
 * Phase C re-baseline: aggregation on the CURATED identity dial. The
 * aggregator resolves the hand-authored domain table for a referenced
 * entity by (tenant, telegram id) — tf_users / tf_chats / tf_channels —
 * the same tables the identity mirrors seed. Generated Tl* models,
 * constructor discriminator upserts and tl_data rows are gone.
 *
 * Identity rows come from the identity mirror seams (tests/Mirror), NOT
 * from ingest: the user/channel difference-stream ctors have no curated
 * write surface, so these tests seed the rows directly exactly the way
 * IdentityMirrorTest does — children then live in the 1:1 fact tables
 * (first_name is tf_users_first_name, not a tf_users column).
 */
final class EntityAggregatorTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const OTHER_ACCOUNT = 8;

    private const USER_ID = 501558149;

    private const CHANNEL_ID = 1737473577;

    private const CHAT_ID = 220000013;

    private function seedUser(int $accountId = self::ACCOUNT, string $firstName = 'Reza', bool $deleted = false): void
    {
        $user = new TfUser([
            'account_id' => $accountId,
            'id' => self::USER_ID,
            'constructor' => 'user',
            'deleted' => $deleted,
            'contact' => true,
        ]);
        $user->save();

        $user->firstName()->create([...$user->childKey(), 'first_name' => $firstName]);
        $user->username()->create([...$user->childKey(), 'username' => 'RezaRezaei']);
    }

    public function test_finds_user_anchor_resolves_the_domain_row(): void
    {
        $this->seedUser();

        $anchor = (new EntityAggregator)->user(self::ACCOUNT, self::USER_ID);

        self::assertInstanceOf(TfUser::class, $anchor);
        self::assertSame(self::USER_ID, (int) $anchor->getAttribute('id'), 'native Telegram id is half the PK');
        self::assertSame(self::ACCOUNT, (int) $anchor->getAttribute('account_id'));
        self::assertSame('user', $anchor->getAttribute('constructor'), 'ctor name, no crc32 column');

        // The curated dial keeps identity facts as 1:1 CHILD rows — the
        // aggregator resolves the parent; the child read is a relation.
        self::assertSame('Reza', $anchor->firstName()->sole()->getAttribute('first_name'));
        self::assertSame('RezaRezaei', $anchor->username()->sole()->getAttribute('username'));
    }

    public function test_unknown_user_is_null(): void
    {
        $this->seedUser();

        self::assertNull((new EntityAggregator)->user(self::ACCOUNT, 999999));
    }

    public function test_user_rows_are_tenant_scoped(): void
    {
        $this->seedUser(self::ACCOUNT, 'Reza');

        // Composite PK (account_id, id): each tenant holds its own row for
        // the same Telegram id — account 8 sees nothing until it is seeded.
        $a = (new EntityAggregator)->user(self::ACCOUNT, self::USER_ID);
        self::assertNotNull($a);
        self::assertNull((new EntityAggregator)->user(self::OTHER_ACCOUNT, self::USER_ID));

        $this->seedUser(self::OTHER_ACCOUNT, 'Ali');
        $resolved = (new EntityAggregator)->user(self::OTHER_ACCOUNT, self::USER_ID);
        self::assertNotNull($resolved);
        self::assertSame(self::USER_ID, (int) $resolved->getAttribute('id'), 'same native id');
        self::assertSame(self::OTHER_ACCOUNT, (int) $resolved->getAttribute('account_id'), '... but a distinct tenant row');
        // Child facts keyed (account_id, id): scope the relation read to the
        // tenant explicitly — the ambient account scope is unset in tests.
        self::assertSame('Ali', $resolved->firstName()->forAccount(self::OTHER_ACCOUNT)->sole()->getAttribute('first_name'));
        self::assertSame(2, TfUser::acrossAccounts()->count());
    }

    public function test_deleted_users_do_not_resolve(): void
    {
        $this->seedUser(self::ACCOUNT, 'Reza');

        // Upstream delete: latest identity row flips deleted=true — the
        // row persists but behaves as absent.
        TfUser::forAccount(self::ACCOUNT)->where('id', self::USER_ID)->update(['deleted' => true]);

        self::assertNull((new EntityAggregator)->user(self::ACCOUNT, self::USER_ID), 'deleted row resolves to null');
    }

    public function test_resolves_channel_and_chat_anchors(): void
    {
        $channel = new TfChannel([
            'account_id' => self::ACCOUNT,
            'id' => self::CHANNEL_ID,
            'constructor' => 'channel',
            'title' => 'Teleframe Café',
            'verified' => true,
            'megagroup' => true,
        ]);
        $channel->save();

        $chat = new TfChat([
            'account_id' => self::ACCOUNT,
            'id' => self::CHAT_ID,
            'constructor' => 'chat',
            'title' => 'Park Bench',
        ]);
        $chat->save();

        $aggregator = new EntityAggregator;

        $found = $aggregator->channel(self::ACCOUNT, self::CHANNEL_ID);
        self::assertInstanceOf(TfChannel::class, $found);
        self::assertSame('Teleframe Café', $found->getAttribute('title'));
        self::assertTrue((bool) $found->getAttribute('verified'));
        self::assertTrue((bool) $found->getAttribute('megagroup'));

        self::assertNull($aggregator->chat(self::ACCOUNT, self::CHANNEL_ID), 'channel rows do not live in tf_chats');
        self::assertInstanceOf(TfChat::class, $aggregator->chat(self::ACCOUNT, self::CHAT_ID), 'chats resolve through chat()');
        self::assertNull($aggregator->channel(self::ACCOUNT, 42), 'unknown channel id');
        self::assertNull($aggregator->channel(self::OTHER_ACCOUNT, self::CHANNEL_ID), 'tenant-scoped: other account sees nothing yet');
    }
}
