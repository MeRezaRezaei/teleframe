<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Backfill\MemoryCursorStore;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;

/**
 * THE SECOND DIALOGS CTOR. `api_full.tl:280` declares
 * `messages.dialogsSlice#71e094f3`, a peer of `messages.dialogs#15ba6c40`
 * carrying the SAME four vectors (`dialogs`, `messages`, `chats`, `users`) plus
 * a `count:int`. Telegram returns the slice form on every page after the
 * first, so a discovery run walks the majority of its pages under a
 * constructor most fixtures never name.
 *
 * WHY THAT IS A REAL RISK AND NOT A TYPO. `MirrorNestedSourceWalker::walk()`
 * looks each nested source up by its EXACT constructor string in
 * `mirror-catalog.json`, and the catalog declares BOTH dialogs ctors
 * (`tf_dialogs <- messages.dialogs.dialogs` AND
 * `tf_dialogs <- messages.dialogsSlice.dialogs`, at :412-417). Two spellings
 * that both have to resolve is exactly the shape where a qualifier, a filter or
 * an allow-list silently drops one: the page is then walked for zero facts and
 * stored as nothing, with no error — the defect class this suite exists for.
 * `BackfillCommand::qualifyDialogsCtor()` is the seam that touches both, so
 * both are asserted here.
 *
 * WHAT THIS IS NOT. It is not about a BARE `'dialogs'` spelling. There is no
 * bare dialogs constructor on the wire: `messages.getDialogs` answers under
 * `messages.dialogs` / `messages.dialogsSlice`, `DialogDiscovery` itself
 * compares against the qualified `messages.dialogsNotModified`, and
 * `MirrorStoresWhatTheWireCarriesTest::test_the_bare_dialogs_spelling_is_not_a_
 * registered_constructor` measures that `TLRegistry` does not register the bare
 * form. The bare entries in `DIALOGS_CTOR_BY_WIRE_TYPE` are therefore
 * defensive, not what arrives.
 *
 * WHY THE VALUE, NOT THE COUNT: a row written with `access_hash = 0` satisfies
 * any count assertion and still leaves every `--peer` refused, because
 * `usableHash()` reads 0 as "Telegram has not told us". This is the
 * "looks resolved but is not" shape the whole persister exists to remove, so
 * the assertions below name the hash the payload carried.
 */
final class BackfillDialogsSliceCtorTest extends IngestTestCase
{
    /**
     * The tenant this fixture writes under.
     *
     * MEASURED, not assumed: every row inserted here carries
     * `account_id = 7` (self::ACCOUNT).
     *
     * It was undeclared. With foreign-key enforcement on, every insert was
     * refused at the DATABASE rather than at the code under test — the test
     * failed on its own fixture and reported nothing about the behaviour it
     * exists to prove.
     *
     * Declared HERE rather than by relaxing seedIngestTenant(), because that
     * helper contracts that each test declares the tenants it means to use, so
     * a test writing under an unplanted id fails LOUDLY. These were failing
     * loudly, which is the mechanism working.
     */
    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    private const ACCOUNT = 7;

