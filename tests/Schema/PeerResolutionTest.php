<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;

/**
 * Local peer-holder model for exercising PeerResolution in isolation.
 */
final class PeerHolder extends Model
{
    use AccountScoped, PeerResolution;

    protected $table = 'peer_holders';
    public $timestamps = false;

    /** @var array<int, string> */
    protected $casts = [
        'account_id' => 'integer',
        'peer_id'    => 'integer',
    ];
}

/**
 * Lightweight stand-in for TlUser (generated model) so we can test
 * resolvePeerModel via the $classMap seam without importing generated code.
 */
final class FakeTlUser extends Model
{
    use AccountScoped;

    protected $table = 'fake_tl_users';
    public $timestamps = false;

    /** @var array<int, string> */
    protected $casts = [
        'account_id' => 'integer',
        'tl_id'      => 'integer',
    ];
}

/**
 * Stand-in for TlChat.
 */
final class FakeTlChat extends Model
{
    use AccountScoped;

    protected $table = 'fake_tl_chats';
    public $timestamps = false;

    /** @var array<int, string> */
    protected $casts = [
        'account_id' => 'integer',
        'tl_id'      => 'integer',
    ];
}

/**
 * Stand-in for TlChannel.
 */
final class FakeTlChannel extends Model
{
    use AccountScoped;

    protected $table = 'fake_tl_channels';
    public $timestamps = false;

    /** @var array<int, string> */
    protected $casts = [
        'account_id' => 'integer',
        'tl_id'      => 'integer',
    ];
}

