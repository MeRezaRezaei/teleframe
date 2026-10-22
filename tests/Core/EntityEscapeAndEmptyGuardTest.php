<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE ESCAPE HOP AND THE EMPTY-INPUT GUARDS.
 *
 * ## WHY THIS FILE EXISTS
 *
 * The final `EntityParser` triage left survivors at three guards that every other
 * test file drives only incidentally:
 *
 *   :35 / :70   `trim($x) === ''` — the empty-input guard on both entry points
 *   :87         `$chars[++$i]`     — the escape hop's PRE-INCREMENT
 *   :90         `$i++`             — the step past the escaped character
 *
 * `:35` and `:70` are the easiest survivors in the file to explain and the easiest
 * to get wrong: `trim()` is not `=== ''` on every input, and a test that only ever
 * passes genuinely blank strings cannot tell `trim($x) === ''` from
 * `$x === ''` from `strlen(trim($x)) === 0`. Whitespace-ONLY input is the input
 * that separates them, and `trim()` strips more than spaces.
 *
 * `:87` is the pre-increment: `$chars[++$i]` advances BEFORE the read, so it reads
 * the character AFTER the backslash. Written as `$chars[$i]` it would read the
 * backslash itself and every escape would resolve to itself.
 *
 * ## WHAT IS ASSERTED
 *
 * `text` and each entity's `_`, `offset`, `length`. No scan index.
 */
final class EntityEscapeAndEmptyGuardTest extends TestCase
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

    // ─────────────────────────────────────────────────────────────────────────
    // THE EMPTY-INPUT GUARDS  (:35 markdown, :42 html)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Whitespace-only input on the markdown entry point.
     *
     * `trim()` covers space, tab, CR and LF, so all of these take the guard and
     * return early with no entities. An input of only spaces would also pass a
     * `strlen` check, but the tab and the newline would not — and a parser that
     * returned `"  "` as text for a blank-looking message is a real difference the
     * operator would see as stray whitespace in the sent message.
     */
    public function test_whitespace_only_markdown_returns_empty_text_and_no_entities(): void
    {
        foreach (['', ' ', '   ', "\t", "\n", "\r\n", " \t\r\n "] as $blank) {
            $result = EntityParser::markdownToEntities($blank);

            self::assertSame('', $result['text'], 'blank input yields empty text');
            self::assertSame([], $result['entities'], 'blank input yields no entities');
        }
    }

    /** The same guard on the HTML entry point. */
    public function test_whitespace_only_html_returns_empty_text_and_no_entities(): void
    {
        foreach (['', ' ', "\t", "\n", "\r\n", " \t\r\n "] as $blank) {
            $result = EntityParser::htmlToEntities($blank);

            self::assertSame('', $result['text'], 'blank input yields empty text');
            self::assertSame([], $result['entities'], 'blank input yields no entities');
        }
    }

    /**
     * The guard is a BLANK check, not a trim — and this pins the difference.
     *
     * `trim($x) === ''` returns early only when the WHOLE input is blank. When
     * there is real content, the surrounding whitespace is NOT stripped: it is
     * preserved as literal text and it counts toward the entity's offset.
     *
     * `"  *b*  "` keeps both spaces and puts the bold span at offset 2, not 0.
     * I first asserted the opposite — that whitespace around content was trimmed —
     * and the test caught it. The distinction matters: a caller that trims here
     * would silently rewrite the message, which is the fidelity rule this whole
     * suite is built on.
     */
    public function test_whitespace_around_content_is_preserved_and_offsets_the_entity(): void
    {
        self::assertParses('  *b*  ', '  b  ', [['messageEntityBold', 2, 1]]);
    }

    /** A leading tab counts as one UTF-16 unit, so the span starts at 1. */
    public function test_a_leading_tab_offsets_the_entity_by_one(): void
    {
        self::assertParses("\t*b*\t", "\tb\t", [['messageEntityBold', 1, 1]]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE ESCAPE HOP  (:87 `$chars[++$i]`, :90 `$i++`)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * `\b` is the MarkdownV2 escape for a literal asterisk.
     *
     * Two things are pinned at once. The `b` survives as text — so the hop read
     * the character AFTER the backslash, not the backtick's own slot. And it
     * produces NO bold entity — so the escaped delimiter did not open a span.
     */
    public function test_an_escaped_asterisk_is_literal_text_and_opens_no_span(): void
    {
        self::assertParses('\\*not bold\\*', '*not bold*', []);
    }

    /**
     * The escaped character must not re-enter the scanner's own branches.
     *
     * `\`` is the escape for a literal backtick: if the hop handed the backtick
     * back to the top of the loop, the inline-code branch would open a span that
     * was never asked for.
     */
    public function test_an_escaped_backtick_does_not_open_an_inline_code_span(): void
    {
        self::assertParses('a \\` b', 'a ` b', []);
    }

    /**
     * An escaped bracket, which is the case the LINK scan's own escape handling
     * (:205-207) has to agree with.
     */
    public function test_an_escaped_bracket_does_not_open_a_link(): void
    {
        self::assertParses('\\[a\\]', '[a]', []);
    }

    /**
     * The escape hop advances the offset by the ESCAPED character's width.
     *
     * `\😀` then a bold span: the emoji is one character and two UTF-16 units, so
     * the bold span starts at 2. Reading `$chars[$i]` instead of `$chars[++$i]`
     * would put the backslash in the text and start the span at 1.
     */
    public function test_the_escape_hop_advances_the_offset_by_the_escaped_characters_width(): void
    {
        self::assertParses('\\😀*b*', '😀b', [['messageEntityBold', 2, 1]]);
    }

    /**
     * :90 — the step PAST the escaped character.
     *
     * `\**b*`: the `\*` is consumed as a literal asterisk, then a real `*` opens a
     * bold span that the following `*` closes. If the step at :90 were missing the
     * scan would re-read the escaped character and the offsets would drift.
     */
    public function test_the_step_past_an_escaped_character_positions_the_next_delimiter(): void
    {
        self::assertParses('\\**b*', '*b', [['messageEntityBold', 1, 1]]);
    }

    /**
     * Two escapes in a row, so both hops have to land correctly.
     */
    public function test_two_escapes_in_a_row_both_land(): void
    {
        self::assertParses('\\\\', '\\', []);
        self::assertParses('\\\\*b*', '\\b', [['messageEntityBold', 1, 1]]);
    }

    /**
     * A backslash at the very end escapes nothing — the guard at :86 admits only
     * when `$i + 1 < $len`, so the last backslash stays literal.
     */
    public function test_a_trailing_backslash_is_literal_and_emits_nothing(): void
    {
        self::assertParses('a\\', 'a\\', []);
    }
}
