<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleproto\Bot\Tests;

use MeRezaRezaei\Teleproto\Methods\Generated\Bots;
use MeRezaRezaei\Teleproto\Services\BotAccountScope;
use MeRezaRezaei\Teleproto\Services\BotClient;
use PHPUnit\Framework\TestCase;

final class BotPackageTest extends TestCase
{
    public function testBotSurfaceLoadsFromThisPackage(): void
    {
        $ref = new \ReflectionClass(BotClient::class);
        self::assertStringContainsString(
            'packages/bot/src',
            str_replace('\\', '/', $ref->getFileName() ?: ''),
            'BotClient must load from the bot package (PSR-4 subset mapping works)'
        );
        self::assertTrue(class_exists(Bots::class));
        self::assertTrue(class_exists(BotAccountScope::class));
    }

    public function testBotClientInstantiatesWithoutLaravelApp(): void
    {
        $client = new BotClient('123:abc', []);
        self::assertInstanceOf(BotClient::class, $client);
    }
}