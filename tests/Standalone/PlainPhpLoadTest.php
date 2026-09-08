<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Standalone;

use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use PHPUnit\Framework\TestCase;

/**
 * Phase 0 Task 1: the ingest event + ingester must be loadable and
 * constructible with NO Laravel app booted. Before the fix this fatals:
 * UpdateStored imported Illuminate\Foundation\Events\Dispatchable and
 * Illuminate\Queue\SerializesModels — neither is a production dependency.
 */
final class PlainPhpLoadTest extends TestCase
{
    public function test_update_stored_loads_without_laravel(): void
    {
        self::assertTrue(class_exists(UpdateStored::class));
    }

    public function test_update_stored_has_no_framework_traits(): void
    {
        $uses = class_uses(UpdateStored::class);

        self::assertSame([], $uses);
    }

    public function test_ingestor_constructs_without_laravel(): void
    {
        $ingestor = new UpdateIngestor();

        self::assertInstanceOf(UpdateIngestor::class, $ingestor);
    }
}