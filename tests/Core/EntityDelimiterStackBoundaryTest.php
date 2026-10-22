<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE DELIMITER STACK AND THE UNCLOSED-CONSTRUCT SCANS, PINNED AT THE BOUNDARY.
 *
 * ## WHY THIS FILE EXISTS
 *
 * After `EntityScannerLookaheadBoundaryTest` closed the end-of-string lookaheads,
 * Infection still reported survivors concentrated in the scanner's LOOP COUNTERS
 * (`IncrementInteger`, `DecrementInteger`, `Plus`, `LessThan`) and its `break`
 * statements — 37 of the 54 that remained. Those live at five places:
 *
 *   :96 / :100 / :103   the ``` fence opener and its close-fence scan
 *   :142 / :145         the inline ` scan
 *   :161–:179           the `![alt](tg://emoji?id=N)` scan
 *   :203–:224           the `[text](url)` scan, including `\` escapes
 *   :259 / :264 / :279  the two-character delimiter stack (`**`, `__`)
 *   :295 / :300         the single-character delimiter stack (`*`, `_`, `~`)
 *
 * A counter mutation is only observable if the scan's DECISION differs, so each
 * test here drives a case where the loop bound, the counter step, or the `break`
 * decides whether a construct is recognised at all.
 *
 * ## WHAT IS ASSERTED
 *
 * The OBSERVABLE result only: `text`, and each entity's `_` (constructor),
 * `offset` and `length`. No scan index, no loop variable — asserting one of those
 * would be a change-detector, which is the exact smell this suite exists to kill.
 *
 * ## THE THREE RULES, restated from EntityScannerLookaheadBoundaryTest
 *
 *   1. TOTAL — a truncated message never throws and never reads past the end.
 *   2. FIDELITY — the plain text is the input, character for character.
 *   3. NO PARTIAL ENTITY — an unclosed construct stays LITERAL TEXT and emits
 *      nothing; Telegram rejects a zero-length entity outright.
 *
 * Telegram's MarkdownV2 documentation specifies the CLOSED forms only. What a
 * trailing lone `` ` ``, an unclosed `[`, or an unclosed `(` means is genuinely
 * unspecified, so the behaviour below is stated as an explicit pin rather than
 * presented as a Telegram requirement.
 */
