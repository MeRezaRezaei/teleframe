<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE INLINE-CODE SCAN WITH SEVERAL BACKTICKS, AND THE EMOJI SCAN'S SECOND
 * DELIMITERS.
 *
 * ## WHY THIS FILE EXISTS
 *
 * Two loop-`break` survivors remain unobserved: `:177` in the inline-code scan, and
 * `:291` in the two-character stack. A `break` is only observable when removing it
 * lets the scan continue to a **different** closer, so every case here supplies more
 * than one candidate.
 *
 * The emoji scan's `:193`–`:211` bounds have the same requirement, which
 * `EntityEmojiSliceBoundsTest` addressed for `]` and `)`. What was still missing is
 * the interaction between the two scans — a bracket found by one scan and a paren
 * found by the other, with content that could belong to either.
 *
 * ## WHAT IS ASSERTED
 *
 * `text` and each entity's `_`, `offset`, `length`, `url`, `document_id`. No scan
 * index.
 */
final class EntityInlineCodeMultiTickTest extends TestCase
{
    /**
     * @param  list<array<string, int|string>>  $expected
     */
    private static function assertParses(string $markdown, string $expectedText, array $expected): void
    {
        $result = EntityParser::markdownToEntities($markdown);

        self::assertSame($expectedText, $result['text']);

        $actual = array_map(
            static function (array $e): array {
                $out = ['_' => $e['_'], 'offset' => $e['offset'], 'length' => $e['length']];
                return $out + array_intersect_key($e, array_flip(['url', 'document_id']));
            },
            $result['entities'],
        );

        self::assertSame($expected, $actual);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // :177 — THE INLINE-CODE `break`, WITH MORE THAN ONE BACKTICK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Two backticks inside the span — the closer must be the FIRST one.
     *
     * `` `x``y` ``: the scan starts at 1, closes at the backtick at 2, so the code
     * content is `x` and the remaining ``` ``y` ``` opens a second span closed by the
     * backtick at 5. The entity offsets are 0 and 1.
     *
     * Without the `break` the scan would run to the LAST backtick, making the first
     * span cover `x``y` — length 4 at offset 0 — and emitting one entity instead of
     * two. The offsets are what tell the two readings apart.
     */
    public function test_the_inline_code_span_closes_on_the_first_backtick(): void
    {
        self::assertParses(
            '`x``y`',
            'xy',
            [
                ['_' => 'messageEntityCode', 'offset' => 0, 'length' => 1],
                ['_' => 'messageEntityCode', 'offset' => 1, 'length' => 1],
            ],
        );
    }

    /**
     * Three backticks in a row inside the message.
     *
     * `` `a``b``c` `` gives three spans of one character each. Removing the `break`
     * makes each span swallow everything to the last backtick.
     */
    public function test_three_inline_code_spans_each_close_on_their_own_backtick(): void
    {
        self::assertParses(
            '`a``b``c`',
            'abc',
            [
                ['_' => 'messageEntityCode', 'offset' => 0, 'length' => 1],
                ['_' => 'messageEntityCode', 'offset' => 1, 'length' => 1],
                ['_' => 'messageEntityCode', 'offset' => 2, 'length' => 1],
            ],
        );
    }

    /** The same with text between the spans, so the offsets are spread out. */
    public function test_inline_code_spans_separated_by_text_each_close_on_their_own_backtick(): void
    {
        self::assertParses(
            'a `b` c`d`',
            'a b cd',
            [
                ['_' => 'messageEntityCode', 'offset' => 2, 'length' => 1],
                ['_' => 'messageEntityCode', 'offset' => 5, 'length' => 1],
            ],
        );
    }

    /**
     * A span whose content is long, with a backtick pair inside it.
     *
     * `` `abc`def`ghi` `` — the closer is the backtick after `abc`, so the code
     * content is `abc`. `def` is ordinary text, then `` `ghi` `` is a second span.
     * Two entities, at 0 and 6 — the second starts after `abc` and the space.
     *
     * I wrote three entities at offsets 0, 1 and 2, having copied the shape from
     * `` `a``b``c` `` where the spans ARE adjacent. Here the backticks are
     * single-character pairs separated by whole words, so the second span starts at
     * 6, not 1. The test caught the difference.
     *
     * **The inline-code scan has NO escape branch** — `:174`–`:179` is a bare
     * `` ` `` comparison. I first wrote this case with a backslash, assuming the
     * scanner honours escapes inside code spans; it does not, so the backslash was
     * carried into the text and the test caught it. Backslashes are literal inside
     * an inline span, which is consistent with the URL-side escape question from
     * 46.14 and 46.26 being about LINK text, not code.
     */
    public function test_a_long_span_closes_at_its_first_inner_backtick(): void
    {
        self::assertParses(
            '`abc`def`ghi`',
            'abcdefghi',
            [
                ['_' => 'messageEntityCode', 'offset' => 0, 'length' => 3],
                ['_' => 'messageEntityCode', 'offset' => 6, 'length' => 3],
            ],
        );
    }

    /**
     * A backslash inside an inline span is literal — the scan has no escape branch.
     *
     * `` `a\b` `` yields the content `a\b`. This is a pin of measured behaviour; like
     * the link-text escape question, whether Telegram agrees needs a live send.
     */
    public function test_a_backslash_inside_an_inline_span_is_literal(): void
    {
        self::assertParses('`a\\b`', 'a\\b', [['_' => 'messageEntityCode', 'offset' => 0, 'length' => 3]]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE EMOJI SCAN'S TWO SCANS INTERACTING
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A `]` in the URL is not the alt's closer.
     *
     * `![a](tg://emoji?id=1)b]` — the bracket scan closes at index 3, before the
     * URL even starts, so the trailing `]` is ordinary text.
     */
    public function test_a_bracket_inside_the_url_is_not_the_alt_closer(): void
    {
        self::assertParses(
            '![a](tg://emoji?id=1)b]',
            'ab]',
            [['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 1, 'document_id' => 1]],
        );
    }

    /**
     * A `)` in the alt text — the bracket scan does not care, but the paren scan
     * starts after the bracket, so a paren before it cannot close anything.
     */
    public function test_a_paren_in_the_alt_text_does_not_close_the_url(): void
    {
        self::assertParses(
            '![a)b](tg://emoji?id=1)',
            'a)b',
            [['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 3, 'document_id' => 1]],
        );
    }

    /** A second `![...]` construct after a first — the scans must not cross. */
    public function test_two_custom_emoji_constructs_do_not_share_delimiters(): void
    {
        self::assertParses(
            '![a](tg://emoji?id=1)![b](tg://emoji?id=2)',
            'ab',
            [
                ['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 1, 'document_id' => 1],
                ['_' => 'messageEntityCustomEmoji', 'offset' => 1, 'length' => 1, 'document_id' => 2],
            ],
        );
    }

    /**
     * An emoji construct immediately followed by a link, so the `]` and `)` scans of
     * one construct sit next to those of another.
     */
    public function test_a_custom_emoji_followed_by_a_link(): void
    {
        self::assertParses(
            '![a](tg://emoji?id=1)[b](u)',
            'ab',
            [
                ['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 1, 'document_id' => 1],
                ['_' => 'messageEntityTextUrl', 'offset' => 1, 'length' => 1, 'url' => 'u'],
            ],
        );
    }

    /** And the reverse order, which is where a leaked scan index would show. */
    public function test_a_link_followed_by_a_custom_emoji(): void
    {
        self::assertParses(
            '[a](u)![b](tg://emoji?id=2)',
            'ab',
            [
                ['_' => 'messageEntityTextUrl', 'offset' => 0, 'length' => 1, 'url' => 'u'],
                ['_' => 'messageEntityCustomEmoji', 'offset' => 1, 'length' => 1, 'document_id' => 2],
            ],
        );
    }
}
