<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE EMOJI SCAN AND THE DELIMITER STACK, AT NON-ZERO OFFSETS AND UNDER NESTING.
 *
 * ## WHY THIS FILE EXISTS
 *
 * Closing the mutation survivors in `EntityParser` left 47, of which the bulk are
 * loop-counter arithmetic (`DecrementInteger` 10, `IncrementInteger` 6, `Break_` 6,
 * `Plus` 5, `LessThan` 4, `Increment` 4, `Assignment` 4) concentrated at:
 *
 *   :193 / :195 / :201 / :203 / :210 / :211   the `![alt](tg://emoji?id=N)` scan
 *   :235–:256 / :280 / :291 / :296            the two- and one-character stack
 *   :311 / :327 / :332                        the blockquote and offset paths
 *
 * A counter mutation is only observable when the scan's DECISION differs. Two
 * properties of the existing corpus made every one of them invisible:
 *
 *   1. **Everything started at offset 0.** `$utf16Offset` begins at 0, so
 *      `+=` and `=` agree on the first span. Only a construct preceded by real
 *      text separates them.
 *   2. **Every delimiter was closed in order.** The stack was never asked to
 *      pair an inner delimiter with an outer one of a different width, and never
 *      had to find a closing delimiter that belonged to an earlier entry.
 *
 * This file drives both: offsets that are already non-zero when the construct is
 * reached, and delimiters that nest, cross and reopen.
 *
 * ## WHAT IS ASSERTED
 *
 * `text` and each entity's `_`, `offset`, `length`. No scan index — asserting one
 * would be a change-detector, which is the smell this work exists to remove.
 *
 * ## FIDELITY
 *
 * The plain text is the input, character for character, in every case here. Where
 * a construct IS recognised, the markup is replaced by its content — that is the
 * point of a parser. Where it is NOT recognised, the markup survives as literal
 * text, which is what makes these assertions meaningful.
 */
