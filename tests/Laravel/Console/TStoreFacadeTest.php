<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Console;

use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\Facades\TStore;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * The TStore facade (plan W7): resolves the same StoreService the
 * `teleframe:tstore` CLI and `Teleframe::store()` use — the six-seam
 * container binding under TstoreCommand::SERVICE_KEY.
 */
final class TStoreFacadeTest extends TestCase
{
    public function test_facade_resolves_the_bound_store_service(): void
    {
        $service = TStore::getFacadeRoot();

        self::assertInstanceOf(StoreService::class, $service);
        self::assertSame($service, $this->app->make(TstoreCommand::SERVICE_KEY));
    }
}