    /**
     * THE RISK, exercised. The SECOND qualified dialogs ctor must store exactly
     * what the first one stores — same payload, one different string.
     */
    public function test_a_dialogs_slice_page_persists_its_access_hashes(): void
    {
        $this->bind($this->slicePage());

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true])
            ->expectsOutputToContain(sprintf('discovered channel:%d', DiscoveryScope::CHANNEL_ID))
            ->assertExitCode(0);

        $db = $this->db();

        self::assertSame(
            DiscoveryScope::USER_HASH,
            (string) $db->table('tf_users_access_hash')->where('id', DiscoveryScope::USER_ID)->value('access_hash'),
            "a page the wire spells '_' => 'messages.dialogsSlice' must store the hash the payload carried; "
            .'a slice ctor that missed the catalog\'s nested-source lookup wrote nothing at all',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_users_access_hash')->count(),
            'exactly the one user of the page companion users vector',
        );
        self::assertSame(
            3,
            (int) $db->table('tf_dialogs')->count(),
            'and the page dialogs vector, which is the third declared edge',
        );
        self::assertSame(
            12424,
            (int) $db->table('tf_dialogs')->where('peer_id', DiscoveryScope::CHANNEL_ID)->value('top_message'),
            'row payloads are unchanged by which dialogs ctor the page arrived under: the dialog keeps the top_message it carried',
        );
    }

    /**
     * THE REGRESSION GUARD, in the other direction. Qualifying must be
     * IDEMPOTENT: the first dialogs ctor is still stored exactly once, not
     * rewritten into the slice spelling or doubled by a second walk.
     */
    public function test_the_first_dialogs_ctor_still_persists(): void
    {
        $this->bind((new DiscoveryScope)->page());

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true])
            ->assertExitCode(0);

        $db = $this->db();

        self::assertSame(
            DiscoveryScope::USER_HASH,
            (string) $db->table('tf_users_access_hash')->where('id', DiscoveryScope::USER_ID)->value('access_hash'),
            'the messages.dialogs spelling is the one the existing suite uses; it must keep working',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_users_access_hash')->count(),
            'qualifying an already-qualified ctor must not write the row twice',
        );
    }

    /**
     * WHY THE GUARD IS STILL THERE. Qualifying the ctor is not a licence to
     * route everything: `messages.dialogsNotModified` carries no dialogs /
     * users / chats at all (it is the walk's terminal page), so it declares no
     * facts and a route call on it provably writes nothing.
     */
    public function test_dialogs_not_modified_still_stores_nothing(): void
    {
        $this->bind([
            '_' => 'messages.dialogsNotModified',
            'dialogs' => [],
            'messages' => [],
            'chats' => [],
            'users' => [],
        ]);

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true])
            ->assertExitCode(0);

        $db = $this->db();

        self::assertSame(0, (int) $db->table('tf_dialogs')->count(), 'the terminal page declares no facts');
        self::assertSame(0, (int) $db->table('tf_users_access_hash')->count(), 'and carries no companion vector');
    }

    /**
     * `DiscoveryScope`'s page with ONLY the `_` rewritten to the slice ctor.
     * Array union, not array merge: the left operand's `_` wins and the
     * payload keeps every key `DiscoveryScope` put there, so the two fixtures
     * cannot drift apart in anything but the one string under test.
     *
     * @return array<string, mixed>
     */
    private function slicePage(): array
    {
        return ['_' => 'messages.dialogsSlice'] + (new DiscoveryScope)->page();
    }

    /**
     * @param  array<string, mixed>  $page  the one `messages.getDialogs` answer
     */
    private function bind(array $page): FakeUserScope
    {
        $scope = new class($page) extends FakeUserScope
        {
            /** @param array<string, mixed> $page */
            public function __construct(private readonly array $page)
            {
                parent::__construct();
            }

            /** @param array<string, mixed> $params */
            public function call(string $method, array $params = []): array
            {
                $this->calls[] = ['method' => $method, 'params' => $params];

                if ($method === 'messages.getDialogs') {
                    return $this->page;
                }

                return match ($method) {
                    'messages.getHistory' => ['_' => 'messages.messagesSlice', 'count' => 0, 'messages' => []],
                    default => parent::call($method, $params),
                };
            }
        };

        $this->app->instance(BackfillCommand::SCOPE_RESOLVER_KEY, static fn (): UserAccountScope => $scope);
        $this->app->bind(
            BackfillCommand::INGESTER_KEY,
            static fn (): callable => static fn (int $accountId): callable => static fn (array $batch): array => ['ingested' => 0],
        );
        $this->app->bind(
            BackfillCommand::CURSOR_STORE_KEY,
            static fn (): callable => static fn (int $accountId): MemoryCursorStore => new MemoryCursorStore,
        );

        return $scope;
    }

    private function db(): Connection
    {
        return $this->app->make('db')->connection();
    }
}
