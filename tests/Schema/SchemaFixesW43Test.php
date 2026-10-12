<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Laravel\Console\SchemaAuditCommand;
use PHPUnit\Framework\TestCase;

/**
 * W-43: methods-botapi.json must carry the same top-level `layer` key as
 * methods-mtproto.json (the only artifact that knows the telegram layer).
 * The pipeline (SchemaAuditCommand::stampBotapiLayer) stamps it right after
 * `api` in the botapi envelope; re-running is a byte-stable no-op.
 */
final class SchemaFixesW43Test extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const SCHEMA_DIR = self::PACKAGE_ROOT.'/src/Schema/schema';

    public function test_committed_botapi_artifact_is_valid_json_with_layer(): void
    {
        $raw = (string) file_get_contents(self::SCHEMA_DIR.'/methods-botapi.json');
        $artifact = json_decode($raw, true);
        self::assertIsArray($artifact, 'methods-botapi.json must decode as a JSON object');
        self::assertArrayHasKey('layer', $artifact, 'W-43: top-level layer key missing');
        self::assertIsInt($artifact['layer']);
        self::assertGreaterThan(0, $artifact['layer']);

        $mtproto = json_decode((string) file_get_contents(self::SCHEMA_DIR.'/methods-mtproto.json'), true);
        self::assertIsArray($mtproto);
        self::assertSame($mtproto['layer'] ?? null, $artifact['layer'], 'botapi layer must match mtproto layer');
    }

    public function test_layer_sits_right_after_api_in_botapi_envelope(): void
    {
        $artifact = json_decode((string) file_get_contents(self::SCHEMA_DIR.'/methods-botapi.json'), true);
        $keys = array_keys($artifact);
        self::assertSame('_generated', $keys[0], 'botapi envelope keeps its provenance stamp first');
        $apiIdx = array_search('api', $keys, true);
        self::assertIsInt($apiIdx, 'botapi envelope must carry api');
        self::assertSame('layer', $keys[$apiIdx + 1] ?? null, 'W-43: layer must be stamped immediately after api');
    }

    public function test_stamp_botapi_layer_is_idempotent_and_recovers_component_order(): void
    {
        $dir = sys_get_temp_dir().'/w43-'.uniqid();
        mkdir($dir, 0777, true);

        $botapi = json_decode((string) file_get_contents(self::SCHEMA_DIR.'/methods-botapi.json'), true);
        $mtproto = json_decode((string) file_get_contents(self::SCHEMA_DIR.'/methods-mtproto.json'), true);
        file_put_contents($dir.'/methods-mtproto.json', json_encode($mtproto, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        // Simulate the pre-fix artifact: envelope without the layer key.
        unset($botapi['layer']);
        file_put_contents($dir.'/methods-botapi.json', json_encode($botapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        self::assertTrue(SchemaAuditCommand::stampBotapiLayer($dir), 'first stamp writes the layer');
        $stamped = json_decode((string) file_get_contents($dir.'/methods-botapi.json'), true);
        self::assertSame($mtproto['layer'] ?? null, $stamped['layer'] ?? null);
        $keys = array_keys($stamped);
        $apiIdx = array_search('api', $keys, true);
        self::assertIsInt($apiIdx);
        self::assertSame('layer', $keys[$apiIdx + 1] ?? null, 'layer sits right after api');

        self::assertFalse(SchemaAuditCommand::stampBotapiLayer($dir), 're-stamp with matching layer is a no-op');
        self::assertSame(
            file_get_contents($dir.'/methods-botapi.json'),
            file_get_contents(self::SCHEMA_DIR.'/methods-botapi.json'),
            'stamped artifact must be byte-identical to the committed one',
        );

        unlink($dir.'/methods-botapi.json');
        unlink($dir.'/methods-mtproto.json');
        rmdir($dir);
    }
}
