<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity;

use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Identity\Bindings;
use MeRezaRezaei\Teleframe\Identity\TlUserBinding;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestContactableUser;

/**
 * Q8/Q7 model-level ruling tests: stable telegram-id key, nullable morph
 * (plain-PHP safe), tenant-scoped find (never cross-tenant), unique
 * (tl_user_id, account_id), contact_lost survives deletion, ensureBinding
 * upsert semantics.
 */
class TlUserBindingTest extends TestCase
{
    public function testCreateAndFindByTgId(): void
    {
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => null]);

        $binding = TlUserBinding::bindingFor(42);

        self::assertNotNull($binding);
        self::assertSame(42, $binding->tl_user_id);
        self::assertNull($binding->account_id);
        self::assertFalse($binding->contact_lost);
        self::assertNull($binding->user_type);
        self::assertNull($binding->user_id);
    }

    public function testPrimaryAccountDefaultWhenConfigured(): void
    {
        config(['teleframe.primary_account_id' => 7]);
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 5]);
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 7]);

        $binding = TlUserBinding::bindingFor(42);

        self::assertSame(7, $binding?->account_id);
    }

    public function testExplicitAccountOverrideWins(): void
    {
        config(['teleframe.primary_account_id' => 7]);
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 5]);

        $binding = TlUserBinding::bindingFor(42, 5);

        self::assertSame(5, $binding?->account_id);
    }

    public function testFirstKnownAccountFallback(): void
    {
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 300]);
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 5]);

        $binding = TlUserBinding::bindingFor(42);

        self::assertSame(5, $binding?->account_id);
    }

    public function testSingleTenantRowResolvesViaFirstKnownAccount(): void
    {
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 5]);

        // No primary configured: the deterministic first-known-account default
        // resolves the sole tenant row without any cross-tenant global scan.
        self::assertSame(5, TlUserBinding::bindingFor(42)?->account_id);
    }

    public function testNoRowsReturnsNull(): void
    {
        self::assertNull(TlUserBinding::bindingFor(42));
        self::assertNull(TlUserBinding::bindingFor(42, 5));
    }

    public function testUniqueTlUserAccountPairEnforced(): void
    {
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 5]);

        $this->expectException(QueryException::class);
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 5]);
    }

    public function testSeparateAccountsAreSeparateRows(): void
    {
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 5]);
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 9]);

        self::assertSame(2, TlUserBinding::query()->where('tl_user_id', 42)->count());
    }

    public function testBindingSurvivesBoundUserDeletion(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        $binding = Bindings::bindLaravelUser($user, 42, 5);

        $user->delete();

        self::assertDatabaseHas('tl_user_bindings', ['id' => $binding->id]);
        self::assertSame(42, $binding->fresh()->tl_user_id);
    }

    public function testMarkContactLostTogglesFlag(): void
    {
        $binding = TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 5]);
        self::assertFalse($binding->fresh()->contact_lost);

        $binding->markContactLost();
        self::assertTrue($binding->fresh()->contact_lost);

        $binding->markContactLost(false);
        self::assertFalse($binding->fresh()->contact_lost);
    }

    public function testEnsureBindingIsIdempotent(): void
    {
        $first = TlUserBinding::ensureBinding(42, 5);
        $second = TlUserBinding::ensureBinding(42, 5);

        self::assertSame($first->id, $second->id);
        self::assertSame(1, TlUserBinding::query()->where('tl_user_id', 42)->count());
    }

    public function testEnsureBindingWithoutUserIsPlainBinding(): void
    {
        $binding = TlUserBinding::ensureBinding(42, 5);

        self::assertNull($binding->user_type);
        self::assertNull($binding->user_id);
        self::assertNull($binding->resolver());
    }

    public function testEnsureBindingAssociatesUser(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        $binding = TlUserBinding::ensureBinding(42, 5, $user);

        self::assertSame($user->id, (int) $binding->fresh()->user_id);
        self::assertSame($user->id, (int) $binding->resolver()->getKey());
    }

    public function testEnsureBindingUpdatesMorphWhenUserChanges(): void
    {
        $first = TestContactableUser::create(['name' => 'A']);
        $second = TestContactableUser::create(['name' => 'B']);

        TlUserBinding::ensureBinding(42, 5, $first);
        $binding = TlUserBinding::ensureBinding(42, 5, $second);

        self::assertSame($second->id, (int) $binding->fresh()->user_id);
        self::assertSame(1, TlUserBinding::query()->where('tl_user_id', 42)->count());
    }

    public function testResolverReturnsBoundUser(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        $binding = Bindings::bindLaravelUser($user, 42, 5);

        self::assertSame($user->id, (int) $binding->resolver()->getKey());
    }
}