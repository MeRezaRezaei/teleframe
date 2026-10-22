<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE `||` AND `__` ARMS — THE TWO DELIMITERS THAT WERE NEVER DRIVEN.
 *
 * ## WHY THIS FILE EXISTS
 *
 * `:281`'s two-character branch handles `**`, `__` and `||`, and its `match` at
 * `:282-286` maps each to an entity type. Every pre-existing case in the suite
 * drove `**` and the single-character delimiters. `__` and `||` had **no case at
 * all**, which is why `:291`'s `Break_`, `:296`'s `IncrementInteger` and
 * `:280`'s `Concat` survived — the stack search and the step never ran with those
 * two delimiters on the stack.
 *
 * `||` is `messageEntitySpoiler`, which Telegram added later than bold and italic,
 * so it is the most likely of the three to be unexercised in a hand-written corpus.
 *
 * ## WHAT IS ASSERTED
 *
 * `text` and each entity's `_`, `offset`, `length`. No scan index, no stack depth.
 */
final class EntityTwoCharArmsTest extends TestCase
{
    /**
     * @param  list<array{0: string, 1: int, 2: int}>  $expected
     */
    private static function assertParses(string $markdown, string $expectedText, array $expected): void
    {
        $result = EntityParser::markdownToEntities($markdown);

        self::assertSame($expectedText, $result['text']);
        self::assertSame(
            $expected,
            array_map(static fn (array $e): array => [$e['_'], $e['offset'], $e['length']], $result['entities']),
        );
    }

    /**
     * All three two-character delimiters, each mapped to its own entity type.
     *
     * The `match` at `:282` is only exercised in full if every arm is reached; two
     * of the three were not.
     */
    public function test_each_two_character_delimiter_maps_to_its_own_entity_type(): void
    {
        self::assertParses('**b**', 'b', [['messageEntityBold', 0, 1]]);
        self::assertParses('__u__', 'u', [['messageEntityUnderline', 0, 1]]);
        self::assertParses('||s||', 's', [['messageEntitySpoiler', 0, 1]]);
    }

    /** `__` at a non-zero offset — the offset comes off the running text. */
    public function test_an_underline_after_existing_text_carries_the_accumulated_offset(): void
    {
        self::assertParses('ab __u__', 'ab u', [['messageEntityUnderline', 3, 1]]);
    }

    /** `||` at a non-zero offset. */
    public function test_a_spoiler_after_existing_text_carries_the_accumulated_offset(): void
    {
        self::assertParses('ab ||s||', 'ab s', [['messageEntitySpoiler', 3, 1]]);
    }

    /**
     * :291's `Break_` and `:296`'s step, with `||` on the stack.
     *
     * `||a ||b|| c||` pushes a spoiler at 0, pushes another at 4, closes the inner
     * (length 2 at offset 3), then closes the outer (length 1 at offset 0). A
     * backward search that took the first match instead would pair them the other
     * way round.
     */
    public function test_nested_spoilers_close_innermost_first(): void
    {
        self::assertParses(
            '||a ||b|| c||',
            'a b c',
            [['messageEntitySpoiler', 0, 1], ['messageEntitySpoiler', 3, 2]],
        );
    }

    /** The same for `__`. */
    public function test_nested_underlines_close_innermost_first(): void
    {
        self::assertParses(
            '__a __b__ c__',
            'a b c',
            [['messageEntityUnderline', 0, 1], ['messageEntityUnderline', 3, 2]],
        );
    }

    /**
     * A single `|` is NOT a spoiler.
     *
     * `:280` builds `$twoChar` from two characters, so `|x|` never matches `||`
     * and the pipes stay literal. This is the `:280 Concat` mutant's control: if
     * the pair were built wrongly, `|x|` would become a spoiler.
     */
    public function test_a_single_pipe_is_not_a_two_character_delimiter(): void
    {
        self::assertParses('|x|', '|x|', []);
    }

    /**
     * Three pipes — the opener takes the first two and the closer takes the last
     * two, leaving one literal pipe between them.
     *
     * `|||x|||` → `|x|` with a spoiler of length 2. The step at `:296` is `$i += 2`,
     * so this is the case that pins it.
     */
    public function test_three_pipes_leave_one_literal_pipe_inside_the_spoiler(): void
    {
        self::assertParses('|||x|||', '|x|', [['messageEntitySpoiler', 0, 2]]);
    }

