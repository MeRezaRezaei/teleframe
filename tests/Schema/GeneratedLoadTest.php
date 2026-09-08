<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUserAbstractData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UserData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UserEmptyData;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFull;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * Task 5 load goldens: the committed generated/ layer is actually loadable
 * through the composer PSR-4 mapping (MeRezaRezaei\Teleframe\Schema\Generated\
 * => generated/), and the model/DTO classes resolve + instantiate for real.
 * Guards review findings C1 (dropped HasFactory import made all 1684 instance
 * models fatal on load) and C2 (missing autoload mapping).
 */
final class GeneratedLoadTest extends TestCase
{
    public function test_generated_models_are_autoloadable_and_newable(): void
    {
        // Anchor model (root-namespace User) — the wire-decode entry point.
        self::assertTrue(class_exists(TlUser::class));
        $anchor = new TlUser();
        self::assertSame('tl_user', $anchor->getTable());

        // User instance model (constructor table row).
        self::assertTrue(class_exists(TlUserFull::class));
        self::assertSame('tl_user_full', (new TlUserFull())->getTable());

        // Message instance model; the `use HasFactory, HasTlChildren;` trait
        // lines must resolve via the restored import (review C1).
        self::assertTrue(class_exists(TlMessageMessage::class));
        $message = new TlMessageMessage();
        self::assertSame('tl_message_message', $message->getTable());
        self::assertArrayHasKey(
            HasFactory::class,
            class_uses(TlMessageMessage::class),
            'HasFactory import missing from generated instance model',
        );
    }

    public function test_user_dto_from_wire_dispatches_on_constructor_name(): void
    {
        $user = TlUserAbstractData::hydrate([
            '_' => 'user',
            'flags' => 0,
            'flags2' => 0,
            'id' => 777,
            'access_hash' => 1,
            'first_name' => 'Ada',
        ]);
        self::assertInstanceOf(UserData::class, $user);
        self::assertSame(777, $user->id);
        self::assertSame('Ada', $user->firstName);
        self::assertFalse($user->self);

        $empty = TlUserAbstractData::hydrate(['_' => 'userEmpty', 'id' => 1]);
        self::assertInstanceOf(UserEmptyData::class, $empty);
        self::assertSame(1, $empty->id);
    }
}
