<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\Update;
use PHPUnit\Framework\TestCase;

final class UpdateValueObjectsTest extends TestCase
{
    public function test_constructor_name_is_read_lazily_from_raw(): void
    {
        $u = Update::fromBus(['_' => 'updateNewMessage'], 1);

        self::assertSame('updateNewMessage', $u->constructor());
    }

    public function test_bus_and_event_sources_are_distinct(): void
    {
        self::assertSame('bus', Update::fromBus([], 1)->source);
        self::assertSame('event', (new Update([], 1))->source);
    }

    public function test_from_mirror_has_empty_constructor_and_carries_model_slot(): void
    {
        $u = Update::fromMirror($this->createStub(\MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel::class), 9);

        self::assertSame('', $u->constructor());
        self::assertSame('event', $u->source);
        self::assertNotNull($u->model);
    }

    public function test_immutability_via_with_helpers(): void
    {
        $u = Update::fromBus(['_' => 'x'], 1);
        $owned = $u->withSelfOriginated(true);

        self::assertFalse($u->selfOriginated);
        self::assertTrue($owned->selfOriginated);
        self::assertNotSame($u, $owned);
    }
}