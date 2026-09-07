<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use MeRezaRezaei\Teleframe\Laravel\Console\SchemaAuditCommand;
use PHPUnit\Framework\TestCase;

/**
 * Phase 1 Task 3: the unified upgrade command runs the FULL generation
 * chain in a fixed order, never migrates, and begins from the corrected
 * package root (regression guard for the stale `packages/schema` path).
 */
final class SchemaPipelineTest extends TestCase
{
    public function test_pipeline_steps_are_ordered_and_complete(): void
    {
        $steps = SchemaAuditCommand::pipelineSteps();

        $names = array_column($steps, 'name');
        self::assertSame([
            'method-schema', 'botapi-schema', 'method-builders',
            'skill-files', 'rpc-catalog', 'userscope-schema',
        ], $names);

        foreach ($steps as $step) {
            self::assertFileExists($step['bin'], "pipeline generator missing: {$step['bin']}");
            self::assertStringNotContainsString('migrate', $step['name'], 'schema pipeline must never run migrations (D4)');
        }
    }

    public function test_root_resolves_to_package_root_with_bins_and_sources(): void
    {
        $root = SchemaAuditCommand::root();

        self::assertDirectoryExists($root . '/bin', 'package root must contain bin/');
        self::assertFileExists($root . '/bin/generate-method-schema.php');
        self::assertDirectoryExists($root . '/src/Schema/schema/sources', 'committed TL sources must resolve');
        self::assertFileExists($root . '/src/Schema/schema/methods-mtproto.json');
        self::assertStringNotContainsString('packages/schema', $root, 'stale teleproto layout must not resurface');
    }
}