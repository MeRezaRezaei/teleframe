<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\Ai\AppAiBridge;
use MeRezaRezaei\Teleframe\App\Ai\DefaultAppAiBridge;
use MeRezaRezaei\Teleframe\App\Ai\NoopAppAiBridge;
use MeRezaRezaei\Teleframe\App\Ai\NopAppAiBridge;
use MeRezaRezaei\Teleframe\App\Ai\NullAiBridge;
use MeRezaRezaei\Teleframe\App\Ai\NullAppAiBridge;
use PHPUnit\Framework\TestCase;

final class AppAiBridgeTest extends TestCase
{
    public function test_interface_and_noop_exist(): void
    {
        self::assertTrue(interface_exists(AppAiBridge::class));
        $bridge = new NullAppAiBridge;
        self::assertInstanceOf(AppAiBridge::class, $bridge);
        self::assertNull($bridge->decide(['x' => 1]));
        self::assertNull($bridge->generate('hi'));
        self::assertTrue($bridge->shouldHandle('t', 'u'));
    }

    public function test_all_noop_aliases_construct(): void
    {
        foreach ([NullAppAiBridge::class, NoopAppAiBridge::class, DefaultAppAiBridge::class, NopAppAiBridge::class, NullAiBridge::class] as $class) {
            $instance = new $class;
            self::assertInstanceOf(AppAiBridge::class, $instance);
        }
    }
}
