<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageService;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageViews;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * Messages-domain mirror surface (Task 3): the three curated migrations must
 * migrate clean, accept the decomposed write-path row shapes, come back
 * through the Eloquent models, and stay account-scoped.
 */
final class MessageMirrorTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'ingest');
        $app['config']->set('database.connections.ingest', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        AccountContext::reset();
        $this->migrateMirror();
    }

    public function test_content_message_row_is_retrievable_with_fact_children(): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => 42,
            'id' => 55,
            'peer_type' => 2,
            'peer_id' => 900,
            'constructor' => 'message',
            'date' => 1_700_000_000,
            'message' => 'hello',
            'out' => 1,
            'pinned' => 1,
        ]);
        DB::table('tf_messages_views')->insert(['account_id' => 42, 'id' => 55, 'views' => 10]);
        DB::table('tf_messages_entities')->insert([
            ['account_id' => 42, 'id' => 55, 'position' => 0, 'constructor' => 'messageEntityBold', 'offset' => 0, 'length' => 4],
            ['account_id' => 42, 'id' => 55, 'position' => 1, 'constructor' => 'messageEntityItalic', 'offset' => 4, 'length' => 2],
        ]);

        $message = AccountContext::for(42, fn () => TfMessage::query()->find(55));

        self::assertNotNull($message);
        self::assertSame('message', $message->constructor);
        self::assertSame(2, $message->peer_type);
        self::assertSame(900, $message->peer_id);
        self::assertTrue($message->out);
        self::assertTrue($message->pinned);
        self::assertSame(10, $message->views->views);
        self::assertCount(2, $message->entities);
        self::assertSame([0, 1], $message->entities->pluck('position')->all());
        self::assertSame('messageEntityItalic', $message->entities[1]->constructor);

        AccountContext::for(42, static function (): void {
            $viewsRow = TfMessageViews::query()->find(55);
            self::assertNotNull($viewsRow);
            self::assertSame(10, $viewsRow->views);
        });
    }

    public function test_empty_message_places_with_type_defaults(): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => 42,
            'id' => 56,
            'constructor' => 'messageEmpty',
        ]);

        $message = AccountContext::for(42, fn () => TfMessage::query()->find(56));

        self::assertNotNull($message);
        self::assertSame('messageEmpty', $message->constructor);
        self::assertSame(0, $message->date);
        self::assertSame(0, $message->peer_type);
        self::assertSame('', $message->message);
    }

    public function test_service_message_and_action_are_retrievable(): void
    {
        DB::table('tf_messages_service')->insert([
            'account_id' => 42,
            'id' => 77,
            'peer_type' => 2,
            'peer_id' => 900,
            'constructor' => 'messageService',
            'date' => 1_700_000_001,
            'post' => 1,
        ]);
        DB::table('tf_messages_service_action')->insert([
            'account_id' => 42,
            'id' => 77,
            'constructor' => 'messageActionChatCreate',
            'title' => 'group',
        ]);

        $service = AccountContext::for(42, fn () => TfMessageService::query()->find(77));

        self::assertNotNull($service);
        self::assertSame('messageService', $service->constructor);
        self::assertTrue($service->post);
        self::assertNotNull($service->action);
        self::assertSame('messageActionChatCreate', $service->action->constructor);
        self::assertSame('group', $service->action->title);
    }

    public function test_media_union_child_places_partial_rows(): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => 42, 'id' => 58, 'peer_type' => 3, 'peer_id' => -100, 'constructor' => 'message', 'date' => 1, 'message' => 'x',
        ]);
        DB::table('tf_messages_media')->insert([
            'account_id' => 42,
            'id' => 58,
            'constructor' => 'messageMediaContact',
            'phone_number' => '+1800',
        ]);

        $message = AccountContext::for(42, fn () => TfMessage::query()->find(58));

        self::assertNotNull($message->media);
        self::assertSame('messageMediaContact', $message->media->constructor);
        self::assertSame('+1800', $message->media->phone_number);
    }

    public function test_account_isolation_via_fact_scope(): void
    {
        DB::table('tf_messages')->insert([
            ['account_id' => 1, 'id' => 55, 'peer_type' => 2, 'peer_id' => 900, 'constructor' => 'message', 'date' => 1, 'message' => 'a'],
            ['account_id' => 2, 'id' => 55, 'peer_type' => 2, 'peer_id' => 900, 'constructor' => 'message', 'date' => 1, 'message' => 'b'],
        ]);

        self::assertSame(1, AccountContext::for(1, fn () => TfMessage::query()->count()));
        self::assertSame('a', AccountContext::for(1, fn () => TfMessage::query()->first()->message));
        self::assertSame('b', AccountContext::for(2, fn () => TfMessage::query()->first()->message));
        self::assertSame(2, TfMessage::acrossAccounts()->count());
        self::assertSame(1, TfMessage::forAccount(2)->count());
    }

    private function migrateMirror(): void
    {
        // Direct up() instead of artisan migrate: the provider still registers
        // the purged TeleframeMirrorCommand, so the console kernel cannot boot
        // in this worktree (expected-red until the controller re-wires it).
        foreach ([
            __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php',
            __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200011_create_tf_message_entities_tables.php',
            __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200012_create_tf_message_medias_tables.php',
        ] as $path) {
            $migration = require $path;
            $migration->up();
        }
    }
}
