<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Backfill\MemoryCursorStore;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;

/**
 * `teleframe:backfill` really does route an RPC result into the mirror.
 *
 * `MirrorIngesterFactory::buildResultRoute()` was shipped proven and
 * unreachable: the mirror's only ingest surface was fed by the Redis bus, so
 * it saw updates and never an RPC result, and every constructor Telegram
 * delivers solely as a result (`botInfo` behind `userFull.bot_info`, flag 3)
 * had DDL, a model, a write path and no data. The route's own test could not
 * close that, because it drives the route DIRECTLY — a test of the factory is
 * not a test of a caller, and "the seam works" is not "the seam is reached".
 *
 * So this suite drives the CALLER: the real artisan command, over a real
 * account scope, through the policy-gated route, into a database whose schema
 * was produced by EXECUTING the shipped migration
 * (`ShippedStoreSchema::capsuleFor()`). Hand-typing the DDL would make this a
 * test of the author's imagination — the disease this project has paid for
 * repeatedly, and which `ShippedStoreSchema` exists to make structurally
 * impossible. Assertions are about ROWS THAT EXIST, never about SQL.
 *
 * IT BITES, in both directions, in this one file:
 *
 *  - `users.getFullUser` NOT admitted by the policy → the command still dials
 *    it (asserted on the recorded call) and the row is ABSENT. So the row in
 *    the next test cannot be an artefact of the test's own wiring.
 *  - the shipped default policy → the row is PRESENT, keyed on the shipped
 *    `(account_id, id)` primary key with the bot's own user id.
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php
 * (tf_bot_infos, primary(['account_id','id'])); the caller is
 * src/Laravel/Console/BackfillCommand.php::mirrorAccountProfile(); the policy
 * is src/Teleframe/Ingest/MirrorResultPolicy.php.
 */
