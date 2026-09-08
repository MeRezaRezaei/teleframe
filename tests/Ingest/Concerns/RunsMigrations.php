<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns;

use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * Boots the ingest database surface for logic tests (plan Task 1):
 * sqlite :memory: connection + the shipped curated dial (provider-registered
 * migrations/) migrated together with the off-dial root-namespace entity
 * anchors the ingest surface writes to (User first; grows with the walker).
 */
trait RunsMigrations
{
    /**
     * SQLite :memory: for logic tests — plan Tech Stack: PG-only paths
     * (deferrable FKs, uuid native) are exercised by the CI/live gate.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineIngestDatabase($app): void
    {
        $app['config']->set('database.default', 'ingest');
        $app['config']->set('database.connections.ingest', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Migrate the shipped curated dial (112 migrations) plus
     * UpdateIngestor's entity anchors (off-dial root-namespace tables) in
     * one pass. `--path` replaces registered package paths, so the dial
     * dir is part of the path list.
     */
    protected function migrateIngestSurface(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ]);
    }
}
