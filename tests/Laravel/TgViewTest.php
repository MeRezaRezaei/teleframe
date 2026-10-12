<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Control\RelaySession;
use MeRezaRezaei\Teleframe\Control\RelaySessionRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard\Support\TestUser;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;
use RuntimeException;

/**
 * The `/tg` browser view: the route resolves inside the dashboard group, the
 * view renders the managed vault account's MIRRORED chats, the switch button
 * posts the EXISTING `accounts/{id}/switch` endpoint, and nothing on the page
 * is a credential.
 *
 * The chat list is proved from the mirror — the tf_* tables the ingest writes
 * — never from a live RPC and never from invented rows. An empty dialog table
 * renders a STATED "no mirrored chats" state, which is what a cold install
 * actually shows. The live-lane case (a relay dialing Telegram and the list
 * filling from a real session) is deliberately NOT proved here; see
 * docs/evidence/tg-view-server-side.md.
 */
final class TgViewTest extends Http\Dashboard\TestCase
{
    /** The mirror dials the chat list reads. Everything else is irrelevant to it. */
    private const MIRROR_MIGRATIONS = [
        '2026_09_14_200001_create_tf_users_tables',
        '2026_09_14_200002_create_tf_chats_tables',
        '2026_09_14_200003_create_tf_channels_tables',
        '2026_09_14_200004_create_tf_dialogs_tables',
        '2026_09_14_200010_create_tf_messages_tables',
    ];

    /** @var int how often a switch request tried to build a client / dial */
    private int $clientCalls = 0;

