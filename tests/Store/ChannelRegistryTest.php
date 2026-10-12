<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\ChannelRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ChannelRegistry (verbatim S5): the TFREGISTRY1 wire format that lets a
 * fresh login rediscover managed channels by walking dialogs. Covers
 * title classification (store/data/backup prefixes), title construction,
 * render → parse roundtrips, foreign-message nulls, and strict-throw
 * paths on header/payload disagreement.
 */
final class ChannelRegistryTest extends TestCase
{
    public function test_classify_title_for_all_three_kinds(): void
    {
        $this->assertSame(
            ['kind' => 'store', 'bucket' => 'tg-reports'],
            ChannelRegistry::classifyTitle('teleframe-store:tg-reports'),
        );
        $this->assertSame(
            ['kind' => 'data', 'bucket' => 'media-pool'],
            ChannelRegistry::classifyTitle('teleframe-data:media-pool'),
        );
        $this->assertSame(
            ['kind' => 'backup', 'bucket' => 'vault-1'],
            ChannelRegistry::classifyTitle('teleframe-backup:vault-1'),
        );
    }

    public function test_classify_title_without_bucket_is_null(): void
    {
        $this->assertNull(ChannelRegistry::classifyTitle('teleframe-store:'));
        $this->assertNull(ChannelRegistry::classifyTitle('teleframe-store'));
    }

    public function test_classify_foreign_title_is_null(): void
    {
        $this->assertNull(ChannelRegistry::classifyTitle('some random channel'));
        $this->assertNull(ChannelRegistry::classifyTitle(''));
        $this->assertNull(ChannelRegistry::classifyTitle('teleframe-store'));
    }

    public function test_title_for_rebuilds_canonical_titles(): void
    {
        $this->assertSame('teleframe-store:tg-reports', ChannelRegistry::titleFor('store', 'tg-reports'));
        $this->assertSame('teleframe-data:media-pool', ChannelRegistry::titleFor('data', 'media-pool'));
        $this->assertSame('teleframe-backup:vault-1', ChannelRegistry::titleFor('backup', 'vault-1'));
    }

    public function test_title_for_unknown_kind_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ChannelRegistry::titleFor('other', 'bucket');
    }

    public function test_render_parse_roundtrips(): void
    {
        $text = ChannelRegistry::render('store', 'tg-reports');

        $parsed = ChannelRegistry::parse($text);
        $this->assertNotNull($parsed);
        $this->assertSame('store', $parsed['kind']);
        $this->assertSame('tg-reports', $parsed['bucket']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $parsed['created']);
    }

    public function test_render_includes_version_payload(): void
    {
        $text = ChannelRegistry::render('data', 'media-pool');

        $this->assertStringStartsWith('TFREGISTRY1: data/media-pool'."\n", $text);
        $this->assertStringContainsString('"version": 1', $text);
    }

    public function test_render_merges_extra_keys_without_overwriting_core(): void
    {
        $text = ChannelRegistry::render('backup', 'vault-1', ['note' => 'legacy', 'bucket' => 'spoofed']);

        $parsed = ChannelRegistry::parse($text);
        $this->assertNotNull($parsed);
        $this->assertSame('vault-1', $parsed['bucket']); // core key wins
    }

    public function test_foreign_message_is_null(): void
    {
        $this->assertNull(ChannelRegistry::parse('just a chat message'));
        $this->assertNull(ChannelRegistry::parse(''));
        $this->assertNull(ChannelRegistry::parse('TFREGISTRY1:'));
    }

    public function test_registry_message_with_unknown_kind_is_null(): void
    {
        $this->assertNull(ChannelRegistry::parse('TFREGISTRY1: other/somewhere'));
    }

    public function test_registry_message_without_fence_throws(): void
    {
        $this->expectException(RuntimeException::class);
        ChannelRegistry::parse('TFREGISTRY1: store/tg-reports'."\n".'no fence');
    }

    public function test_header_payload_mismatch_throws(): void
    {
        $json = json_encode(
            ['version' => 1, 'kind' => 'store', 'bucket' => 'different', 'created' => 'now'],
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
        );
        $text = 'TFREGISTRY1: store/tg-reports'."\n".'```json'."\n".$json."\n".'```';

        $this->expectException(RuntimeException::class);
        ChannelRegistry::parse($text);
    }

    public function test_bad_payload_throws(): void
    {
        $text = "TFREGISTRY1: store/tg-reports\n```json\n{\"kind\":42}\n```";

        $this->expectException(RuntimeException::class);
        ChannelRegistry::parse($text);
    }
}
