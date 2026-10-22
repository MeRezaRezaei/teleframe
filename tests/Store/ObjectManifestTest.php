<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ObjectManifest (v2 descriptor): name/size/sha256/mode/mime/salt/
 * created/chunks truth carried by TFSTORE1 metadata messages and the
 * tf_store_objects row. Covers construction validation, JSON roundtrip,
 * mode/salt coupling, chunk validation, and chunkDocName wire naming
 * (sealed → wire_sha256, raw → plaintext sha256).
 */
final class ObjectManifestTest extends TestCase
{
    private const SHA = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const SHA_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private const WIRE = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

    private function rawManifest(): ObjectManifest
    {
        return ObjectManifest::new(
            'reports/q3.zip',
            12,
            self::SHA,
            ObjectManifest::MODE_RAW,
            'application/zip',
            null,
            [['n' => 0, 'size' => 12, 'sha256' => self::SHA]],
        );
    }

    public function test_accessors_expose_all_fields(): void
    {
        $m = $this->rawManifest();

        $this->assertSame('reports/q3.zip', $m->name());
        $this->assertSame(12, $m->size());
        $this->assertSame(self::SHA, $m->sha256());
        $this->assertSame(ObjectManifest::MODE_RAW, $m->mode());
        $this->assertSame('application/zip', $m->mime());
        $this->assertNull($m->saltHex());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $m->created());
        $this->assertCount(1, $m->chunks());
        $this->assertSame(1, $m->chunkCount());
    }

    public function test_roundtrip_through_to_array_and_from_array(): void
    {
        $m = $this->rawManifest();
        $parsed = ObjectManifest::fromArray($m->toArray());

        $this->assertSame($m->toArray(), $parsed->toArray());
        $this->assertSame(ObjectManifest::VERSION, $parsed->toArray()['version']);
    }

    public function test_sealed_requires_salt_and_raw_forbids_it(): void
    {
        $sealed = ObjectManifest::new(
            'secret.bin',
            4,
            self::SHA_B,
            ObjectManifest::MODE_SEALED,
            'application/octet-stream',
            str_repeat('f', 64),
            [['n' => 0, 'size' => 4, 'sha256' => self::SHA_B, 'wire_sha256' => self::WIRE]],
        );
        $this->assertSame(str_repeat('f', 64), $sealed->saltHex());

        $this->expectException(InvalidArgumentException::class);
        ObjectManifest::new(
            'x',
            4,
            self::SHA_B,
            ObjectManifest::MODE_SEALED,
            'application/octet-stream',
            null,
            [['n' => 0, 'size' => 4, 'sha256' => self::SHA_B, 'wire_sha256' => self::WIRE]],
        );
    }

    public function test_raw_with_salt_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ObjectManifest::new(
            'x',
            4,
            self::SHA_B,
            ObjectManifest::MODE_RAW,
            'application/octet-stream',
            str_repeat('f', 64),
            [['n' => 0, 'size' => 4, 'sha256' => self::SHA_B]],
        );
    }

    public function test_bad_mode_and_empty_name_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ObjectManifest::new('', 1, self::SHA, 'encrypted', 'x', null, []);
    }

    public function test_from_array_rejects_unsupported_version(): void
    {
        $data = $this->rawManifest()->toArray();
        $data['version'] = 1;

        $this->expectException(RuntimeException::class);
        ObjectManifest::fromArray($data);
    }

    public function test_from_array_rejects_bad_sha_and_chunk_shapes(): void
    {
        $this->expectException(RuntimeException::class);
        ObjectManifest::fromArray([
            'version' => 2,
            'name' => 'x',
            'size' => 1,
            'sha256' => 'not-hex',
            'mode' => 'raw',
            'mime' => 'x',
            'created' => 'now',
            'chunks' => [['n' => 0, 'size' => 1, 'sha256' => 'zz']],
        ]);
    }

    public function test_from_array_rejects_sealed_without_salt_and_raw_with_salt(): void
    {
        $sealed = [
            'version' => 2,
            'name' => 'x',
            'size' => 1,
            'sha256' => self::SHA,
            'mode' => 'sealed',
            'mime' => 'x',
            'created' => 'now',
            'chunks' => [['n' => 0, 'size' => 1, 'sha256' => self::SHA, 'wire_sha256' => self::WIRE]],
        ];
        $this->expectException(RuntimeException::class);
        ObjectManifest::fromArray($sealed);
    }

    public function test_chunk_doc_name_uses_plaintext_hash_for_raw(): void
    {
        $m = $this->rawManifest();

        $this->assertSame(self::SHA, $m->chunkDocName(0));
    }

    public function test_sealed_chunk_doc_name_uses_wire_hash(): void
    {
        $m = ObjectManifest::new(
            'secret.bin',
            4,
            self::SHA_B,
            ObjectManifest::MODE_SEALED,
            'application/octet-stream',
            str_repeat('f', 64),
            [['n' => 0, 'size' => 4, 'sha256' => self::SHA_B, 'wire_sha256' => self::WIRE]],
        );

        $this->assertSame(self::WIRE, $m->chunkDocName(0));
    }

    public function test_chunk_doc_name_for_missing_chunk_throws(): void
    {
        $m = $this->rawManifest();

        $this->expectException(RuntimeException::class);
        $m->chunkDocName(7);
    }

    public function test_sealed_survives_roundtrip_including_side_keys(): void
    {
        $m = ObjectManifest::new(
            'secret.bin',
            4,
            self::SHA_B,
            ObjectManifest::MODE_SEALED,
            'application/octet-stream',
            str_repeat('f', 64),
            [['n' => 0, 'size' => 4, 'sha256' => self::SHA_B, 'wire_sha256' => self::WIRE]],
        );

        $parsed = ObjectManifest::fromArray($m->toArray());

        $this->assertSame(self::WIRE, $parsed->chunkDocName(0));
        $this->assertSame(str_repeat('f', 64), $parsed->saltHex());
    }
}
