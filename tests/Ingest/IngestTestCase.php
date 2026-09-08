<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\RunsMigrations;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * Base for ingest tests: testbench app (same providers as Schema\TestCase)
 * + sqlite :memory: + migrated P1 truth (dial + entity anchors).
 */
abstract class IngestTestCase extends TestCase
{
    use RunsMigrations;

    protected function getEnvironmentSetUp($app): void
    {
        $this->defineIngestDatabase($app);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateIngestSurface();
    }
}
