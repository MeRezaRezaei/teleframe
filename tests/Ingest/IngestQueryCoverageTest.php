<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Ingest\MtprotoChunkFetcher;
use MeRezaRezaei\Teleframe\Ingest\Queries\DialogQuery;
use MeRezaRezaei\Teleframe\Ingest\Queries\UserQuery;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;

/**
 * W-21: MtprotoChunkFetcher + Queries/UserQuery + Queries/DialogQuery had
 * ZERO direct tests.
 *
 *  - MtprotoChunkFetcher: the wire adapter is exercised against a Client
 *    double (constructor-injected, no live auth) — getState envelope +
 *    getDifference param precedence (prior-state overrides live state).
 *  - UserQuery / DialogQuery: typed builders run against a real sqlite
 *    connection (capsule) with the mirror tables.
 */
final class IngestQueryCoverageTest extends TestCase
{
    private const MIGRATION_USERS = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200001_create_tf_users_tables.php';

    private const MIGRATION_DIALOGS = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php';

    /** Client double that records the last call and returns canned payloads. */
    private function fakeClient(callable $handler): Client
    {
        return new class($handler) extends Client
        {
            public function __construct(private readonly mixed $handler)
            {
                parent::__construct(live: false);
            }

            public function call(string $method, array $params = []): array
            {
                return ($this->handler)($method, $params);
            }
        };
    }

    public function test_get_state_envelopes_pts_date_qts_seq(): void
    {
        $seen = null;
        $client = $this->fakeClient(function (string $method, array $params) use (&$seen): array {
            $seen = [$method, $params];

            return ['_' => 'updates.state', 'pts' => 100, 'date' => 200, 'qts' => 3, 'seq' => 0, 'unread_count' => 4];
        });

        $fetcher = new MtprotoChunkFetcher($client);
        $state = $fetcher->getState();

        self::assertSame(['updates.getState', []], $seen);
        self::assertSame(['pts' => 100, 'date' => 200, 'qts' => 3, 'seq' => 0], $state);
    }

    public function test_get_state_defaults_missing_keys_to_zero(): void
    {
        $client = $this->fakeClient(fn (string $m, array $p): array => ['_' => 'updates.state']);

        $state = (new MtprotoChunkFetcher($client))->getState();

        self::assertSame(['pts' => 0, 'date' => 0, 'qts' => 0, 'seq' => 0], $state);
    }

    public function test_get_difference_uses_prior_state_over_live_state(): void
    {
        $seen = null;
        $client = $this->fakeClient(function (string $method, array $params) use (&$seen): array {
            $seen = $params;

            return ['_' => 'updates.difference'];
        });

        $fetcher = new MtprotoChunkFetcher($client);
        $result = $fetcher->getDifference(
            ['pts' => 100, 'date' => 200, 'qts' => 3],
            ['pts' => 999, 'date' => 888, 'qts' => 7],
        );

        self::assertSame(['pts' => 999, 'date' => 888, 'qts' => 7], $seen);
        self::assertSame(['_' => 'updates.difference'], $result);
    }

    public function test_get_difference_falls_back_to_live_state_without_prior(): void
    {
        $seen = null;
        $client = $this->fakeClient(function (string $method, array $params) use (&$seen): array {
            $seen = $params;

            return ['_' => 'updates.difference'];
        });

        (new MtprotoChunkFetcher($client))->getDifference(['pts' => 100, 'date' => 200, 'qts' => 3]);

        self::assertSame(['pts' => 100, 'date' => 200, 'qts' => 3], $seen);
    }

    // ---- UserQuery (sqlite: elapsed on real tables) ----

    private function usersModel(): Model
    {
        return new class extends Model
        {
            protected $table = 'tf_users';

            protected $primaryKey = 'id';
        };
    }

    private function dialogsModel(): Model
    {
        return new class extends Model
        {
            protected $table = 'tf_dialogs';

            protected $primaryKey = 'peer_id';
        };
    }

