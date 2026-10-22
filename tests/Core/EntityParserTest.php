<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;

class EntityParserTest extends TestCase
{
    public function testBasicHtmlFormattingToEntities(): void
    {
        $html = '<b>Hello</b> <i>World</i> <code>code</code>';
        $result = EntityParser::htmlToEntities($html);

        $this->assertEquals('Hello World code', $result['text'], 'the extracted text must be Hello World code with no tag residue');
        $this->assertCount(3, $result['entities'], 'the three inline tags must produce exactly THREE entities');

        $this->assertEntity($result, 0, 'messageEntityBold', 0, 5);
        $this->assertEntity($result, 1, 'messageEntityItalic', 6, 5);
        $this->assertEntity($result, 2, 'messageEntityCode', 12, 4);
    }

    public function testEmojiUtf16SurrogatePairOffsets(): void
    {
        // 4-byte emoji 😀 is 2 UTF-16 code units
        $html = '😀 <b>Bold after emoji</b>';
        $result = EntityParser::htmlToEntities($html);

        $this->assertEquals('😀 Bold after emoji', $result['text'], 'the emoji must survive the DOM walk');
        $this->assertCount(1, $result['entities'], 'one <b> tag means exactly ONE entity');

        // Offset must be 3 (2 code units for emoji + 1 space)
        $this->assertEntity($result, 0, 'messageEntityBold', 3, 16);
    }

    public function testTextUrlWithAttributes(): void
    {
        $html = 'Check <a href="https://telegram.org">Telegram</a> now!';
        $result = EntityParser::htmlToEntities($html);

        $this->assertEquals('Check Telegram now!', $result['text'], 'the anchor text is kept, the tag is not');
        $this->assertCount(1, $result['entities'], 'one <a> tag means exactly ONE entity');

        $this->assertEntity($result, 0, 'messageEntityTextUrl', 6, 8, url: 'https://telegram.org');
    }

    public function testMarkdownToEntitiesFormatting(): void
    {
        $markdown = '*Hello* _World_ `code` ~strike~ ||spoiler|| [Telegram](https://telegram.org)';
        $result = EntityParser::markdownToEntities($markdown);

        $this->assertEquals('Hello World code strike spoiler Telegram', $result['text'], 'every markdown delimiter must be consumed, none left in the text');
        $this->assertCount(6, $result['entities'], 'the six delimiter forms must produce exactly SIX entities');

        $this->assertEntity($result, 0, 'messageEntityBold', 0, 5);
        $this->assertEntity($result, 1, 'messageEntityItalic', 6, 5);
        $this->assertEntity($result, 2, 'messageEntityCode', 12, 4);
        $this->assertEntity($result, 3, 'messageEntityStrike', 17, 6);
        $this->assertEntity($result, 4, 'messageEntitySpoiler', 24, 7);
        $this->assertEntity($result, 5, 'messageEntityTextUrl', 32, 8, url: 'https://telegram.org');
    }

    public function testMarkdownEscapedCharactersAndPreBlocks(): void
    {
        $markdown = "Notice: \*not bold\* and [link](https://tg.org)\n```php\n\$var = 1;\n```";
        $result = EntityParser::markdownToEntities($markdown);

        $this->assertEquals("Notice: *not bold* and link\n\$var = 1;\n", $result['text'], 'escaped asterisks stay literal and the fenced body keeps its newlines');
        $this->assertCount(2, $result['entities'], 'the escaped asterisks must NOT become an entity, so only the link and the fence do');

        // Hand-counted against the text the parser must return,
        // "Notice: *not bold* and link\n$var = 1;\n": the link opens at 23
        // ("Notice: " 8 + "*not bold*" 10 + " and " 5) and spans 4 units; the
        // fence opens at 28, after the newline that separates them, and spans
        // the 9 units of "$var = 1;". The pre-span GEOMETRY itself is pinned
        // with an independent oracle in EntityIndependentOraclePropertyTest::
        // test_the_shipped_markdown_pre_block_fix_still_holds.
        $this->assertEntity($result, 0, 'messageEntityTextUrl', 23, 4, url: 'https://tg.org');

        $this->assertEntity($result, 1, 'messageEntityPre', 28, 9, language: 'php');
    }

    /**
     * ONE produced entity, by its position in the list: the three fields every
     * method in this file asserts, plus the URL or language when the entity
     * carries one.
     *
     * A Custom Assertion, not an extraction for its own sake — it NAMES the
     * span under test, which is the whole point: the old form asserted
     * `entities[1]['offset']` and `entities[1]['length']` with no message at
     * all, and a reader could not tell which of the three entities in a
     * six-entity document moved. Nothing is weakened — the same assertions
     * with the same comparison, each now carrying the span it is about.
     *
     * @param  array<string, mixed>  $result  the parser result under test
     */
    private function assertEntity(array $result, int $index, string $type, int $offset, int $length, ?string $url = null, ?string $language = null): void
    {
        $what = "entity {$index} of " . count($result['entities']) . " (the {$type} span)";

        $this->assertEquals($type, $result['entities'][$index]['_'], "{$what}: wrong constructor");
        $this->assertEquals($offset, $result['entities'][$index]['offset'], "{$what}: wrong offset");
        $this->assertEquals($length, $result['entities'][$index]['length'], "{$what}: wrong length");

        $this->assertSame($url, $result['entities'][$index]['url'] ?? null, "{$what}: wrong url");
        $this->assertSame($language, $result['entities'][$index]['language'] ?? null, "{$what}: wrong language");
    }
}
