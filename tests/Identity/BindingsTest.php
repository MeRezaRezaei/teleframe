<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity;

use MeRezaRezaei\Teleframe\Identity\Bindings;
use MeRezaRezaei\Teleframe\Identity\TlUserBinding;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestContactableUser;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestPlainUser;

/**
 * The static façade: Q7 findTF tenancy (never a cross-tenant global scan),
 * Q8 bindLaravelUser, the Q11 UpdateStored write-hook (exact tg-id
 * extraction, never a guess), and the Q8/Q11 onLogin write-hook.
 */
class BindingsTest extends TestCase
{
    public function testBindLaravelUserCreatesMorphRow(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        $binding = Bindings::bindLaravelUser($user, 42, 5);

        self::assertSame(42, $binding->tl_user_id);
        self::assertSame(5, $binding->account_id);
        self::assertSame($user->id, (int) $binding->fresh()->user_id);
        self::assertSame($user->id, (int) $binding->resolver()->getKey());
    }

    public function testBindLaravelUserWithoutAccountIsPlainTenantNull(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        $binding = Bindings::bindLaravelUser($user, 42);

        self::assertNull($binding->account_id);
        self::assertSame($user->id, (int) $binding->resolver()->getKey());
    }

    public function testFindTFTenancy(): void
    {
        TlUserBinding::create(['tl_user_id' => 42, 'account_id' => 5]);

        // No primary -> deterministic first-known-account default resolves the
        // sole tenant row; an explicit other account never scans globally.
        self::assertSame(5, Bindings::findTF(42)?->account_id);
        self::assertNotNull(Bindings::findTF(42, 5));
        self::assertNull(Bindings::findTF(42, 3));

        config(['teleframe.primary_account_id' => 5]);
        self::assertNotNull(Bindings::findTF(42));
    }

    public function testOnStoreUpdateRegistersAndClearsContactLost(): void
    {
        Bindings::onLogin(42, 5)->markContactLost();

        $binding = Bindings::onStoreUpdate(new UpdateStored($this->tlUserRoot(42), 5));

        self::assertNotNull($binding);
        self::assertSame(42, $binding->tl_user_id);
        self::assertSame(5, $binding->account_id);
        self::assertFalse($binding->fresh()->contact_lost);
    }

    public function testOnStoreUpdateUsesEventAccount(): void
    {
        Bindings::onLogin(42, 9)->markContactLost();

        $binding = Bindings::onStoreUpdate(new UpdateStored($this->tlUserRoot(42), 9));

        self::assertSame(9, $binding?->account_id);
        self::assertFalse($binding?->fresh()->contact_lost);
    }

    public function testOnStoreUpdateNoopWithoutExactTelegramId(): void
    {
        $model = new TlUserRootWithoutId();

        self::assertNull(Bindings::onStoreUpdate(new UpdateStored($model, 5)));
    }

    public function testOnLoginRegistersBindingWithoutUser(): void
    {
        $binding = Bindings::onLogin(42, 3);

        self::assertSame(42, $binding->tl_user_id);
        self::assertSame(3, $binding->account_id);
        self::assertNull($binding->resolver());
        self::assertSame(1, TlUserBinding::query()->where('tl_user_id', 42)->where('account_id', 3)->count());
    }

    public function testTelegramUserIdFromModelTlIdWinsForUserNamespaceRoot(): void
    {
        self::assertSame(19, Bindings::telegramUserIdFromModel($this->tlUserRoot(19)));
    }

    public function testTelegramUserIdFromModelReadsTlUserIdAttribute(): void
    {
        $user = new TestPlainUser();
        $user->tl_user_id = 37;

        self::assertSame(37, Bindings::telegramUserIdFromModel($user));
    }

    public function testTelegramUserIdFromModelNullWithoutExactMarker(): void
    {
        self::assertNull(Bindings::telegramUserIdFromModel(new TestPlainUser()));

        $root = new TlUserRootWithoutId();
        self::assertNull(Bindings::telegramUserIdFromModel($root));
    }

    /**
     * User-namespace root instance (`tl_user_user` table + `tl_id` attribute)
     * memcached row-free — the onStoreUpdate write-hook only reads attributes.
     */
    private function tlUserRoot(int $tgId): TlUserRoot
    {
        return new TlUserRoot(['tl_id' => $tgId]);
    }
}

/**
 * In-memory TlInstanceModel mirroring the generated `TlUserUser` shape
 * (table `tl_user_user`, `tl_id` cast to int) — used as the UpdateStored
 * fixture without any database row.
 */
final class TlUserRoot extends TlInstanceModel
{
    protected $table = 'tl_user_user';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = ['tl_id' => 'int'];
}

/**
 * TlInstanceModel root WITHOUT any telegram-id marker column.
 */
final class TlUserRootWithoutId extends TlInstanceModel
{
    protected $table = 'tl_user_user';

    protected $guarded = [];
}