final class EntityEmojiAndStackOffsetTest extends TestCase
{
    /**
     * @param  list<array{0: string, 1: int, 2: int}>  $expected
     */
    private static function assertParses(string $markdown, string $expectedText, array $expected): void
    {
        $result = EntityParser::markdownToEntities($markdown);

        self::assertSame($expectedText, $result['text'], 'plain text must equal the input, character for character');

        $actual = array_map(
            static fn (array $e): array => [$e['_'], $e['offset'], $e['length']],
            $result['entities'],
        );

        self::assertSame($expected, $actual, 'entity constructor, offset and length');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE EMOJI SCAN AT A NON-ZERO OFFSET
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * :215 `$plainText .= $altText`, with text already scanned.
     *
     * `ab ` is in the plain text before the emoji, so appending and assigning are
     * distinguishable: append gives `ab x`, assigning gives `x`. This is the half
     * of the pair that the offset test below cannot see.
     */
    public function test_a_custom_emoji_after_existing_text_appends_its_alt_text(): void
    {
        self::assertParses('ab ![x](tg://emoji?id=5)', 'ab x', [['messageEntityCustomEmoji', 3, 1]]);
    }

    /**
     * :216 `$utf16Offset += …` — the ACCUMULATE half, which the test above cannot see.
     *
     * `$startOffset` is captured at :214 BEFORE the advance, so it stays 3 whether
     * the advance is `+=` or `=`. The only observable that distinguishes them is
     * a SECOND entity after the emoji, whose offset is read from `$utf16Offset`
     * after the fact. With `+=` the bold span starts at 3 + 1 = 4; with `=` it
     * restarts at the alt text's own width and reads 1.
     *
     * This is the same shape as the unclosed-fence offset tests: an accumulate
     * mutation on the FIRST construct is invisible, and only a following
     * construct makes it observable.
     */
    public function test_the_offset_accumulates_across_a_custom_emoji_into_the_next_span(): void
    {
        self::assertParses(
            'ab ![x](tg://emoji?id=5)*b*',
            'ab xb',
            [
                ['messageEntityCustomEmoji', 3, 1],
                ['messageEntityBold', 4, 1],
            ],
        );
    }

    /**
     * The alt text is more than one character, so its LENGTH is exercised too.
     *
     * `![alt](tg://emoji?id=9)` preceded by `pre ` puts the entity at offset 4
     * with length 3.
     */
    public function test_a_multi_character_alt_text_is_measured_from_the_accumulated_offset(): void
    {
        self::assertParses('pre ![alt](tg://emoji?id=9) post', 'pre alt post', [['messageEntityCustomEmoji', 4, 3]]);
    }

    /**
     * :210 — the alt text is the slice between `[` and `]`.
     *
     * A `(` inside the alt text must not end the alt: the paren belongs to the
     * alt, and the entity length is 4, covering `a(b)`.
     */
    public function test_a_paren_inside_the_alt_text_does_not_end_the_alt(): void
    {
        self::assertParses('![a(b)](tg://emoji?id=1)', 'a(b)', [['messageEntityCustomEmoji', 0, 4]]);
    }

    /**
     * :201 — the `]` must be followed by `(`.
     *
     * `![a[b]` has a bracket but no `(`, so nothing is recognised and the whole
     * construct stays literal. The `]` closes at the FIRST one, which is why the
     * remaining `b]` never becomes a URL.
     */
    public function test_an_emoji_without_an_opening_paren_stays_literal(): void
    {
        self::assertParses('![a[b]', '![a[b]', []);
    }

    /** :211 — a second `)` ends the URL; trailing text after it is not consumed. */
    public function test_text_after_a_custom_emoji_is_not_consumed(): void
    {
        self::assertParses('![a](tg://emoji?id=12)x', 'ax', [['messageEntityCustomEmoji', 0, 1]]);
    }

    /** Two emoji in sequence: the second must not reuse the first's offset. */
    public function test_two_custom_emojis_each_carry_their_own_offset(): void
    {
        self::assertParses(
            '![a](tg://emoji?id=1)![b](tg://emoji?id=2)',
            'ab',
            [['messageEntityCustomEmoji', 0, 1], ['messageEntityCustomEmoji', 1, 1]],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE STACK: NESTING, CROSSING, REOPENING
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * :235–:256 — the stack pairs the INNERMOST matching delimiter.
     *
     * `**a **b** c**` pushes bold at 0, pushes bold again at 4, then the next
     * `**` pops the inner one (length 2, at offset 3) and the last one pops the
     * outer (length 1, at offset 0). A search that took the first match instead
     * of the last would emit those two spans the other way round.
     */
    public function test_nested_same_delimiters_close_innermost_first(): void
    {
        self::assertParses(
            '**a **b** c**',
            'a b c',
            [['messageEntityBold', 0, 1], ['messageEntityBold', 3, 2]],
        );
    }

    /**
     * Three delimiters in a row: `**a***b***` — the middle `*` is consumed by the
     * two-character probe, which is what makes the offsets 0 and 1 rather than
     * three separate single-character spans.
     */
    public function test_three_delimiters_in_a_row_produce_two_adjacent_spans(): void
    {
        self::assertParses(
            '**a***b***',
            'ab',
            [['messageEntityBold', 0, 1], ['messageEntityBold', 1, 1]],
        );
    }

    /**
     * :256 — a single `*` does NOT close a `**` span.
     *
     * `*a **b** c*` opens a single-character bold at 0, a two-character bold at 2,
     * closes the inner one, then the final `*` closes the OUTER one — a span of
     * length 5. If the single-character probe could close the two-character entry
     * the outer span would end early.
     */
    public function test_a_single_character_delimiter_does_not_close_a_two_character_span(): void
    {
        self::assertParses(
            '*a **b** c*',
            'a b c',
            [['messageEntityBold', 0, 5], ['messageEntityBold', 2, 1]],
        );
    }

    /**
     * The mirror image: `**a* b*c**` — the `*` inside the `**` span closes the
     * outer span, not a same-width one, because no single-character entry is on
     * the stack yet.
     */
    public function test_a_single_character_delimiter_inside_a_two_character_span_closes_it(): void
    {
        self::assertParses(
            '**a* b*c**',
            'a bc',
            [['messageEntityBold', 0, 4], ['messageEntityBold', 1, 2]],
        );
    }

    /**
     * :246 / :248 — three DIFFERENT delimiters on the stack at once.
     *
     * Each closing delimiter must find its own entry by value, not by position.
     * Getting the pairing wrong emits a span of the wrong type.
     */
    public function test_three_different_delimiters_nest_without_mispairing(): void
    {
        self::assertParses(
            '*a **b ~c~~ d** e* f',
            'a b c d e f',
            [
                ['messageEntityBold', 0, 9],
                ['messageEntityBold', 2, 5],
                ['messageEntityStrike', 4, 1],
            ],
        );
    }

    /** Adjacent closed spans of three different types, each with its own type. */
    public function test_adjacent_spans_each_carry_their_own_entity_type(): void
    {
        self::assertParses(
            '*a*_b_~c~',
            'abc',
            [
                ['messageEntityBold', 0, 1],
                ['messageEntityItalic', 1, 1],
                ['messageEntityStrike', 2, 1],
            ],
        );
    }

    /** :291 — a closed span can be followed by a fresh, independent one. */
    public function test_a_closed_span_does_not_consume_the_next_one(): void
    {
        self::assertParses(
            '*a* *b*',
            'a b',
            [['messageEntityBold', 0, 1], ['messageEntityBold', 2, 1]],
        );
    }

    /** :296 — the step after a two-character delimiter, with a single one after it. */
    public function test_a_two_character_span_closes_before_a_single_character_one_opens(): void
    {
        self::assertParses('*a**_b_', 'ab', [['messageEntityItalic', 1, 1]]);
    }

    /** :311 — an offset advance across two adjacent spans of different types. */
    public function test_the_offset_advances_across_two_adjacent_spans(): void
    {
        self::assertParses(
            '*b*~s~',
            'bs',
            [['messageEntityBold', 0, 1], ['messageEntityStrike', 1, 1]],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONSTRUCTS THE SCANNER DOES NOT HANDLE — pinned so they stay literal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * `>` blockquotes and `#` headings are NOT in the parser's construct list.
     *
     * Telegram's MarkdownV2 supports both, and neither is recognised here. That
     * is a real gap in the parser, not in the test — but whatever the decision,
     * the current behaviour is that they stay LITERAL, so the pin records what
     * ships today rather than pretending it is correct.
     */
    public function test_blockquote_and_heading_syntax_stay_literal_text(): void
    {
        self::assertParses("> q\n> r", "> q\n> r", []);
        self::assertParses("# h\ntext", "# h\ntext", []);
    }

    /**
     * A nested link: the inner `[b](c)` is consumed as content, so the outer
     * bracket pair closes on the SECOND `]`.
     *
     * The entity covers `a[b]` at length 3 — the inner link's markup is part of
     * the link TEXT here, which is what the parser does today.
     */
    public function test_a_nested_link_closes_on_the_second_bracket(): void
    {
        self::assertParses('[a[b](c)](d)', 'a[b](d)', [['messageEntityTextUrl', 0, 3]]);
    }
}
