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

    public function test_update_id_is_a_deterministic_content_hash(): void
    {
        $a = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);
        $b = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertSame($a->updateId(), $b->updateId());
        self::assertSame(40, strlen($a->updateId())); // sha1 hex
    }

    public function test_update_id_changes_with_payload_account_or_ts(): void
    {
        $base = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertNotSame(
            $base->updateId(),
            Update::fromBus(['_' => 'updateNewMessage', 'message' => 'yo'], 7, 100)->updateId(),
        );
        self::assertNotSame(
            $base->updateId(),
            Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 8, 100)->updateId(),
        );
        self::assertNotSame(
            $base->updateId(),
            Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 101)->updateId(),
        );
    }

    public function test_from_mirror_seeds_update_id_from_model_created_at(): void
    {
        $created = \Carbon\Carbon::createFromTimestamp(500);
        $model = $this->createStub(\MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel::class);
        $model->method('getAttribute')->willReturn($created);

        $u = Update::fromMirror($model, 9);

        self::assertSame(500, $u->ts);
        self::assertSame($u->updateId(), Update::fromMirror($model, 9)->updateId());
    }
}