final class MirrorResultSyncPlacesRowTest extends IngestTestCase
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
        return [7];
    }

    /** The shipped bots DDL — the only migration this suite's capsule runs. */
    private const BOTS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php';

    /**
     * The fold that drops `tf_bot_infos_description` and adds `tf_bot_infos.description`.
     *
     * 🔴 THIS SUITE MUST EXECUTE IT, and not 200041 alone. With the bots DDL alone
     * this capsule still carried `tf_bot_infos_description` and no
     * `tf_bot_infos.description` column — a table the shipped dial DROPS. Reading
     * `value('description')` off a parent with no such column returns the LITERAL
     * STRING `'description'` on SQLite rather than raising (MEASURED), so an
     * assertion re-pointed at the parent without this migration is one that can
     * never fail. Running the fold keeps the schema the shipped end-state and keeps
     * the assertion load-bearing.
     */
    private const FOLD = __DIR__.'/../../src/Laravel/Migrations/2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php';

    private const ACCOUNT = 7;

    private const BOT = 424242;

    private Capsule $capsule;

    /** @var list<array{ingested: list<array<string, mixed>>}> */
    private array $ingested = [];

    /**
     * A REALISTIC `users.getFullUser` answer for a bot, as the transport
     * decodes it. `bot_info` is flags.3-gated, so it is present only on a
     * bot's full user — which is exactly why no update ever carried it. The
     * absent optional fields are omitted, as a real decode omits them.
     *
     * @var array<string, mixed>
     */
    private const GET_FULL_USER_RESULT = [
        '_' => 'userFull',
        'id' => self::BOT,
        'about' => 'a bot that does things',
        'bot_info' => [
            '_' => 'botInfo',
            'user_id' => self::BOT,
            'description' => 'does things, loudly',
            'commands' => [
                ['_' => 'botCommand', 'command' => 'start', 'description' => 'begin'],
            ],
            'privacy_policy_url' => 'https://example.test/privacy',
            'has_preview_medias' => false,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Execute the SHIPPED migration. capsuleFor() clobbers the global
        // container and the facade application, so the real app is put back
        // and the capsule's connection manager is installed as the app's
        // `db` — which is what makes the command's `DB::connection()` resolve
        // to a database whose schema came from the migration file rather than
        // from this file.
        $this->capsule = ShippedStoreSchema::capsuleFor(self::BOTS_MIGRATION, self::FOLD);
        ShippedStoreSchema::restoreFacadeApplication($this->app);
        $this->app->instance('db', $this->capsule->getDatabaseManager());
        $this->app['config']->set('database.default', 'default');
        Facade::clearResolvedInstances();
    }

    /**
     * The route is REACHED, and reaches the mirror, from the real command.
     */
    public function test_a_bot_info_row_places_from_a_result_the_command_itself_dialed(): void
    {
        $scope = $this->bind(new ProfileMirrorScope(self::GET_FULL_USER_RESULT));

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--peer' => 'chat'])
            ->assertExitCode(0);

        $db = $this->db();
        self::assertSame(1, $db->table('tf_bot_infos')->count(), 'the bot-info row must place');
        self::assertContains('users.getFullUser', $scope->dialed, 'the caller dialled the method the result came from');
        self::assertSame(
            'users.getFullUser',
            $scope->dialed[0] ?? null,
            'the caller must dial users.getFullUser FIRST, before any crawl — otherwise the row above proves nothing',
        );
        self::assertSame(
            ['id' => ['_' => 'inputUserSelf']],
            $scope->getFullUserParams[0] ?? null,
            'the account is asked for itself, so no peer id is needed',
        );
        self::assertSame(1, $db->table('tf_bot_infos')->where('account_id', self::ACCOUNT)->count(), 'scoped to the account the result was fetched FOR');
        self::assertSame(
            self::BOT,
            (int) $db->table('tf_bot_infos')->where('account_id', self::ACCOUNT)->value('id'),
            'keyed on the bot user id the payload carried',
        );
        self::assertSame(
            false,
            (bool) $db->table('tf_bot_infos')->where('account_id', self::ACCOUNT)->value('has_preview_medias'),
            'the shipped base column carries the payload boolean',
        );
        self::assertSame(
            'does things, loudly',
            (string) $db->table('tf_bot_infos')->where('account_id', self::ACCOUNT)->value('description'),
            'the description fact reached its folded column on the parent row',
        );
        self::assertSame(
            'start',
            (string) $db->table('tf_bot_infos_commands')->where('account_id', self::ACCOUNT)->value('command'),
            'the 1:N child fact reached its shipped table',
        );
    }

    /**
     * THE BITE, inverted: with the method not admitted, the very same command
     * over the very same payload dials the method and writes NOTHING. An
     * always-on route would have placed this row — which is the whole reason
     * the policy exists, and the reason the positive test above means
     * something.
     */
    public function test_an_unadmitted_method_dials_but_places_no_row(): void
    {
        $this->app['config']->set('teleframe.mirror.result_methods', []);
        $scope = $this->bind(new ProfileMirrorScope(self::GET_FULL_USER_RESULT));

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--peer' => 'chat'])
            ->assertExitCode(0);

        self::assertContains('users.getFullUser', $scope->dialed, 'the call still happened — only the mirror was refused');
        self::assertSame(
            0,
            $this->db()->table('tf_bot_infos')->count(),
            'a method the operator did not admit must place no row, or the policy is decoration',
        );
    }

    /**
     * A method that IS admitted, carrying no declared fact, writes nothing.
     * Absence of a fact is absence of a row, never a placeholder.
     */
    public function test_an_admitted_method_carrying_no_declared_fact_places_no_row(): void
    {
        $this->bind(new ProfileMirrorScope(['_' => 'users.userFullEmpty', 'id' => 1]));

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--peer' => 'chat'])
            ->assertExitCode(0);

        self::assertSame(0, $this->db()->table('tf_bot_infos')->count());
    }

    /**
     * The cursor ledger and the batch ingester are container seams the command
     * resolves; the crawl side is not what this suite is about, so they are
     * bound to in-memory doubles and the history pages are empty. The profile
     * fetch under test is NOT a seam and runs for real.
     */
    private function bind(ProfileMirrorScope $scope): ProfileMirrorScope
    {
        $this->app->instance(BackfillCommand::SCOPE_RESOLVER_KEY, static fn (): UserAccountScope => $scope);
        $this->app->bind(
            BackfillCommand::INGESTER_KEY,
            function (): callable {
                return function (int $accountId): callable {
                    return function (array $batch): array {
                        $this->ingested[] = ['ingested' => $batch];

                        return ['ingested' => 0];
                    };
                };
            },
        );
        $this->app->bind(
            BackfillCommand::CURSOR_STORE_KEY,
            static fn (): callable => static fn (int $accountId): MemoryCursorStore => new MemoryCursorStore,
        );

        return $scope;
    }

    private function db(): Connection
    {
        return $this->capsule->getConnection();
    }
}

/**
 * A scope that answers exactly the two calls this command makes: the account's
 * own `users.getFullUser` (the caller under test) and an empty
 * `messages.getHistory` page (so the crawl contributes no rows and no schema
 * of its own is needed). Records what was dialled.
 */
final class ProfileMirrorScope extends FakeUserScope
{
    /** @var list<string> */
    public array $dialed = [];

    /** @var list<array<string, mixed>> */
    public array $getFullUserParams = [];

    /** @param array<string, mixed> $fullUser */
    public function __construct(private readonly array $fullUser)
    {
        parent::__construct();
    }

    public function call(string $method, array $params = []): array
    {
        $this->dialed[] = $method;

        return match ($method) {
            'users.getFullUser' => $this->fullUser($params),
            'messages.getHistory' => ['_' => 'messages.messagesSlice', 'count' => 0, 'messages' => []],
            default => parent::call($method, $params),
        };
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function fullUser(array $params): array
    {
        $this->getFullUserParams[] = $params;

        return $this->fullUser;
    }
}