final class PeerResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('peer_holders', static function (Blueprint $t): void {
            $t->bigInteger('id');
            $t->bigInteger('account_id');
            $t->bigInteger('peer_id');
            $t->primary(['id']);
        });

        Schema::create('fake_tl_users', static function (Blueprint $t): void {
            $t->bigInteger('id');
            $t->bigInteger('account_id');
            $t->bigInteger('tl_id');
            $t->primary(['id']);
        });

        Schema::create('fake_tl_chats', static function (Blueprint $t): void {
            $t->bigInteger('id');
            $t->bigInteger('account_id');
            $t->bigInteger('tl_id');
            $t->primary(['id']);
        });

        Schema::create('fake_tl_channels', static function (Blueprint $t): void {
            $t->bigInteger('id');
            $t->bigInteger('account_id');
            $t->bigInteger('tl_id');
            $t->primary(['id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('peer_holders');
        Schema::dropIfExists('fake_tl_users');
        Schema::dropIfExists('fake_tl_chats');
        Schema::dropIfExists('fake_tl_channels');
        AccountContext::reset();

        parent::tearDown();
    }

    // ── scopeWherePeerLong ─────────────────────────────────────────

    public function test_scope_where_peer_long(): void
    {
        PeerHolder::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => PeerIdTool::userLong(42)],
            ['id' => 2, 'account_id' => 1, 'peer_id' => PeerIdTool::chatLong(7)],
        ]);

        AccountContext::for(1, static function (): void {
            $users = PeerHolder::acrossAccounts()
                ->wherePeerLong('peer_id', PeerIdTool::userLong(42))
                ->get();
            self::assertCount(1, $users);
            self::assertSame(1, $users->first()->id);
        });
    }

    // ── scopeWherePeerIsUser / Chat / Channel ──────────────────────

    public function test_scope_where_peer_is_chat(): void
    {
        PeerHolder::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => PeerIdTool::chatLong(7)],
            ['id' => 2, 'account_id' => 1, 'peer_id' => PeerIdTool::userLong(7)],
        ]);

        AccountContext::for(1, static function (): void {
            $chats = PeerHolder::acrossAccounts()
                ->wherePeerIsChat('peer_id', 7)
                ->get();
            self::assertCount(1, $chats);
            self::assertSame(1, $chats->first()->id);
        });
    }

    public function test_scope_where_peer_is_channel(): void
    {
        PeerHolder::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => PeerIdTool::channelLong(100)],
            ['id' => 2, 'account_id' => 1, 'peer_id' => PeerIdTool::userLong(100)],
        ]);

        AccountContext::for(1, static function (): void {
            $channels = PeerHolder::acrossAccounts()
                ->wherePeerIsChannel('peer_id', 100)
                ->get();
            self::assertCount(1, $channels);
            self::assertSame(1, $channels->first()->id);
        });
    }

    public function test_scope_where_peer_is_user(): void
    {
        PeerHolder::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => PeerIdTool::userLong(55)],
            ['id' => 2, 'account_id' => 1, 'peer_id' => PeerIdTool::chatLong(55)],
        ]);

        AccountContext::for(1, static function (): void {
            $users = PeerHolder::acrossAccounts()
                ->wherePeerIsUser('peer_id', 55)
                ->get();
            self::assertCount(1, $users);
            self::assertSame(1, $users->first()->id);
        });
    }

    // ── resolvePeerModel via classMap seam ──────────────────────────

    public function test_resolve_peer_model_returns_chat_for_current_account(): void
    {
        // Seed FakeTlChat rows for two accounts
        FakeTlChat::insert([
            ['id' => 100, 'account_id' => 1, 'tl_id' => 7],
            ['id' => 200, 'account_id' => 8, 'tl_id' => 7],
        ]);

        // PeerHolder pointing to chat 7
        PeerHolder::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => PeerIdTool::chatLong(7)],
        ]);

        $classMap = [
            'chat' => FakeTlChat::class,
        ];

        AccountContext::for(1, static function () use ($classMap): void {
            $holder = PeerHolder::first();
            $peer   = $holder->resolvePeerModel('peer_id', $classMap);
            self::assertInstanceOf(FakeTlChat::class, $peer);
            self::assertSame(7, (int) $peer->tl_id);
            self::assertSame(1, (int) $peer->account_id);
        });
    }

    public function test_resolve_peer_model_returns_null_when_peer_not_seen(): void
    {
        PeerHolder::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => PeerIdTool::userLong(999)],
        ]);

        AccountContext::for(1, static function (): void {
            $holder = PeerHolder::first();
            $peer   = $holder->resolvePeerModel('peer_id', [
                'user' => FakeTlUser::class,
            ]);
            self::assertNull($peer);
        });
    }

    public function test_resolve_peer_model_is_isolated_per_account(): void
    {
        // FakeTlChat row only for account 8
        FakeTlChat::insert([
            ['id' => 100, 'account_id' => 8, 'tl_id' => 42],
        ]);

        PeerHolder::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => PeerIdTool::chatLong(42)],
        ]);

        $classMap = ['chat' => FakeTlChat::class];

        AccountContext::for(1, static function () use ($classMap): void {
            $holder = PeerHolder::first();
            $peer   = $holder->resolvePeerModel('peer_id', $classMap);
            self::assertNull($peer, 'Account 1 should not see account 8\'s chat row');
        });
    }

    public function test_resolve_peer_model_returns_user_via_decoder(): void
    {
        FakeTlUser::insert([
            ['id' => 50, 'account_id' => 1, 'tl_id' => 777],
        ]);

        PeerHolder::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => PeerIdTool::userLong(777)],
        ]);

        $classMap = ['user' => FakeTlUser::class];

        AccountContext::for(1, static function () use ($classMap): void {
            $peer = PeerHolder::first()->resolvePeerModel('peer_id', $classMap);
            self::assertInstanceOf(FakeTlUser::class, $peer);
            self::assertSame(777, (int) $peer->tl_id);
        });
    }

    public function test_resolve_peer_model_returns_channel(): void
    {
        FakeTlChannel::insert([
            ['id' => 200, 'account_id' => 1, 'tl_id' => 55],
        ]);

        PeerHolder::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => PeerIdTool::channelLong(55)],
        ]);

        $classMap = ['channel' => FakeTlChannel::class];

        AccountContext::for(1, static function () use ($classMap): void {
            $peer = PeerHolder::first()->resolvePeerModel('peer_id', $classMap);
            self::assertInstanceOf(FakeTlChannel::class, $peer);
            self::assertSame(55, (int) $peer->tl_id);
        });
    }
}
