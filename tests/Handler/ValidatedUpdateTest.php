<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\ValidatedUpdate;
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;

/**
 * Spec 5a §4: ValidatedUpdate, the FormRequest analog. A handler opts in by
 * type-hinting it; the dispatcher constructs it from the active frame. The
 * wrapper never throws on invalid input and never auto-replies (Q6a) —
 * ``valid()``/``errors()`` are explicit reads.
 */
final class ValidatedUpdateTest extends TestCase
{
    private HandlerRegistry $registry;
    private ArrayContainer $container;
    private ArrayCache $sends;

    protected function setUp(): void
    {
        $this->registry = new HandlerRegistry();
        $this->container = new ArrayContainer();
        $this->sends = new ArrayCache();
    }

    public function test_well_formed_update_is_valid(): void
    {
        $update = Update::fromBus([
            '_' => 'updateNewMessage',
            'from' => ['id' => 1],
            'chat' => ['id' => 2],
            'message' => ['text' => 'hello'],
        ], 7);

        $validated = new ValidatedUpdate($update);

        self::assertTrue($validated->valid());
        self::assertSame([], $validated->errors());
    }

    public function test_malformed_update_reports_errors_without_throwing(): void
    {
        $update = Update::fromBus(['_' => 'updateNewMessage'], 7);

        $validated = new ValidatedUpdate($update);

        self::assertFalse($validated->valid());
        self::assertNotEmpty($validated->errors());
        self::assertArrayHasKey('from', $validated->errors());
        self::assertArrayHasKey('chat', $validated->errors());
        self::assertArrayHasKey('message', $validated->errors());
    }

    public function test_rules_expose_the_guardrail_shape(): void
    {
        $validated = new ValidatedUpdate(Update::fromBus([], 1));

        self::assertArrayHasKey('from', $validated->rules());
        self::assertArrayHasKey('chat', $validated->rules());
        self::assertArrayHasKey('message', $validated->rules());
    }

    public function test_update_passthrough_returns_the_underlying_frame(): void
    {
        $update = Update::fromBus(['_' => 'x'], 3);
        $validated = new ValidatedUpdate($update);

        self::assertSame($update, $validated->update());
    }

    public function test_resolves_through_the_container_during_fake_dispatch(): void
    {
        $seen = null;
        $this->registry->onMessage(function (Update $u, ValidatedUpdate $v) use (&$seen): void {
            $seen = $v;
        });

        $fake = new FakeDispatcher(
            [[
                'update' => [
                    '_' => 'updateNewMessage',
                    'from' => ['id' => 1],
                    'chat' => ['id' => 2],
                    'message' => ['text' => 'hello'],
                ],
                'account_id' => 5,
            ]],
            $this->registry,
            $this->container,
            $this->sends,
        );

        $fake->run();

        self::assertInstanceOf(ValidatedUpdate::class, $seen);
        self::assertTrue($seen->valid());
        self::assertSame(5, $seen->update()->accountId);
        self::assertCount(1, $fake->dispatched);
    }
}