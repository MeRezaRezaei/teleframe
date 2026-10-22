<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;

/**
 * Local anchor model used to exercise AccountScoped without touching
 * generated models.  Carries account_id + a peer_id long so both
 * AccountContext isolation and PeerResolution scopes can be validated.
 */
final class IsolatedAnchor extends Model
{
    use AccountScoped;

    protected $table = 'isolated_anchors';
    public $timestamps = false;

    /** @var array<int, string> */
    protected $casts = [
        'account_id' => 'integer',
        'peer_id'    => 'integer',
    ];
}

/**
 * Local instance model for testing child-table inheritance of the
 * AccountScoped trait (child table also carries account_id).
 */
final class IsolatedInstance extends Model
{
    use AccountScoped;

    protected $table = 'isolated_instances';
    public $timestamps = false;

    /** @var array<int, string> */
    protected $casts = [
        'account_id' => 'integer',
    ];
}

final class AccountIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('isolated_anchors', static function (Blueprint $t): void {
            $t->bigInteger('id');
            $t->bigInteger('account_id');
            $t->bigInteger('peer_id');
            $t->primary(['id']);
        });

        Schema::create('isolated_instances', static function (Blueprint $t): void {
            $t->bigInteger('id');
            $t->bigInteger('account_id');
            $t->string('label');
            $t->primary(['id']);
        });

        // Seed two accounts
        IsolatedAnchor::insert([
            ['id' => 1, 'account_id' => 1, 'peer_id' => 100],
            ['id' => 2, 'account_id' => 8, 'peer_id' => 200],
        ]);

        IsolatedInstance::insert([
            ['id' => 10, 'account_id' => 1, 'label' => 'a1'],
            ['id' => 20, 'account_id' => 8, 'label' => 'a8'],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('isolated_anchors');
        Schema::dropIfExists('isolated_instances');
        AccountContext::reset();

        parent::tearDown();
    }

    // ── AccountContext basics ──────────────────────────────────────

    public function test_current_returns_null_without_context(): void
    {
        AccountContext::reset();
        self::assertNull(AccountContext::current());
    }

    public function test_set_and_current(): void
    {
        AccountContext::set(42);
        self::assertSame(42, AccountContext::current());
    }

    public function test_reset_clears_context(): void
    {
        AccountContext::set(42);
        AccountContext::reset();
        self::assertNull(AccountContext::current());
    }

    public function test_for_sets_and_restores(): void
    {
        AccountContext::set(1);

        $result = AccountContext::for(99, static function () {
            return AccountContext::current();
        });

        self::assertSame(99, $result);
        self::assertSame(1, AccountContext::current());
    }

    public function test_for_restores_previous_after_exception(): void
    {
        AccountContext::set(1);

        try {
            AccountContext::for(99, static function (): never {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
            // expected
        }

        self::assertSame(1, AccountContext::current());
    }

    public function test_for_falls_back_to_config_when_no_singleton(): void
    {
        $this->app->config->set('teleframe.primary_account_id', 7);

        // Reset to simulate "no current binding"
        AccountContext::reset();

        $resolved = $this->app->make(AccountContext::class);
        self::assertSame(7, $resolved->current());
    }

    // ── Default global scope filters by current account ─────────────

    public function test_default_scope_filters_by_current_account(): void
    {
        AccountContext::for(1, static function (): void {
            self::assertSame(1, IsolatedAnchor::count());
            self::assertSame(1, IsolatedAnchor::first()->account_id);
        });
    }

    public function test_default_scope_filters_instance_model_too(): void
    {
        AccountContext::for(1, static function (): void {
            self::assertSame(1, IsolatedInstance::count());
            self::assertSame('a1', IsolatedInstance::first()->label);
        });
    }

    // ── acrossAccounts drops the scope ─────────────────────────────

    public function test_across_accounts_reveals_all(): void
    {
        AccountContext::for(1, static function (): void {
            $all = IsolatedAnchor::acrossAccounts()->orderBy('id')->get();
            self::assertCount(2, $all);
            self::assertSame([1, 8], $all->pluck('account_id')->all());
        });
    }

    // ── forAccount() overrides the current context ──────────────────

    public function test_for_account_overrides_current_context(): void
    {
        AccountContext::for(1, static function (): void {
            $otherAccount = IsolatedAnchor::forAccount(8)->first();
            self::assertNotNull($otherAccount);
            self::assertSame(8, $otherAccount->account_id);
        });
    }

    public function test_for_account_combined_with_across(): void
    {
        // No context set: acrossAccounts returns everything
        $all = IsolatedAnchor::acrossAccounts()->orderBy('id')->get();
        self::assertCount(2, $all);

        // forAccount(8) returns only account 8's row
        AccountContext::for(1, static function (): void {
            $a8 = IsolatedAnchor::forAccount(8)->get();
            self::assertCount(1, $a8);
            self::assertSame(200, $a8->first()->peer_id);
        });
    }
}
