<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE LINK SCAN, WITH TWO LINKS AND WITH ESCAPES INSIDE THEM.
 *
 * ## WHY THIS FILE EXISTS
 *
 * The `:235` bracket scan is the last substantial loop whose counter mutants are
 * all unobserved: `DecrementInteger`, `IncrementInteger` at `:235`, the `continue`
 * at `:239`, the `Break_` at `:243`, and `Plus`/`Minus` at `:246`.
 *
 * All five share one cause. A loop-bound or counter mutation inside the bracket
 * scan is only observable when the scan's DECISION changes — when it finds a
 * `]` at a different place, or fails to find one. Every existing case drives a
 * single link with a single `]`, so:
 *
 *   - widening or narrowing the bound does not move the FIRST `]`
 *   - the escape `continue` at `:239` never runs, because no case has a `\`
 *     inside the link text before the closing bracket
 *   - and the following-construct problem from 46.12/46.13 applies too: an
 *     accumulate mutant on the first construct in a parse is unobservable
 *
 * So this file drives **two** links, and links whose text contains escapes.
 *
 * ## WHAT IS ASSERTED
 *
 * `text` and each entity's `_`, `offset`, `length`, `url`. No scan index.
 *
 * ## ONE OPEN QUESTION, RECORDED NOT RESOLVED
 *
 * Escapes inside link text keep their backslash: `[a\]b](u)` yields the text
 * `a\]b`. Telegram's MarkdownV2 documents `\]` as the escape for a literal `]`,
 * which suggests `a]b`. **This suite does not claim which is right** — that needs
 * a live send against a real bot, and this repo has no live account. Every
 * assertion here pins what the parser does today; the divergence is flagged in the
 * docblock of `test_an_escaped_bracket_inside_link_text_is_skipped_not_closed_on`
 * rather than asserted as either correct or incorrect.
 */
final class EntityLinkScanEscapeTest extends TestCase
{
    /**
     * @param  list<array{0: string, 1: int, 2: int, 3?: string}>  $expected
     */
    private static function assertParses(string $markdown, string $expectedText, array $expected): void
    {
        $result = EntityParser::markdownToEntities($markdown);

        self::assertSame($expectedText, $result['text']);

        $actual = array_map(
            static fn (array $e): array => [$e['_'], $e['offset'], $e['length']] + (isset($e['url']) ? [3 => $e['url']] : []),
            $result['entities'],
        );

        self::assertSame($expected, $actual);
    }

    /**
     * Two links in sequence.
     *
     * The first link's scan runs with `$i` already advanced by 1 past the `[`,
     * so the loop starts at a non-zero position. The second link then has to find
     * ITS `]` — a bound or step mutation that misreads the first `]` changes
     * which bracket the second scan closes on, and the offsets diverge.
     */
    public function test_two_links_in_sequence_each_close_on_their_own_bracket(): void
    {
        self::assertParses(
            '[a](u1)[b](u2)',
            'ab',
            [
                ['messageEntityTextUrl', 0, 1, 'u1'],
                ['messageEntityTextUrl', 1, 1, 'u2'],
            ],
        );
    }

    /**
     * Two links with TEXT between them, so the second scan starts further in.
     *
     * The gap is what makes the two scans' start positions differ, which is the
     * only way a counter mutation inside the second scan is distinguishable from
     * the first.
     */
    public function test_a_second_link_after_text_scans_from_a_different_start(): void
    {
        self::assertParses(
            '[a](u1) gap [b](u2)',
            'a gap b',
            [
                ['messageEntityTextUrl', 0, 1, 'u1'],
                ['messageEntityTextUrl', 6, 1, 'u2'],
            ],
        );
    }

    /**
     * :236-240 — the `\` escape inside the link text, and the `continue` at :239.
     *
     * `[a\]b](u)` — the escaped `]` must NOT close the bracket, so the scan skips
     * it and finds the `]` after the `b`. Removing the `$j++` skip makes the
     * `continue` step one instead of two and the scan closes on the escaped
     * bracket instead, which changes the entity's length.
     *
     * **THE BACKSLASH IS RETAINED IN THE OUTPUT — `a\]b`, not `a]b`.** I wrote
     * this test expecting the backslash to be consumed, and it was not. That is
     * deliberate on the parser's part: the link branch copies the raw slice
     * between the brackets, and the escape's only job there is to stop the `]`
     * from closing the bracket.
     *
     * ⛔ **This is a pin of MEASURED behaviour, not a claim about Telegram.**
     * Telegram's MarkdownV2 documents `\]` as the escape for a literal `]`, which
     * would suggest `a]b`. Whether the server renders the backslash or strips it
     * CANNOT be checked without a live account and a live send, and this repo has
     * neither. So the assertion records what the parser does today, and the
     * divergence from the documented rendering — if it is one — is recorded here
     * as **unverified** rather than quietly asserted either way.
     *
     * To resolve it: send `[a\]b](u)` to a real bot with the live gate and read
     * back what Telegram rendered. Until then this is an open question, not a
     * settled contract.
     */
    public function test_an_escaped_bracket_inside_link_text_is_skipped_not_closed_on(): void
    {
        self::assertParses('[a\\]b](u)', 'a\\]b', [['messageEntityTextUrl', 0, 4, 'u']]);
    }

    /**
     * Two escapes inside the link text — the skip has to land twice.
     *
     * `[a\]b\]c](u)` keeps both escaped brackets and closes on the third `]`,
     * giving a length of 6.
     */
    public function test_two_escaped_brackets_inside_link_text_are_both_skipped(): void
    {
        self::assertParses('[a\\]b\\]c](u)', 'a\\]b\\]c', [['messageEntityTextUrl', 0, 7, 'u']]);
    }

    /**
     * An escape immediately before the closing bracket.
     *
     * `[a\]](u)` — the `]` is escaped, so the link text never closes and the whole
     * construct stays literal. This is the boundary the skip's step decides.
     */
    public function test_an_escape_immediately_before_the_bracket_leaves_the_link_unclosed(): void
    {
        self::assertParses('[a\\](u)', '[a](u)', []);
    }

    /**
     * An escape at the very end of the link text, with a real bracket after it.
     *
     * The skip must not read past the string: the `]` after the escape is still
     * found.
     */
    public function test_the_escape_skip_does_not_consume_the_closing_bracket(): void
    {
        self::assertParses('[a\\]](u)', 'a\\]', [['messageEntityTextUrl', 0, 3, 'u']]);
    }

    /**
     * The URL scan, after an escape in the TEXT.
     *
     * `:248`'s loop starts at `$closeBracket + 2`, so with the bracket pushed past
     * the escaped `]` it starts somewhere it has not started in any other case.
     */
    public function test_the_url_scan_starts_past_an_escaped_bracket_in_the_text(): void
    {
        self::assertParses('[a\\]b](some/url)', 'a\\]b', [['messageEntityTextUrl', 0, 4, 'some/url']]);
    }

    /** Two links where the FIRST contains an escape — the second starts later still. */
    public function test_a_link_following_one_with_an_escape_scans_correctly(): void
    {
        self::assertParses(
            '[a\\]b](u1)[c](u2)',
            'a\\]bc',
            [
                ['messageEntityTextUrl', 0, 4, 'u1'],
                ['messageEntityTextUrl', 4, 1, 'u2'],
            ],
        );
    }

    /**
     * :246 — the `&&` chain: `]` present, `(` missing.
     *
     * `[a]b` has a closing bracket but no `(`, so nothing is recognised. Dropping
     * either conjunct produces a different construct: without the bound check the
     * `(` read runs off the end, and without the `(` test a bare `[a]` closes.
     */
    public function test_a_bracket_without_a_following_paren_stays_literal(): void
    {
        self::assertParses('[a]b', '[a]b', []);
    }

    /** The bracket at the very end of the input, so `$closeBracket + 1 < $len` is false. */
    public function test_a_trailing_bracket_with_nothing_after_it_stays_literal(): void
    {
        self::assertParses('[a]', '[a]', []);
    }

    /** The paren at the very end — the URL scan finds no `)`. */
    public function test_a_link_with_an_opening_paren_and_no_closing_paren_stays_literal(): void
    {
        self::assertParses('[a](u', '[a](u', []);
    }

    /**
     * Two links where the second is unclosed — the first must still be recognised.
     *
     * This is the accumulate-mutation shape from 46.12/46.13 applied to links: an
     * offset mutation on the first link is invisible without a second construct.
     */
    public function test_a_closed_link_before_an_unclosed_one_keeps_its_offset(): void
    {
        self::assertParses('[a](u1)[b](u2', 'a[b](u2', [['messageEntityTextUrl', 0, 1, 'u1']]);
    }
}