final class EntityDelimiterStackBoundaryTest extends TestCase
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
    // THE FENCE  (:96 opener bound, :100 close-scan bound, :103 break)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Three backticks and nothing else.
     *
     * The opener probe at :96 needs `$i + 2 < $len`, which is FALSE at exactly
     * three characters, so this never enters the fence branch at all — it is
     * three separate inline probes, each unclosed. That is the `$i + 2` bound
     * doing its job, and it is why the text keeps all three backticks.
     */
    public function test_three_bare_backticks_are_not_a_code_block(): void
    {
        self::assertParses('```', '```', []);
    }

    /**
     * RULE 1 + 2 + 3 — a fence that is opened and never closed.
     *
     * This is a REGRESSION TEST for a shipped defect, not a boundary pin.
     * `EntityParser` advanced the scan index past the three backticks BEFORE
     * proving a closing fence existed, so an unclosed fence fell through having
     * already consumed them: "```x\ny" parsed to "`\ny" — two backticks and one
     * body character silently deleted from the user's message. Fidelity rule 2
     * says the text is the input character for character; the caller asked to
     * parse, not to sanitise.
     *
     * Both the no-entity half and the byte-for-byte text half are asserted,
     * because either alone would let the defect back in.
     */
    public function test_a_fence_that_is_never_closed_emits_no_entity_and_keeps_its_text(): void
    {
        self::assertParses("```x\ny", "```x\ny", []);
    }

    /**
     * The same defect on a flat body, and on a fence at end-of-input.
     *
     * "```abc" lost the two backticks and the `a`, coming out "`bc".
     */
    public function test_an_unclosed_fence_on_a_flat_body_keeps_its_text(): void
    {
        self::assertParses('```abc', '```abc', []);
    }

    public function test_an_unclosed_fence_at_end_of_input_keeps_its_text(): void
    {
        self::assertParses('```', '```', []);
    }

    /**
     * :129 `$plainText .= $run` must APPEND.
     *
     * With no text before the fence, an assignment instead of an append is
     * indistinguishable — both produce the same string. Only a case where the
     * fence follows real text separates them: assigning would discard everything
     * scanned so far.
     */
    public function test_an_unclosed_fence_appends_to_the_text_already_scanned(): void
    {
        self::assertParses('before ```abc', 'before ```abc', []);
    }

    /**
     * :130 `$utf16Offset += getUtf16Length($run)` must ADVANCE the offset.
     *
     * The run is literal text, so the offset has to move by its UTF-16 width or
     * the next entity's offset points into the run. The run is four backticks —
     * one UTF-16 unit each — followed by a bold span, so the observed offset is 4
     * only if the advance happened.
     */
    public function test_an_unclosed_fence_advances_the_utf16_offset_by_its_own_width(): void
    {
        self::assertParses('````*b*', '````b', [['messageEntityBold', 4, 1]]);
    }

    /**
     * The same, where the run's UTF-16 width is NOT its character count.
     *
     * The backtick run is 4 characters = 4 units. Each astral-plane emoji is 2
     * UTF-16 units but ONE PHP character, so two of them add 4 units while adding
     * only 2 characters — the bold span starts at 4 + 4 = 8. Counting characters
     * instead would put it at 6, so the two readings are distinguishable.
     */
    public function test_the_offset_after_an_unclosed_fence_counts_utf16_units_not_characters(): void
    {
        self::assertParses('````😀😀*b*', '````😀😀b', [['messageEntityBold', 8, 1]]);
    }

    /**
     * :130 `+=` versus `=`, which is only observable MID-MESSAGE.
     *
     * When the unclosed fence is the first thing in the input, `$utf16Offset` is
     * already 0, so assigning instead of adding gives the same number and the
     * mutant is invisible. These two put real text — and an astral-plane
     * character, so the two widths differ — in front of the run:
     *
     *   😀````*b*   →  2 (emoji) + 4 (run) = 6
     *   x````😀*b*  →  1 (x)     + 4 (run) + 2 (emoji) = 7
     *
     * Assigning would restart at the run's own width and read 4 and 6.
     */
    public function test_the_offset_accumulates_across_text_before_an_unclosed_fence(): void
    {
        self::assertParses('😀````*b*', '😀````b', [['messageEntityBold', 6, 1]]);
    }

    public function test_the_offset_accumulates_across_an_unclosed_fence_followed_by_an_emoji(): void
    {
        self::assertParses('x````😀*b*', 'x````😀b', [['messageEntityBold', 7, 1]]);
    }

    /**
     * :125-127 — the run scan must continue to the END of the backtick run.
     *
     * A five-backtick run with text after it: if the scan stopped after three,
     * the last two would be re-examined and the fence branch would fire again.
     */
    public function test_the_run_scan_consumes_every_backtick_in_the_run(): void
    {
        self::assertParses('`````tail', '`````tail', []);
    }

    /**
     * A run of FOUR backticks, which is where the first attempt at the fix broke.
     *
     * Four backticks open a fence at 0 and then, with only one backtick left,
     * cannot close one — so it is genuinely UNCLOSED and must survive as text.
     * Resuming the scan one character after the opener still satisfied the
     * three-character fence probe here, so the fence branch re-entered and
     * emitted the run twice: "````" came out "``````". The whole run has to go
     * back into the text and the scan resumes past it.
     */
    public function test_a_run_of_four_unclosed_backticks_is_not_duplicated(): void
    {
        self::assertParses('````', '````', []);
    }

    /**
     * SIX backticks are NOT the same case, and the difference is the point.
     *
     * A run of six opens at 0 and closes at 3, so it IS a well-formed fence whose
     * content is empty. The zero-length entity is then rejected — Telegram answers
     * ENTITY_BOUNDS_INVALID for one — and the text is correctly empty, because
     * the block really did hold nothing. This test exists to stop a future
     * reader "fixing" that into literal text: the run is consumed by a real
     * closed fence, not by the unclosed-fence path.
     */
    public function test_a_run_of_six_backticks_is_a_closed_empty_fence_not_literal_text(): void
    {
        self::assertParses('``````', '', []);
    }

    /**
     * The close-fence scan at :100-105 and its `break` at :103.
     *
     * The closing fence is the LAST three characters, so `$j + 2 < $len` is the
     * only thing that finds it: one character short and `break` never runs, so
     * `$closePos` stays null and no entity is produced.
     */
    public function test_a_fence_closing_at_the_very_end_of_the_input_is_recognised(): void
    {
        self::assertParses("a\n```\nb\n```", "a\n\nb\n", [['messageEntityPre', 2, 2]]);
    }

    /**
     * The same probe with the fence IMMEDIATELY at offset 0.
     *
     * Here `$i` is 0, so the opener probe `$i + 2 < $len` reads two characters
     * past the backtick that opened the scan. It is a different arithmetic path
     * from the case above (where `$i` was 2), and both have to hold: a test that
     * only ever opens a fence mid-string leaves the `$i = 0` operand untested.
     *
     * Length is 1, not 2: the trailing newline is the block content and the
     * measured length is rtrimmed (D6), while the running offset still advances
     * over the untrimmed content.
     */
    public function test_a_fence_at_offset_zero_is_recognised(): void
    {
        self::assertParses("```php\nx\n```", "x\n", [['messageEntityPre', 0, 1]]);
    }

    /**
     * :96 `$i + 2 < $len` on the OPENER, with a language line.
     *
     * The opener is followed by a language identifier and a newline, so the
     * three-character probe must succeed for the language to be stripped and the
     * block recognised at all.
     */
    public function test_a_fence_with_a_language_identifier_at_the_opener_is_recognised(): void
    {
        self::assertParses("```php\nx\n```", "x\n", [['messageEntityPre', 0, 1]]);
    }

    /** RULE 2 — two backticks are not a fence, and both survive as text. */
    public function test_two_backticks_are_not_a_fence_and_both_survive_as_text(): void
    {
        self::assertParses('``', '', []);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE INLINE TICK  (:142 bound, :145 break)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The `break` at :145 is the only thing that records `$closePos`.
     *
     * With the closing tick as the LAST character, `$j < $len` must admit it.
     * Removing that last character from the input turns the construct into
     * literal text, which is what makes the break observable.
     */
    public function test_an_inline_code_span_closing_at_the_end_of_the_input_is_recognised(): void
    {
        self::assertParses('a `b`', 'a b', [['messageEntityCode', 2, 1]]);
    }

    /** RULE 1 + 2 + 3 — a lone opening tick escapes nothing and closes nothing. */
    public function test_a_lone_backtick_is_literal_text(): void
    {
        self::assertParses('a `', 'a `', []);
    }

    /** RULE 3 — an unclosed inline span stays literal. */
    public function test_an_unclosed_inline_code_span_stays_literal_text(): void
    {
        self::assertParses('a `b', 'a `b', []);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE CUSTOM EMOJI SCAN  (:161, :163, :169, :171, :179)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * :179 — the `!` scanner and the `:163` / `:171` loops with `document_id`
     * carried through, closing at the very last character.
     */
    public function test_a_custom_emoji_closing_at_the_end_of_the_input_is_recognised(): void
    {
        self::assertParses('![a](tg://emoji?id=7)', 'a', [['messageEntityCustomEmoji', 0, 1]]);
    }

    /** RULE 3 — `document_id` is an int64 on the wire, and a cast that is dropped changes it. */
    public function test_a_custom_emoji_document_id_is_carried_as_an_int(): void
    {
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id=5368324170671202286)');

        self::assertSame('a', $result['text']);
        self::assertCount(1, $result['entities']);
        self::assertSame(5368324170671202286, $result['entities'][0]['document_id']);
    }

    /** RULE 1 + 2 + 3 — `![a` with no bracket and no paren is literal text. */
    public function test_an_unclosed_custom_emoji_is_literal_text(): void
    {
        self::assertParses('![a', '![a', []);
    }

    /** RULE 3 — the closing paren is missing, so nothing is recognised. */
    public function test_a_custom_emoji_without_its_closing_paren_is_literal_text(): void
    {
        self::assertParses('![a](tg://emoji?id=7', '![a](tg://emoji?id=7', []);
    }

    /** :169 — `]` is present but `(` is not; the bracket alone is not enough. */
    public function test_a_custom_emoji_without_an_opening_paren_is_literal_text(): void
    {
        self::assertParses('![a', '![a', []);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE LINK SCAN  (:203, :211, :214, :216, :224) — including `\` escapes
    // ─────────────────────────────────────────────────────────────────────────

    /** The `break` at :211 and the `]` at the last character. */
    public function test_a_link_closing_at_the_end_of_the_input_is_recognised(): void
    {
        self::assertParses('[a](b)', 'a', [['messageEntityTextUrl', 0, 1]]);
    }

    /** RULE 3 — the closing paren is missing, so the link is literal text. */
    public function test_a_link_without_its_closing_paren_is_literal_text(): void
    {
        self::assertParses('[a](b', '[a](b', []);
    }

    /** RULE 2 — a trailing lone backslash escapes nothing and stays in the text. */
    public function test_a_trailing_backslash_inside_a_link_stays_in_the_text(): void
    {
        self::assertParses('[a\\', '[a\\', []);
    }

    /**
     * :205-207 — the `\` escape SKIPS the next character inside the bracket.
     *
     * `\]` must not close the bracket, so the link scan finds no `]` and the
     * construct stays literal. That is only observable if the skip is present:
     * without it the `]` closes the bracket at :209, the `(` follows, and a
     * `messageEntityTextUrl` is emitted where there must be none.
     *
     * The text is `[a](b)` rather than the input: the top-level escape rule
     * (:86-92) consumed the backslash and kept the `]`, which is the documented
     * escape behaviour, and the leading `[` is then an unmatched literal.
     */
    public function test_an_escaped_bracket_does_not_close_the_link_text(): void
    {
        self::assertParses('[a\\](b)', '[a](b)', []);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE TWO-CHARACTER STACK  (:259 break, :264 splice, :279 step)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The `break` at :259 searches the stack BACKWARDS for the matching
     * delimiter, so the innermost `**` is the one that closes.
     *
     * `**a **b**` opens bold at 0, pushes a second bold at 4, then closes the
     * INNER one. Without the backward search the outer one would close instead
     * and the spans would differ.
     */
    public function test_the_innermost_matching_two_character_delimiter_closes_first(): void
    {
        self::assertParses('**a **b**', 'a b', [['messageEntityBold', 0, 1]]);
    }

    /** :279 `$i += 2` — a two-character delimiter advances by two, not one. */
    public function test_a_two_character_delimiter_advances_by_two_characters(): void
    {
        self::assertParses('a**b**c', 'abc', [['messageEntityBold', 1, 1]]);
    }

    /** RULE 3 — an unclosed two-character delimiter stays literal text. */
    public function test_an_unclosed_two_character_delimiter_stays_literal_text(): void
    {
        self::assertParses('**a', 'a', []);
    }

    /** :259 — a `**` opener must not be closed by a single `*`. */
    public function test_a_single_character_delimiter_does_not_close_a_two_character_span(): void
    {
        self::assertParses('**a*b', 'ab', []);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE SINGLE-CHARACTER STACK  (:295 break, :300 splice)
    // ─────────────────────────────────────────────────────────────────────────

    /** :300 — the closing `*` pops the stack entry and emits the span. */
    public function test_a_single_character_delimiter_closing_at_the_end_closes_its_span(): void
    {
        self::assertParses('a*b*', 'ab', [['messageEntityBold', 1, 1]]);
    }

    /** RULE 3 — an unclosed single-character delimiter stays literal text. */
    public function test_an_unclosed_single_character_delimiter_stays_literal_text(): void
    {
        self::assertParses('*a', 'a', []);
    }

    /**
     * :295 — the backward stack search is what pairs a `~` with its own `~`.
     *
     * Three different single-character delimiters open in sequence and none
     * closes, so all three stay on the stack and nothing is emitted. Pairing the
     * wrong two would emit a span that does not exist.
     */
    public function test_three_unclosed_distinct_delimiters_emit_nothing(): void
    {
        self::assertParses('*a _b ~c', 'a b c', []);
    }

    /** Each single-character delimiter maps to its own entity type. */
    public function test_each_single_character_delimiter_maps_to_its_own_entity_type(): void
    {
        self::assertParses('*b*', 'b', [['messageEntityBold', 0, 1]]);
        self::assertParses('_i_', 'i', [['messageEntityItalic', 0, 1]]);
        self::assertParses('~s~', 's', [['messageEntityStrike', 0, 1]]);
    }
}