    /**
     * :296's step, for `__` — and the case where four underscores are NOT the
     * mirror of three pipes.
     *
     * I expected `____x____` to leave two literal underscores inside the span, the
     * way `|||x|||` leaves one literal pipe. It does not: four underscores is
     * exactly **two** `__` pairs, so the opener at 0 closes immediately at 2. That
     * is an empty span, the zero-length entity is refused, and the same happens
     * again at the end — the text is `x` with no entity at all.
     *
     * The parity is the point. Three pipes is an odd count, so one pipe is left
     * over between two pairs; four underscores is even, so there is no leftover.
     * Both the odd and the even case are pinned so the step cannot drift.
     */
    public function test_four_underscores_are_two_pairs_and_produce_two_empty_spans(): void
    {
        self::assertParses('____x____', 'x', []);
    }

    /**
     * The ODD case, which is where a step error shows: `___` is one `__` pair plus
     * one lone `_`.
     *
     * The `__` at 0 opens an underline, the `_` at 2 opens an italic, the `__`
     * after the `x` closes the underline, and the trailing `_` closes the italic.
     * Both spans cover the same single character, at offset 0.
     */
    public function test_three_underscores_pair_as_two_char_then_one_char_delimiters(): void
    {
        self::assertParses(
            '___x___',
            'x',
            [['messageEntityUnderline', 0, 1], ['messageEntityItalic', 0, 1]],
        );
    }

    /** Six underscores — three pairs, one real span between the first and last. */
    public function test_six_underscores_produce_one_span_of_two_characters(): void
    {
        self::assertParses('______x______', 'x', [['messageEntityUnderline', 0, 1]]);
    }

    /** A trailing single star with no pair stays literal text. */
    public function test_a_trailing_single_star_stays_literal(): void
    {
        self::assertParses('x*', 'x', []);
    }

    /** A trailing single underscore with no pair stays literal text. */
    public function test_a_trailing_single_underscore_stays_literal(): void
    {
        self::assertParses('x_', 'x', []);
    }

    /** A trailing single pipe with no pair stays literal text. */
    public function test_a_trailing_single_pipe_stays_literal(): void
    {
        self::assertParses('x|', 'x|', []);
    }

    /** An unclosed `||` stays literal content with no entity. */
    public function test_an_unclosed_spoiler_stays_literal_text(): void
    {
        self::assertParses('||x', 'x', []);
    }

    /** `||` as the last thing in the input — the pair cannot close. */
    public function test_a_trailing_pipe_pair_emits_nothing(): void
    {
        self::assertParses('x||', 'x', []);
    }

    /** A bare `||` with nothing between — an empty span, so no entity. */
    public function test_an_empty_spoiler_emits_nothing(): void
    {
        self::assertParses('||', '', []);
    }

    /** A bare `__` likewise. */
    public function test_an_empty_underline_emits_nothing(): void
    {
        self::assertParses('__', '', []);
    }

    /**
     * The two arms must not cross: `||a__b__c||` opens a spoiler, then an
     * underline inside it, and each closes with its own delimiter.
     */
    public function test_a_spoiler_containing_an_underline_pairs_each_with_its_own_delimiter(): void
    {
        self::assertParses(
            '||a__b__c||',
            'abc',
            [['messageEntitySpoiler', 0, 3], ['messageEntityUnderline', 1, 1]],
        );
    }

    /**
     * And the other way round: an underline containing bold.
     *
     * This is the `:291` search with two DIFFERENT delimiters on the stack, where
     * a search by value rather than by position is the only correct reading.
     */
    public function test_a_spoiler_containing_bold_pairs_each_with_its_own_delimiter(): void
    {
        self::assertParses(
            '||a **b** c||',
            'a b c',
            [['messageEntitySpoiler', 0, 5], ['messageEntityBold', 2, 1]],
        );
    }

    /** An underline containing a spoiler — the reverse order on the stack. */
    public function test_an_underline_containing_a_spoiler_pairs_each_with_its_own_delimiter(): void
    {
        self::assertParses(
            '__a ||b|| c__',
            'a b c',
            [['messageEntityUnderline', 0, 5], ['messageEntitySpoiler', 2, 1]],
        );
    }

    /**
     * A single-char delimiter must not close a two-char span, for `||`.
     *
     * `||a|b||` — the single `|` in the middle is a literal pipe, and the spoiler
     * closes on the `||` at the end.
     */
    public function test_a_single_pipe_does_not_close_a_spoiler_span(): void
    {
        self::assertParses('||a|b||', 'a|b', [['messageEntitySpoiler', 0, 3]]);
    }
}
