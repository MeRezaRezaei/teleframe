<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\Handler;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Laravel\Models\TfAccount;
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;

/**
 * Cycle-5 item 2 — bots + "anything codes work with" hot-load through the
 * observe surface: kind-scoped observers filter frames against the
 * tf_accounts registry kind (bot/account), degrade conservatively on a
 * DB-less container, and generic/tagged handlers hot-register through the
 * shared HandlerRegistry table without any DB dependency.
 */
final class ObserveBotKindTest extends IngestTestCase
{
    /**
     * The tenants this fixture writes under.
     *
     * MEASURED, not assumed: extracted from the account ids this file
     * actually inserts. It was undeclared, and with foreign-key enforcement
     * on (19 constraints) every row was refused at the DATABASE rather than
     * at the code under test — the test failed on its own fixture.
     *
     * Declared HERE rather than by relaxing seedIngestTenant(): that helper
     * contracts that each test declares the tenants it means to use, so a
     * test writing under an unplanted id fails LOUDLY. These were failing
     * loudly, which is the mechanism working. A blanket auto-seed would also
     * make the orphan-refusal tests unable to plant an orphan.
     */
    protected function ingestTenantIds(): array
    {
        return [99];
    }

    private const BOT_ACCOUNT = 11;

    private const ACCOUNT_ACCOUNT = 7;

    private Teleframe $teleframe;

    protected function setUp(): void
    {
        parent::setUp();
        HandlerRegistry::reset();
        $this->teleframe = $this->app->make(Teleframe::class);
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();
        parent::tearDown();
    }

    /** Seed the tf_accounts registry with one bot and one user account. */
    private function seedRegistry(): void
    {
        TfAccount::create([
            'id' => self::BOT_ACCOUNT,
            'label' => 'company-bot',
            'dc_id' => 2,
            'kind' => TfAccount::KIND_BOT,
            'session_ref' => 'vault:bot:1',
        ]);
        TfAccount::create([
            'id' => self::ACCOUNT_ACCOUNT,
            'label' => 'company-account',
            'dc_id' => 2,
            'kind' => TfAccount::KIND_ACCOUNT,
            'session_ref' => 'vault:account:1',
        ]);
    }

    public function test_observe_account_scoped_with_bot_kind_composes_without_regression(): void
    {
        $this->seedRegistry();

        $seen = [];
        $this->teleframe->observe(self::BOT_ACCOUNT, function (Update $u) use (&$seen): void {
            $seen[] = $u->accountId;
        }, 0, Teleframe::KIND_BOT);

        $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::BOT_ACCOUNT, 'ts' => 300],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::ACCOUNT_ACCOUNT, 'ts' => 301],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::BOT_ACCOUNT, 'ts' => 302],
        ]);

        self::assertSame([self::BOT_ACCOUNT, self::BOT_ACCOUNT], $seen, 'bot-kind frames under the bot scope fire; foreign-account frames stay skipped');
    }

    public function test_observe_wildcard_with_bot_kind_filters_frames_against_registry_kind(): void
    {
        $this->seedRegistry();

        $seen = [];
        $this->teleframe->observe('*', function (Update $u) use (&$seen): void {
            $seen[] = $u->accountId;
        }, 0, Teleframe::KIND_BOT);

        $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::BOT_ACCOUNT, 'ts' => 400],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::ACCOUNT_ACCOUNT, 'ts' => 401],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 99, 'ts' => 402],
        ]);

        self::assertSame([self::BOT_ACCOUNT], $seen, 'only the bot-kind account fires; the user-kind and unregistered frames are filtered out');
    }

    public function test_observe_wildcard_with_account_kind_is_the_complement(): void
    {
        $this->seedRegistry();

        $seen = [];
        $this->teleframe->observe('*', function (Update $u) use (&$seen): void {
            $seen[] = $u->accountId;
        }, 0, Teleframe::KIND_ACCOUNT);

        $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::BOT_ACCOUNT, 'ts' => 500],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::ACCOUNT_ACCOUNT, 'ts' => 501],
        ]);

        self::assertSame([self::ACCOUNT_ACCOUNT], $seen, 'the account-kind observer fires only for the user-kind account');
    }

    public function test_kind_scoped_observe_on_a_db_less_container_accepts_all_without_crashing(): void
    {
        HandlerRegistry::reset();
        $container = new ArrayContainer([HandlerRegistry::class => HandlerRegistry::instance()]);
        $teleframe = new Teleframe($container);

        $registered = $teleframe->observe(5, static fn (Update $u): null => null, 0, Teleframe::KIND_BOT);

        self::assertSame($teleframe, $registered);
        self::assertSame(1, HandlerRegistry::instance()->count(), 'the kind-scoped observer still hot-registers through the shared table');
    }

    public function test_generic_or_garbage_tagged_handlers_hot_register_without_a_database(): void
    {
        HandlerRegistry::reset();
        $registry = HandlerRegistry::instance();

        $registry->onMessage(static fn (Update $u): null => null, 0, 'garbage-kind:bot');
        $registry->on('*', static fn (Update $u): null => null, 0, false, 'hot:generic-worker');
        $registry->on('updateNewMessage', static fn (Update $u): null => null, 0, false, 'tagged:unregistered');

        self::assertSame(3, $registry->count());

        $tags = array_map(static fn (Handler $h) => $h->tag, $registry->all());
        self::assertSame(['garbage-kind:bot', 'hot:generic-worker', 'tagged:unregistered'], $tags);

        $untagged = new Handler('*', 'App\\Thing');
        $tagged = new Handler('*', 'App\\Thing', 0, false, 'bot');
        self::assertNull($untagged->tag, 'no tag → current behavior');
        self::assertSame('bot', $tagged->tag);
        self::assertNotSame($untagged->id(), $tagged->id(), 'the tag is part of the handler identity');
        self::assertSame(16, strlen($tagged->id()));
    }
}
