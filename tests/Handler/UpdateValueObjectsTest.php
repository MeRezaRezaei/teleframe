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
        $u = Update::fromMirror($this->createStub(\MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel::class), 9);

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

    /**
     * The oracle is a LITERAL, frozen here on purpose.
     *
     * `assertSame($a->updateId(), $b->updateId())` re-ran the very primitive
     * it was checking, so an `updateId()` that ignored `$ts` or `$accountId`
     * — the exact shape of the shipped UTF-16 rtrim defect — compared
     * equal with itself and stayed green. The constant below is the digest of
     * the preimage `"7\0" . "100\0" . {"_":"updateNewMessage","message":"hi"}`.
     * Nothing in this test computes it, so the formula (both operands, both
     * separators, the unescaped-slash/unicode JSON) is pinned, not assumed;
     * the 40-character width rides on the same comparison.
     */
    public function test_update_id_is_a_deterministic_content_hash(): void
    {
        $expected = 'eb647de822dba0ef72dbbab31bb931c19a167da8';

        $a = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);
        $b = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'hi'], 7, 100);

        self::assertSame($expected, $a->updateId());
        self::assertSame($expected, $b->updateId(), 'one entry hashes the same in every instance');
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

    /**
     * The seeded digest is a LITERAL for the same reason: two `updateId()`
     * calls prove the function is pure, not that `created_at` ever reached
     * it. `getAttributes()` is pinned here too, so the preimage is a
     * deliberate, hand-checkable one —
     * `"9\0" . "500\0" . {"_":"updateUserStatus","random_id":"r1"}` —
     * instead of whatever a bare mock happens to hand back.
     */
    public function test_from_mirror_seeds_update_id_from_model_created_at(): void
    {
        $created = \Carbon\Carbon::createFromTimestamp(500);
        $model = $this->createStub(\MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel::class);
        $model->method('getAttribute')->willReturn($created);
        $model->method('getAttributes')->willReturn([
            'constructor' => 'updateUserStatus',
            'random_id' => 'r1',
        ]);

        $u = Update::fromMirror($model, 9);

        self::assertSame(500, $u->ts, 'created_at seeds ts');
        self::assertSame(
            '4cee778f64654cb00025759cc89ef495f85739e8',
            $u->updateId(),
            'and ts, the account and the canonical identity row all feed the seeded hash',
        );
    }
}