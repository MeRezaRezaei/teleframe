<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE MARKDOWN SIDE OF `EntityParser`: the two things only this path owns.
 *
 * Both holes were found by Infection on `src/Core/Entities/EntityParser.php`
 * and both were confirmed against the source, not guessed at:
 *
 *   1. `document_id` on the `![alt](tg://emoji?id=N)` path. The id is sliced
 *      out of the URL as a STRING (`substr($url, 14)`) and cast with `(int)`
 *      before it goes on the wire, where
 *      `messageEntityCustomEmoji.document_id` is a `long`. Nothing asserted
 *      that value at all, so the cast could be deleted and every test stayed
 *      green. The emoji corpus in EntityIndependentOraclePropertyTest parses
 *      this form constantly — but it only asserts `rtrim`-stability of the
 *      entities it gets back, never their fields.
 *   2. The running offset advance at the fenced-code call site. This site owns
 *      the half of the rtrim rule that is NOT the length: the whitespace stays
 *      in the plain text, so the NEXT entity's offset must include it. No test
 *      ever put a second entity after a pre block, which is the only way to
 *      observe an offset at all.
 */
final class EntityParserMarkdownOffsetTest extends TestCase
{
    /**
     * `document_id` is an int64 on the wire, and the URL hands it over as a
     * string. A leading zero is what makes the difference observable at all:
     * `(int) '007'` is `7`, and an uncast value would stay `'007'` — which no
     * loose comparison against `7` would notice, so the cast is checked with
     * `assertSame` on the TYPE as well as the value.
     */
    public function test_markdown_custom_emoji_document_id_is_an_int(): void
    {
        $short = EntityParser::markdownToEntities('![X](tg://emoji?id=007)');

        self::assertSame('X', $short['text'], 'the alt text is the text, the markup is not');
        self::assertCount(1, $short['entities'], 'one custom-emoji span, one entity');
        self::assertSame('messageEntityCustomEmoji', $short['entities'][0]['_'], 'the tg:// emoji form is a custom-emoji entity');
        self::assertSame(
            7,
            $short['entities'][0]['document_id'],
            'document_id is an int: the id is sliced out of the URL as the string "007" and cast on the way out. '
            .'Without the cast this is the string "007" — a string where the wire type is long.',
        );

        // A real emoji document id, past the 32-bit range.
        $long = EntityParser::markdownToEntities('![X](tg://emoji?id=5368324170671202286)');

        self::assertSame(
            5368324170671202286,
            $long['entities'][0]['document_id'],
            'document_id survives as an int64 value, not a string',
        );
    }

    /**
     * THE OFFSET-ADVANCE HALF OF THE rtrim RULE, on the one call site that
     * owns it.
     *
     * core.telegram.org/api/entities: "the _length_ of an entity must not
     * include the length of trailing newlines or whitespaces, `rtrim` entities
     * before computing their length: however, the next _offset_ must include
     * the length of newlines or whitespaces that precede it."
     *
     * Hand-counted on the expected text `"head\n\ncode\n  \n\nb"`:
     *
     *   h 0  e 1  a 2  d 3  \n 4  \n 5  c 6  o 7  d 8  e 9
     *   \n 10  ␠ 11  ␠ 12  \n 13  \n 14  b 15
     *
     * The fenced block's content is units 5-13, NINE units including the
     * `"  \n"` tail. So THREE things are pinned by one document, and each of
     * them is a different bug:
     *
     *   - the pre entity's LENGTH is 5, not 9: rtrim happened. Reporting 9
     *     would render `"code\n  "` inside the code block, which is the defect
     *     this repo shipped and fixed.
     *   - the next entity's OFFSET is 15, not 11: the advance covered the
     *     UNTRIMMED nine units plus the separating newline. Trimming the
     *     advance satisfies the rule and shifts every later entity in the
     *     message by four units, which Telegram answers as a rendering bug.
     *   - the next entity's OFFSET is 15, not 10: the advance was an
     *     ACCUMULATION onto the running offset (5 + 9 + 1). Replacing `+=`
     *     with `=` throws the running total away and restarts the count at 9.
     */
    public function test_fenced_block_advances_the_offset_over_its_untrimmed_content(): void
    {
        $markdown = "head\n```\ncode\n  \n```\n*b*";
        $result = EntityParser::markdownToEntities($markdown);

        self::assertSame(
            "head\n\ncode\n  \n\nb",
            $result['text'],
            'the fence markers are consumed and the block body keeps its whitespace — the whitespace is what makes the '
            .'offset advance observable at all',
        );
        self::assertCount(2, $result['entities'], 'the fence and the bold span are two entities');

        self::assertSame('messageEntityPre', $result['entities'][0]['_'], 'the fence is a pre entity');
        self::assertSame(5, $result['entities'][0]['offset'], 'the pre entity opens at 5, just past "head\\n"');
        self::assertSame(
            5,
            $result['entities'][0]['length'],
            'the pre entity MEASURES rtrimmed: units 5-9, "code". Its untrimmed content is 9 units wide, so an untrimmed '
            .'length would report 9.',
        );

        self::assertSame('messageEntityBold', $result['entities'][1]['_'], 'the span after the fence is bold');
        self::assertSame(
            15,
            $result['entities'][1]['offset'],
            'the entity after the fence opens at 15: the running offset advanced over the UNTRIMMED block content '
            .'(5 + 9) and the separating newline (+1). Advancing by the trimmed 5 instead lands on 11, and assigning '
            .'rather than accumulating restarts the count and lands on 10.',
        );
        self::assertSame(1, $result['entities'][1]['length'], 'the bold span covers its one character');
    }
}