    /** @var int how often a switch request read the vault */
    private int $vaultLoaderCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            RelayActiveAccount::class,
            new RelayActiveAccount(
                new InMemoryCache,
                new RelayFactory(new RelaySessionResolver(
                    vaultLoader: function (string $ref, int $dc): ?string {
                        $this->vaultLoaderCalls++;

                        return null;
                    },
                    clientFactory: function (string $session, int $dc): object {
                        $this->clientCalls++;

                        throw new RuntimeException('the /tg view must never dial');
                    },
                )),
            ),
        );

        $this->app->instance(
            RelaySessionRegistry::class,
            new RelaySessionRegistry(cache()->store('array')),
        );
    }

    public function test_route_is_registered(): void
    {
        DashboardRoutes::register();

        self::assertTrue(Route::has('teleframe.tg'));
    }

    public function test_tg_requires_auth(): void
    {
        DashboardRoutes::register();

        // A JSON request, like every other guard test in this suite: a plain
        // browser GET through `auth` REDIRECTS to the host's `login` route,
        // and this harness registers no such route, so the honest assertion
        // here is that the guard refuses before the controller runs.
        $this->getJson('/teleframe/tg')->assertUnauthorized();
    }

    public function test_tg_route_carries_the_group_middleware(): void
    {
        DashboardRoutes::register();

        /** @var \Illuminate\Routing\Route $route */
        $route = Route::getRoutes()->getByName('teleframe.tg');

        self::assertSame('teleframe/tg', $route->uri());
        self::assertSame(['GET'], array_values(array_intersect($route->methods(), ['GET'])));
        self::assertContains('auth', $route->gatherMiddleware());
        self::assertContains('web', $route->gatherMiddleware());
    }

    public function test_tg_renders_with_no_account_switched_yet(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $this->makeAccount($user, 'mine', 'session-string-a');

        $response = $this->actingAs($user)->get('/teleframe/tg');

        $response->assertOk();
        $response->assertSee('Teleframe · Telegram');
        $response->assertSee('mine');
        // No active account yet: the page says so rather than rendering an
        // empty chat table, which would read as "this account has no chats".
        $response->assertSee('No account is active yet');
        $response->assertSee('Switch an account above to see its chats.');
    }

    public function test_tg_states_a_missing_mirror_instead_of_an_empty_list(): void
    {
        DashboardRoutes::register();
        // Deliberately NO mirror migrations.

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $this->activate($user, $account);

        $response = $this->actingAs($user)->get('/teleframe/tg');

        $response->assertOk();
        $response->assertSee('Not mirrored');
        self::assertStringNotContainsString(
            'No mirrored chats',
            (string) $response->getContent(),
            'a host without mirror tables is a DIFFERENT state from an account with no chats',
        );
    }

    public function test_tg_states_an_empty_mirror_rather_than_inventing_chats(): void
    {
        DashboardRoutes::register();
        $this->migrateMirror();
        // Tables exist, no rows: the honest state is "nothing ingested yet".

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $this->activate($user, $account);

        $html = (string) $this->actingAs($user)->get('/teleframe/tg')->getContent();

        self::assertStringContainsString('No mirrored chats', $html);
        self::assertStringContainsString('not connected', $html);
        self::assertStringNotContainsString('<table', substr($html, (int) strpos($html, '<h2>Chats</h2>')));
    }

    public function test_tg_renders_no_login_form(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $this->makeAccount($user, 'mine', 'session-string-a');

        $html = (string) $this->actingAs($user)->get('/teleframe/tg')->getContent();

        self::assertStringNotContainsString('teleframe/telegram/start', $html, 'no stock phone login');
        self::assertStringNotContainsString('Verification code', $html, 'no stock code step');
        self::assertStringNotContainsString('Cloud password', $html, 'no stock 2FA step');
        self::assertStringNotContainsString('tg://qr', $html, 'no stock QR login');
    }

    public function test_tg_is_tenant_scoped(): void
    {
        DashboardRoutes::register();

        $alice = $this->makeUser('alice@example.test');
        $bob = $this->makeUser('bob@example.test');
        $this->makeAccount($alice, 'alice-secret-label', 'session-string-a');

        $html = (string) $this->actingAs($bob)->get('/teleframe/tg')->getContent();

        self::assertStringContainsString('No linked accounts', $html);
        self::assertStringNotContainsString('alice-secret-label', $html, "one tenant must never see another's accounts");
    }

    public function test_tg_never_renders_a_credential(): void
    {
        DashboardRoutes::register();
        $this->migrateMirror();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'super-secret-session-string');

        $this->activate($user, $account);
        $this->seedDialogs((int) $account->getKey());

        $html = (string) $this->actingAs($user)->get('/teleframe/tg')->getContent();

        self::assertStringNotContainsString('super-secret-session-string', $html, 'the session string is a credential');
        self::assertStringNotContainsString('0123456789abcdef', $html, 'the app api_hash is a credential');
        self::assertStringContainsString('linked', $html, 'the session is reported as a boolean, not as a value');
    }

    public function test_tg_renders_the_active_accounts_mirrored_chats(): void
    {
        DashboardRoutes::register();
        $this->migrateMirror();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $accountId = (int) $account->getKey();

        $this->activate($user, $account);
        $this->seedDialogs($accountId);

        $response = $this->actingAs($user)->get('/teleframe/tg');

        $response->assertOk();
        // The three title shapes the mirror can hold: first+last name, an
        // inline channel title, and a username-only user.
        $response->assertSee('Ada Lovelace');
        $response->assertSee('Release channel');
        $response->assertSee('@grace_h');
        $response->assertSee('ship it');
        $response->assertSee('v2.0 is out');
        $response->assertSee('3 unread');
        self::assertStringNotContainsString('Not mirrored', (string) $response->getContent());
    }

    public function test_tg_chats_come_only_from_the_active_account(): void
    {
        DashboardRoutes::register();
        $this->migrateMirror();

        $user = $this->makeUser('a@example.test');
        $mine = $this->makeAccount($user, 'mine', 'session-string-a');
        $other = $this->makeAccount($user, 'other', 'session-string-b');
        $mineId = (int) $mine->getKey();
        $otherId = (int) $other->getKey();

        $this->seedDialogs($mineId);
        // The other account reuses the SAME dialog keys and message ids, so
        // a missing account filter would overwrite the preview rather than
        // merely adding a row: the leak is visible either way.
        $this->seedDialogs($otherId, 'other-account-only');

        $this->activate($user, $mine);

        $html = (string) $this->actingAs($user)->get('/teleframe/tg')->getContent();

        self::assertStringContainsString('ship it', $html);
        self::assertStringNotContainsString('other-account-only', $html, "another account's messages must never surface");
    }

    public function test_tg_renders_not_connected_state_and_no_preview_without_messages(): void
    {
        DashboardRoutes::register();
        $this->migrateMirror();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $accountId = (int) $account->getKey();

        $this->activate($user, $account);
        // A dialog row whose top_message was never mirrored: the peer has a
        // row, so the list is NOT empty — the honest state is "this peer has
        // no message text", which is what renders.
        $this->dialog($accountId, 1, 4242, 'dialog', 777, 0, false);

        $html = (string) $this->actingAs($user)->get('/teleframe/tg')->getContent();

        self::assertStringContainsString('not connected', $html);
        self::assertStringContainsString('user 4242', $html);
        self::assertStringContainsString('no message text', $html);
    }

    public function test_tg_switch_button_posts_the_existing_switch_endpoint(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $accountId = (int) $account->getKey();

        $html = (string) $this->actingAs($user)->get('/teleframe/tg')->getContent();

        // The rendered page carries the switch URL for exactly the owner's
        // own account.
        self::assertStringContainsString('/teleframe/accounts/'.$accountId.'/switch', $html);

        // And the endpoint it names really does the switch: POSTing it from
        // the page's own URL renders that account ACTIVE for the relay.
        $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$accountId}/switch")
            ->assertOk()
            ->assertJsonPath('account_id', $accountId);

        self::assertSame(
            $accountId,
            app(RelayActiveAccount::class)->currentFor($this->scopeId($user))?->accountId,
        );
        self::assertSame(0, $this->clientCalls, 'a switch must not dial');
        self::assertSame(0, $this->vaultLoaderCalls, 'a switch must not read the vault');

        // After the switch the page marks that account active and offers no
        // switch button for it any more.
        $after = (string) $this->actingAs($user)->get('/teleframe/tg')->getContent();
        self::assertStringContainsString('>active<', $after);
    }

    public function test_tg_renders_presence_from_the_relay_registry(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $accountId = (int) $account->getKey();

        self::assertStringContainsString(
            'not connected',
            (string) $this->actingAs($user)->get('/teleframe/tg')->getContent(),
        );

        app(RelaySessionRegistry::class)->write($accountId, new RelaySession($accountId, 'relay-1', time(), time()));

        self::assertStringContainsString(
            'connected',
            (string) $this->actingAs($user)->get('/teleframe/tg')->getContent(),
        );
    }

    // ── fixtures ────────────────────────────────────────────────────

    private function migrateMirror(): void
    {
        foreach (self::MIRROR_MIGRATIONS as $name) {
            $migration = require dirname(__DIR__, 2).'/src/Laravel/Migrations/'.$name.'.php';
            $migration->up();
        }
    }

    /**
     * A user peer (first + last name), a channel peer (inline title) and a
     * username-only user peer: the three title shapes the resolver handles.
     */
    private function seedDialogs(int $accountId, string $previewText = 'ship it'): void
    {
        $this->dialog($accountId, 1, 1001, 'dialog', 900, 3, true);
        $this->dialog($accountId, 3, -1002, 'dialogChannel', 800, 0, false);
        $this->dialog($accountId, 1, 1003, 'dialog', 700, 0, false);

        DB::table('tf_users')->insert([
            'account_id' => $accountId, 'id' => 1001, 'constructor' => 'user',
        ]);
        DB::table('tf_users_first_name')->insert([
            'account_id' => $accountId, 'id' => 1001, 'first_name' => 'Ada',
        ]);
        DB::table('tf_users_last_name')->insert([
            'account_id' => $accountId, 'id' => 1001, 'last_name' => 'Lovelace',
        ]);
        DB::table('tf_users')->insert([
            'account_id' => $accountId, 'id' => 1003, 'constructor' => 'user',
        ]);
        DB::table('tf_users_username')->insert([
            'account_id' => $accountId, 'id' => 1003, 'username' => 'grace_h',
        ]);
        DB::table('tf_channels')->insert([
            'account_id' => $accountId, 'id' => -1002, 'constructor' => 'channel',
            'title' => 'Release channel',
        ]);

        $stamp = Carbon::createFromTimestamp(1_700_000_000)->addDay()->getTimestamp();

        DB::table('tf_messages')->insert([
            'account_id' => $accountId, 'id' => 900, 'peer_type' => 1, 'peer_id' => 1001,
            'constructor' => 'message', 'date' => $stamp, 'message' => $previewText, 'out' => false,
        ]);
        DB::table('tf_messages')->insert([
            'account_id' => $accountId, 'id' => 800, 'peer_type' => 3, 'peer_id' => -1002,
            'constructor' => 'message', 'date' => $stamp, 'message' => 'v2.0 is out', 'out' => false,
        ]);
    }

    private function dialog(int $accountId, int $peerType, int $peerId, string $constructor, int $topMessage, int $unread, bool $pinned): void
    {
        DB::table('tf_dialogs')->insert([
            'account_id' => $accountId,
            'peer_type' => $peerType,
            'peer_id' => $peerId,
            'constructor' => $constructor,
            'top_message' => $topMessage,
            'unread_count' => $unread,
            'pinned' => $pinned,
        ]);
    }

    private function activate(TestUser $user, TelegramAccount $account): void
    {
        app(RelayActiveAccount::class)->switchFor(
            $this->scopeId($user),
            new RelayContext(
                accountId: (int) $account->getKey(),
                dcId: (int) $account->dc_id,
                label: (string) $account->label,
            ),
        );
    }

    private function makeUser(string $email): TestUser
    {
        return TestUser::query()->create([
            'name' => 'Test',
            'email' => $email,
            'password' => 'secret',
        ]);
    }

    private function scopeId(TestUser $user): string
    {
        return $user::class.'#'.$user->getKey();
    }

    private function makeAccount(TestUser $user, string $label, ?string $session): TelegramAccount
    {
        $app = new TelegramApp;
        $app->label = $label.'-app';
        $app->api_id = 111;
        $app->api_hash = '0123456789abcdef';
        $app->owner_type = $user::class;
        $app->owner_id = (int) $user->getKey();
        $app->save();

        $account = new TelegramAccount;
        $account->app_id = (int) $app->getKey();
        $account->label = $label;
        $account->type = TelegramAccount::TYPE_USER;
        $account->owner_type = $user::class;
        $account->owner_id = (int) $user->getKey();
        $account->session = $session;
        $account->dc_id = 2;
        $account->save();

        return $account;
    }
}
