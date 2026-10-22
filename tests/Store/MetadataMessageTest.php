<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\MetadataMessage;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * MetadataMessage (verbatim S4): the TFSTORE1 wire format — marker line
 * + name, then a fenced pretty JSON ObjectManifest. Covers render →
 * parse roundtrips, foreign-message nulls, the name extraction seam, and
 * the strict-throw paths when a TFSTORE1 message is corrupt (data loss,
 * not a foreign message).
 */
final class MetadataMessageTest extends TestCase
{
    private const SHA = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private function manifest(): ObjectManifest
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

    public function test_render_has_marker_fence_and_pretty_json(): void
    {
        $text = MetadataMessage::render($this->manifest());

        $this->assertStringStartsWith('TFSTORE1: reports/q3.zip'."\n", $text);
        $this->assertStringContainsString('```json'."\n", $text);
        $this->assertStringEndsWith("\n".'```', $text);
        $this->assertStringContainsString('"version": 2', $text);
        $this->assertStringContainsString("\n    \"", $text); // pretty-printed (indentation)
    }

    public function test_parse_roundtrips_render(): void
    {
        $manifest = $this->manifest();
        $parsed = MetadataMessage::parse(MetadataMessage::render($manifest));

        $this->assertNotNull($parsed);
        $this->assertSame('reports/q3.zip', $parsed->name());
        $this->assertSame($manifest->toArray(), $parsed->toArray());
    }

    public function test_name_from_text_exposes_just_the_name(): void
    {
        $text = MetadataMessage::render($this->manifest());

        $this->assertSame('reports/q3.zip', MetadataMessage::nameFromText($text));
    }

    public function test_foreign_message_is_null_not_an_error(): void
    {
        $this->assertNull(MetadataMessage::nameFromText('just a chat message'));
        $this->assertNull(MetadataMessage::parse('just a chat message'));
        $this->assertNull(MetadataMessage::parse(''));
    }

    public function test_marker_without_name_is_null(): void
    {
        $this->assertNull(MetadataMessage::nameFromText('TFSTORE1:'));
        $this->assertNull(MetadataMessage::nameFromText('TFSTORE1: '));
    }

    public function test_tfstore_message_without_fence_throws(): void
    {
        $this->expectException(RuntimeException::class);
        MetadataMessage::parse('TFSTORE1: reports/q3.zip'."\n".'no fence here');
    }

    public function test_tfstore_message_with_corrupt_json_throws(): void
    {
        $text = "TFSTORE1: reports/q3.zip\n```json\n{not json\n```";

        $this->expectException(RuntimeException::class);
        MetadataMessage::parse($text);
    }

    public function test_name_mismatch_between_header_and_manifest_throws(): void
    {
        $manifest = $this->manifest()->toArray();
        $json = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $text = 'TFSTORE1: different'."\n".'```json'."\n".$json."\n".'```';

        $this->expectException(RuntimeException::class);
        MetadataMessage::parse($text);
    }

    public function test_render_with_slashes_is_unslashed(): void
    {
        $text = MetadataMessage::render($this->manifest());

        // JSON_UNESCAPED_SLASHES: name must appear literally, not escaped.
        $this->assertStringContainsString('reports/q3.zip', $text);
        $this->assertStringNotContainsString('\\/', $text);
    }
}
