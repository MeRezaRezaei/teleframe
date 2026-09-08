<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Message;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Message\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * Q13 conventional-input adapter: plain text + declared `{offset,length,type}`
 * spans → the engine's `messageEntity*` array shape, zero-regex
 * (`mb_*`/`str_*`/`substr` only). Typecheck of the shape mirrors the
 * registry-side validator (`MessagePlanValidator`).
 */
final class EntityParserTest extends TestCase
{
    public function test_alias_maps_to_engine_constructor(): void
    {
        $result = EntityParser::spans('Hello world', [
            ['type' => 'bold', 'offset' => 0, 'length' => 5],
        ]);

        self::assertSame('Hello world', $result['text']);
        self::assertSame('messageEntityBold', $result['entities'][0]['_']);
        self::assertSame(0, $result['entities'][0]['offset']);
        self::assertSame(5, $result['entities'][0]['length']);
    }

    public function test_engine_native_type_passes_through(): void
    {
        $result = EntityParser::spans('abc', [
            ['type' => 'messageEntityStrike', 'offset' => 0, 'length' => 3],
        ]);

        self::assertSame('messageEntityStrike', $result['entities'][0]['_']);
    }

    public function test_text_link_carries_url(): void
    {
        $result = EntityParser::spans('Buy now', [
            ['type' => 'text_link', 'offset' => 4, 'length' => 3, 'url' => 'https://example.com/buy'],
        ]);

        self::assertSame('messageEntityTextUrl', $result['entities'][0]['_']);
        self::assertSame('https://example.com/buy', $result['entities'][0]['url']);
    }

    public function test_custom_emoji_carries_document_id(): void
    {
        $result = EntityParser::spans('Hi', [
            ['type' => 'custom_emoji', 'offset' => 0, 'length' => 2, 'document_id' => 42],
        ]);

        self::assertSame('messageEntityCustomEmoji', $result['entities'][0]['_']);
        self::assertSame(42, $result['entities'][0]['document_id']);
    }

    public function test_mention_alias(): void
    {
        $result = EntityParser::spans('@ada', [
            ['type' => 'mention', 'offset' => 0, 'length' => 4],
        ]);

        self::assertSame('messageEntityMention', $result['entities'][0]['_']);
    }

    public function test_entities_are_sorted_by_offset(): void
    {
        $result = EntityParser::spans('abc def', [
            ['type' => 'italic', 'offset' => 4, 'length' => 3],
            ['type' => 'bold', 'offset' => 0, 'length' => 3],
        ]);

        self::assertSame(['messageEntityBold', 'messageEntityItalic'], [
            $result['entities'][0]['_'],
            $result['entities'][1]['_'],
        ]);
    }

    public function test_utf16_bounds_are_enforced(): void
    {
        // 😀 is 2 UTF-16 code units; the span must fit within the text.
        $result = EntityParser::spans('😀 hi', [
            ['type' => 'bold', 'offset' => 3, 'length' => 2],
        ]);

        self::assertSame('messageEntityBold', $result['entities'][0]['_']);
    }

    public function test_span_exceeding_text_length_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EntityParser::spans('abc', [
            ['type' => 'bold', 'offset' => 2, 'length' => 5],
        ]);
    }

    public function test_unknown_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EntityParser::spans('abc', [
            ['type' => 'blinky', 'offset' => 0, 'length' => 3],
        ]);
    }

    public function test_unknown_extra_key_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EntityParser::spans('abc', [
            ['type' => 'bold', 'offset' => 0, 'length' => 3, 'wobble' => true],
        ]);
    }

    public function test_negative_offset_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EntityParser::spans('abc', [
            ['type' => 'bold', 'offset' => -1, 'length' => 3],
        ]);
    }

    public function test_zero_length_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EntityParser::spans('abc', [
            ['type' => 'bold', 'offset' => 0, 'length' => 0],
        ]);
    }

    public function test_overlapping_spans_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EntityParser::spans('abcdef', [
            ['type' => 'bold', 'offset' => 0, 'length' => 4],
            ['type' => 'italic', 'offset' => 2, 'length' => 3],
        ]);
    }

    public function test_empty_spans_yield_no_entities(): void
    {
        $result = EntityParser::spans('plain', []);

        self::assertSame('plain', $result['text']);
        self::assertSame([], $result['entities']);
    }

    public function test_html_delegates_to_the_existing_core_parser(): void
    {
        $result = EntityParser::html('<b>Hello</b>');

        self::assertSame('Hello', $result['text']);
        self::assertSame('messageEntityBold', $result['entities'][0]['_']);
    }

    public function test_markdown_delegates_to_the_existing_core_parser(): void
    {
        $result = EntityParser::markdown('*Hello*');

        self::assertSame('Hello', $result['text']);
        self::assertSame('messageEntityBold', $result['entities'][0]['_']);
    }
}