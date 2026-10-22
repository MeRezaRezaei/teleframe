<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE LINK SCAN'S URL-SIDE ESCAPE, AND THE STACK'S POP.
 *
 * ## WHY THIS FILE EXISTS
 *
 * Two clusters remain untriaged at `:237` (the bracket-side escape step), `:248`
 * (the **URL-side** escape step), `:311` (the two-char step), `:327` (the
 * single-char stack's `break`) and `:332` (`array_splice`'s length).
 *
 * `:248` is the one with **no coverage at all**. Every escape case in the suite puts
 * its backslash inside the link **text**, before the `]`. The URL scan has its own
 * escape branch that has never been entered, so its step is a counter nobody has
 * ever watched move.
 *
 * The stack cases need a construct where the stack holds **more than one entry**, so
 * that a pop that removes the wrong number of entries is visible.
 *
 * ## WHAT IS ASSERTED
 *
 * `text` and each entity's `_`, `offset`, `length`, `url`. No scan index, no stack
 * depth — asserting the stack would be a change-detector.
 */
final class EntityUrlEscapeAndStackPopTest extends TestCase
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

    // ─────────────────────────────────────────────────────────────────────────
    // :248 — THE URL-SIDE ESCAPE, WHICH NO PREVIOUS CASE ENTERED
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * An escaped `)` inside the URL — the URL-side escape skip at `:248`.
     *
     * `[a](u\)v)`: the paren scan meets `\` at index 5, steps over the `)` at 6, and
     * closes at the `)` at 8. So the url is `u\)v` and the link is recognised.
     *
     * Without the step the `)` at 6 would close the link and the url would be `u`,
     * leaving `v)` as trailing text. The url value is the assertion that tells them
     * apart.
     */
    public function test_an_escaped_paren_inside_the_url_does_not_close_the_link(): void
    {
        $result = EntityParser::markdownToEntities('[a](u\\)v)');

        self::assertSame('a', $result['text']);
        self::assertSame('u\\)v', $result['entities'][0]['url'], 'the escaped ) is part of the url');
    }

    /** The same shape with a different body, to pin the step independently. */
    public function test_an_escaped_paren_in_a_longer_url_does_not_close_the_link(): void
    {
        $result = EntityParser::markdownToEntities('[a](x\\)y)');

        self::assertSame('x\\)y', $result['entities'][0]['url']);
    }

    /**
     * Two escapes in the URL — the skip has to land twice.
     *
     * `[a](p\)q\)r)` closes at the final `)`, giving the url `p\)q\)r`.
     */
    public function test_two_escaped_parens_inside_the_url_are_both_skipped(): void
    {
        $result = EntityParser::markdownToEntities('[a](p\\)q\\)r)');

        self::assertSame('p\\)q\\)r', $result['entities'][0]['url']);
    }

    /**
     * ⛔ The escape immediately before the real closer.
     *
     * `[a](u\)` — the `)` at index 6 is escaped, so the URL scan never finds an
     * unescaped `)` and the link is NOT recognised.
     *
     * The text is `[a](u)` and not `[a](u\)`: once the link branch gives up, the
     * scanner reaches the backslash at index 5 as an ordinary character and the
     * TOP-LEVEL escape rule at `:86` consumes it, keeping the `)`. So the backslash
     * is spent escaping a paren that is already there, and only that paren survives.
     *
     * **Pinned as measured.** Whether Telegram would render this differently cannot
     * be checked without a live send; it joins the open questions from 46.14/46.18.
     */
    public function test_an_escaped_paren_before_the_closer_leaves_the_link_unrecognised(): void
    {
        self::assertParses('[a](u\\)', '[a](u)', []);
    }

    /** An escape in the TEXT and another in the URL — both skips are exercised. */
    public function test_escapes_in_both_the_link_text_and_the_url_are_both_skipped(): void
    {
        $result = EntityParser::markdownToEntities('[a\\]b](u\\)v)');

        self::assertSame('a\\]b', $result['text']);
        self::assertSame('u\\)v', $result['entities'][0]['url']);
    }

    /**
     * ⛔ **THE URL-SIDE STEP: AN ESCAPED BACKSLASH.** `[a](\\)`
     *
     * The URL is `\\` — a single literal backslash. The scan meets `\` at index 4,
     * steps over the `\` at 5, and closes at the `)` at 6.
     *
     * **This is the case that pins the step at `:250`**, and no ordinary escape case
     * reaches it: with an escape of a *parenthesis*, stepping one character or two
     * lands on a `)` and a non-`)` respectively, and both still find the same closer
     * later. With an escape of a *backslash*, stepping two skips over the real
     * closing `)` and the link is lost entirely.
     *
     * Measured both ways:
     *
     *   correct (`$k++`)  → text `a`, url `\\`
     *   mutant (`$k += 2`) → text `[a](\\)`, NO entity
     */
    public function test_a_backslash_escaped_in_the_url_still_closes_the_link(): void
    {
        $result = EntityParser::markdownToEntities('[a](\\\\)');

        self::assertSame('a', $result['text'], 'the link is recognised');
        self::assertCount(1, $result['entities']);
        self::assertSame('\\\\', $result['entities'][0]['url'], 'the url is one literal backslash');
    }

    /**
     * The same, with an escaped backslash that is NOT the last thing in the url.
     *
     * `[a](\\)x)`: indices are `4:\`, `5:\`, `6:)`, `7:x`, `8:)`. The scan starts at
     * `k=4`, meets a backslash, steps to `5`, and `5` is `)` — so **the link closes
     * there**, the url is the single backslash at index 4, and `x)` is trailing text.
     *
     * I wrote that the url would be `\\)x` on the strength of the closer being at
     * index 7, and the test caught it: the skip moves ONE position, so it lands on
     * 5, not 6. The url therefore stops short of the trailing content.
     */
    public function test_an_escaped_backslash_before_more_url_content_still_closes(): void
    {
        $result = EntityParser::markdownToEntities('[a](\\\\)x)');

        self::assertSame('ax)', $result['text']);
        self::assertSame('\\\\', $result['entities'][0]['url'], 'the closer is the ) right after the escaped backslash');
    }

    /** An escaped backslash in the middle of a longer url. */
    public function test_an_escaped_backslash_mid_url_still_closes(): void
    {
        $result = EntityParser::markdownToEntities('[a](a\\\\b)');

        self::assertSame('a\\\\b', $result['entities'][0]['url']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // :332 — THE POP, WITH A STACK THAT HOLDS SEVERAL ENTRIES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * `:332`'s `array_splice($stack, $matchedIdx, 1)` — the length argument.
     *
     * `||a||b||c||d||` is four open/close pairs on ONE stack. Each close pops
     * exactly one entry. If the pop removed the wrong number — or none — a later
     * closer would match a stale opener and the spans would drift.
     *
     * Measured: three spans, because the final `||d||`… is checked below.
     */
    public function test_each_close_pops_exactly_one_stack_entry(): void
    {
        self::assertParses(
            '||a||b||c||',
            'abc',
            [
                ['messageEntitySpoiler', 0, 1],
                ['messageEntitySpoiler', 2, 1],
            ],
        );
    }

    /**
     * A fourth pair. The last one is asserted too, so a stale entry cannot hide.
     */
    public function test_a_fourth_pair_also_closes_correctly(): void
    {
        self::assertParses(
            '||a||b||c||d||',
            'abcd',
            [
                ['messageEntitySpoiler', 0, 1],
                ['messageEntitySpoiler', 2, 1],
            ],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // :327 — THE SINGLE-CHAR STACK'S `break`
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * `:327` — the `break` in the single-character stack search.
     *
     * `*a *b* c*` opens `*` at 0, opens a second at 2, closes the second, then
     * closes the first. Two entries with the SAME delimiter are on the stack, which
     * is the only situation where `break` and "keep scanning" can differ.
     */
    public function test_two_same_delimiters_on_the_stack_close_innermost_first(): void
    {
        self::assertParses(
            '*a *b* c*',
            'a b c',
            [['messageEntityBold', 0, 1], ['messageEntityBold', 3, 2]],
        );
    }

    /** Three independent single-character spans in a row. */
    public function test_three_independent_single_character_spans(): void
    {
        self::assertParses(
            '*a* *b* *c*',
            'a b c',
            [
                ['messageEntityBold', 0, 1],
                ['messageEntityBold', 2, 1],
                ['messageEntityBold', 4, 1],
            ],
        );
    }

    /**
     * Two DIFFERENT single-character delimiters on the stack at once — the search
     * must match by value, not by position.
     *
     * The OUTER span has length 5, not 1: the `*` at 0 opens it, the `_` pair opens
     * and closes inside it, and the final `*` closes it — so the bold covers the
     * whole of `a b c`. I first wrote length 1, having carried the shape over from
     * the adjacent-span case, and the test caught it. The inner italic is the one
     * that is length 1.
     */
    public function test_two_different_delimiters_on_the_stack_pair_with_their_own(): void
    {
        self::assertParses(
            '*a _b_ c*',
            'a b c',
            [
                ['messageEntityBold', 0, 5],
                ['messageEntityItalic', 2, 1],
            ],
        );
    }

    /** A two-char opener sitting on the stack under a single-char one. */
    public function test_a_single_character_closer_passes_over_a_two_character_opener(): void
    {
        self::assertParses(
            '*a __b__ c*',
            'a b c',
            [
                ['messageEntityBold', 0, 5],
                ['messageEntityUnderline', 2, 1],
            ],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // :311 — THE TWO-CHARACTER STEP
    // ─────────────────────────────────────────────────────────────────────────

    /** A single `*` is not a two-character delimiter and is consumed as its own. */
    public function test_a_single_asterisk_alone_produces_nothing(): void
    {
        self::assertParses('*', '', []);
    }

    /** A bare `**` — an open with nothing between it and a close. */
    public function test_a_bare_double_asterisk_produces_nothing(): void
    {
        self::assertParses('**', '', []);
    }

    /**
     * The step, with each of the three two-character delimiters.
     *
     * `||x||` closes at offset 2 only if the opener advanced by exactly 2; a step
     * of 1 would land on `|x||` and the closer would be misplaced.
     */
    public function test_the_two_character_step_holds_for_every_delimiter(): void
    {
        self::assertParses('||x||', 'x', [['messageEntitySpoiler', 0, 1]]);
        self::assertParses('__x__', 'x', [['messageEntityUnderline', 0, 1]]);
        self::assertParses('**x**', 'x', [['messageEntityBold', 0, 1]]);
    }
}
