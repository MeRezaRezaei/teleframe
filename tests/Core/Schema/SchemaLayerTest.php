<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\Schema;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\Schema\SchemaLayer;
use PHPUnit\Framework\TestCase;

/**
 * Phase 1 Task 1: the declared Telegram schema layer resolves with a
 * 4-step fallback; the packaged schema-manifest.json is the truth.
 */
final class SchemaLayerTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/tl-schemalayer-' . bin2hex(random_bytes(6));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp . '/*') ?: [] as $f) {
            unlink($f);
        }
        rmdir($this->tmp);
    }

    public function test_manifest_layer_is_truth_when_present(): void
    {
        file_put_contents($this->tmp . '/schema-manifest.json', json_encode(['layer' => 231]));

        self::assertSame(231, SchemaLayer::layer($this->tmp . '/schema-manifest.json'));
        self::assertSame('schema-layer-231', SchemaLayer::cacheSalt($this->tmp . '/schema-manifest.json'));
    }

    public function test_missing_manifest_falls_back_to_packaged_artifact_or_wire(): void
    {
        $layer = SchemaLayer::layer(); // no explicit manifest → packaged methods-mtproto.json

        self::assertIsInt($layer);
        self::assertGreaterThanOrEqual(EncryptedConnection::LAYER, $layer);
    }

    public function test_cache_salt_tracks_layer_bump(): void
    {
        file_put_contents($this->tmp . '/schema-manifest.json', json_encode(['layer' => 229]));
        $salt229 = SchemaLayer::cacheSalt($this->tmp . '/schema-manifest.json');
        file_put_contents($this->tmp . '/schema-manifest.json', json_encode(['layer' => 230]));
        $salt230 = SchemaLayer::cacheSalt($this->tmp . '/schema-manifest.json');

        self::assertNotSame($salt229, $salt230);
        self::assertSame('schema-layer-230', $salt230);
    }
}