<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use PHPUnit\Framework\TestCase;

final class ScaffoldTest extends TestCase
{
    public function testProviderLoads(): void
    {
        self::assertInstanceOf(\Illuminate\Support\ServiceProvider::class, new TeleframeServiceProvider(null));
    }
}