    private function withMirrorTables(callable $fn): void
    {
        // The schema is the SHIPPED migrations, executed — not transcribed
        // here. This harness used to re-type tf_users / tf_users_username /
        // tf_users_phone / tf_dialogs / tf_dialogs_folder_id by hand, and
        // that second copy drifted: it gave tf_dialogs a `top_message_id`
        // column that does not exist (the shipped column is `top_message`),
        // which is how DialogQuery::ordered() shipped a guaranteed SQL error
        // behind a green test. Provenance:
        // 2026_09_14_200001_create_tf_users_tables.php and
        // 2026_09_14_200004_create_tf_dialogs_tables.php.
        $previous = Container::getInstance();

        try {
            $fn(ShippedStoreSchema::capsuleFor(
                self::MIGRATION_USERS,
                self::MIGRATION_DIALOGS,
            ));
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    public function test_user_query_scopes_by_account_and_username(): void
    {
        $this->withMirrorTables(function (Capsule $capsule): void {
            $capsule->table('tf_users')->insert([
                ['account_id' => 1, 'id' => 11, 'constructor' => 'user', 'bot' => false, 'contact' => false, 'self' => false],
                ['account_id' => 2, 'id' => 22, 'constructor' => 'user', 'bot' => false, 'contact' => false, 'self' => false],
                ['account_id' => 1, 'id' => 33, 'constructor' => 'user', 'bot' => true, 'contact' => false, 'self' => false],
            ]);

            // username is a 1:1 fact on a CHILD table, so the case-sensitivity
            // is now tested where the data actually lives.
            $capsule->table('tf_users_username')->insert([
                ['account_id' => 1, 'id' => 11, 'username' => 'Alice'],
                ['account_id' => 2, 'id' => 22, 'username' => 'alice'],
                ['account_id' => 1, 'id' => 33, 'username' => 'botman'],
            ]);

            $query = (new UserQuery($capsule->table('tf_users')))->setModel($this->usersModel());
            $rows = $query->forAccount(1)->byUsername('ALICE')->get()->all();

            self::assertCount(1, $rows);
            self::assertSame(11, (int) $rows[0]->id);
        });
    }

    public function test_user_query_bot_and_human_filters(): void
    {
        $this->withMirrorTables(function (Capsule $capsule): void {
            $capsule->table('tf_users')->insert([
                ['account_id' => 1, 'id' => 1, 'constructor' => 'user', 'bot' => true, 'contact' => false, 'self' => false],
                ['account_id' => 1, 'id' => 2, 'constructor' => 'user', 'bot' => false, 'contact' => false, 'self' => false],
            ]);

            self::assertCount(1, (new UserQuery($capsule->table('tf_users')))->setModel($this->usersModel())->bots()->get());
            self::assertCount(1, (new UserQuery($capsule->table('tf_users')))->setModel($this->usersModel())->humans()->get());
        });
    }

    public function test_user_query_contact_and_self_filters(): void
    {
        $this->withMirrorTables(function (Capsule $capsule): void {
            $capsule->table('tf_users')->insert([
                ['account_id' => 1, 'id' => 1, 'constructor' => 'user', 'contact' => true, 'self' => false],
                ['account_id' => 1, 'id' => 2, 'constructor' => 'user', 'contact' => false, 'self' => true],
            ]);

            self::assertCount(1, (new UserQuery($capsule->table('tf_users')))->setModel($this->usersModel())->contacts()->get());
            self::assertCount(1, (new UserQuery($capsule->table('tf_users')))->setModel($this->usersModel())->self()->get());
        });
    }

    // ---- DialogQuery ----

    public function test_dialog_query_account_and_folder_scoping(): void
    {
        $this->withMirrorTables(function (Capsule $capsule): void {
            $capsule->table('tf_dialogs')->insert([
                ['account_id' => 1, 'peer_type' => 2, 'peer_id' => 101, 'constructor' => 'dialog', 'pinned' => 0, 'top_message' => 1, 'unread_count' => 0],
                ['account_id' => 2, 'peer_type' => 2, 'peer_id' => 202, 'constructor' => 'dialog', 'pinned' => 0, 'top_message' => 2, 'unread_count' => 3],
                ['account_id' => 1, 'peer_type' => 2, 'peer_id' => 303, 'constructor' => 'dialog', 'pinned' => 1, 'top_message' => 9, 'unread_count' => 0],
            ]);

            // folder_id is a 1:1 fact on a CHILD table; a dialog with no folder
            // has no row, which is exactly what the correlated EXISTS models.
            $capsule->table('tf_dialogs_folder_id')->insert([
                ['account_id' => 1, 'peer_type' => 2, 'peer_id' => 101, 'folder_id' => 5],
                ['account_id' => 1, 'peer_type' => 2, 'peer_id' => 303, 'folder_id' => 5],
                ['account_id' => 2, 'peer_type' => 2, 'peer_id' => 202, 'folder_id' => 7],
            ]);

            self::assertCount(2, (new DialogQuery($capsule->table('tf_dialogs')))->setModel($this->dialogsModel())->forAccount(1)->get());
            self::assertCount(2, (new DialogQuery($capsule->table('tf_dialogs')))->setModel($this->dialogsModel())->inFolder(5)->get());
            self::assertCount(1, (new DialogQuery($capsule->table('tf_dialogs')))->setModel($this->dialogsModel())->pinned()->get());
            self::assertCount(1, (new DialogQuery($capsule->table('tf_dialogs')))->setModel($this->dialogsModel())->withUnread()->get());
        });
    }

    public function test_dialog_query_ordering_pins_first_then_top_message_desc(): void
    {
        $this->withMirrorTables(function (Capsule $capsule): void {
            $capsule->table('tf_dialogs')->insert([
                ['account_id' => 1, 'peer_type' => 2, 'peer_id' => 1, 'constructor' => 'dialog', 'pinned' => 0, 'top_message' => 5, 'unread_count' => 0],
                ['account_id' => 1, 'peer_type' => 2, 'peer_id' => 2, 'constructor' => 'dialog', 'pinned' => 1, 'top_message' => 3, 'unread_count' => 0],
                ['account_id' => 1, 'peer_type' => 2, 'peer_id' => 3, 'constructor' => 'dialog', 'pinned' => 0, 'top_message' => 8, 'unread_count' => 0],
            ]);

            $query = (new DialogQuery($capsule->table('tf_dialogs')))->setModel($this->dialogsModel());
            $rows = $query->ordered()->get()->all();

            self::assertSame([2, 3, 1], array_map(fn ($r) => (int) $r->peer_id, $rows));
        });
    }
}
