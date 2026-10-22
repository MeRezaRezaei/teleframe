<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE SCANNER'S LOOKAHEAD BOUNDS, PINNED AT THE END OF THE INPUT.
 *
 * ## WHY THIS FILE EXISTS
 *
 * Infection measured `src/Core/Entities/EntityParser.php` at 64.36% covered
 * MSI: 404 mutants, 260 killed, **137 escaped**. The largest single family was
 * 68 survivors, all in the markdown scanner's LOOKAHEAD BOUNDS — the `$i + N <
 * $len` / `$j + N < $len` / `$chars[$X + N]` probes that decide whether the
 * character after a delimiter exists at all.
 *
 * The corpus fed the scanner had content on BOTH sides of every delimiter, so
 * every END-OF-STRING boundary was invisible: no input ever asked the scanner
 * what to do when the delimiter is the last one or two characters of the
 * message. These are those inputs. Each one puts the delimiter against the end
 * of the string (or, where the probe is a multi-character lookahead, the
 * smallest near-miss that makes the probe's three operands disagree).
 *
 * ## WHAT IS ASSERTED
 *
 * The OBSERVABLE result only: `text`, and each entity's constructor, `offset`,
 * `length`, `url` and `document_id`. No internal scan state is asserted,
 * because a scan index is not part of the contract and an assertion on one
 * would be a change-detector.
 *
 * ## WHY THE TRUNCATED-INPUT BEHAVIOUR IS PINNED RATHER THAN AVOIDED
 *
 * Telegram's MarkdownV2 documentation specifies the CLOSED forms only: it says
 * `` ` `` escapes the next character, that a code block is exactly three
 * backticks, and that a link is `[text](url)`. It does NOT say what a trailing
 * lone `\`, an unclosed `[`, or an unclosed `(` should mean — those are
 * malformed inputs, and Telegram's own server rejects them. So this class's
 * behaviour on them is genuinely UNSPECIFIED by Telegram.
 *
 * The defensible pin is therefore stated in full, and it is the same rule at
 * every one of them:
 *
 *   1. TOTAL — the scanner never reads past the end of the array and never
 *      crashes. A scanner that throws on a truncated message takes the whole
 *      send down, so totality outranks agreement with an undefined case.
 *   2. FIDELITY — the plain text is exactly the input, character for
 *      character. Silently DROPPING the final `\` would change the message the
 *      caller sends; the caller asked to parse, not to sanitise.
 *   3. NO PARTIAL ENTITY — an unclosed construct stays LITERAL TEXT and emits
 *      nothing. An entity with offsets that cover markup the user wrote as
 *      text is a mis-rendered message, and a zero-length entity is rejected
 *      outright by Telegram (ENTITY_BOUNDS_INVALID).
 *
 * Each test below names which of those three it is defending.
 */
final class EntityScannerLookaheadBoundaryTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // LINE 86 — `if ($c === '\\' && $i + 1 < $len)`: the escape lookahead.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * THE BACKSLASH AS THE VERY LAST CHARACTER (rule 1 + rule 2).
     *
     * `$i + 1 < $len` is the guard that stops `++$i` from reading `$chars[$len]`.
     * With one backslash and nothing after it the guard must be FALSE, and the
     * backslash must survive as literal text — it escapes nothing, so there is
     * nothing to escape it away with.
     *
     * Mutated to `$i + 0 < $len`, `$i + 1 <= $len`, or `$i - 1 < $len`, the guard
     * admits the last position, `$chars[$len]` reads as absent, and the
     * backslash is dropped from the text.
     */
    public function test_a_backslash_as_the_last_character_is_literal_text_because_it_escapes_nothing(): void
    {
        $result = EntityParser::markdownToEntities('a\\');

        self::assertSame('a\\', $result['text'], 'a trailing backslash escapes nothing, so it stays in the text');
        self::assertSame([], $result['entities'], 'literal text emits no entity');
    }

    /**
     * THE BACKSLASH ONE CHARACTER FROM THE END (rule 2 — the other side of the
     * same guard).
     *
     * `$i + 1 < $len` must be TRUE here: there IS a character to escape.
     * Mutated to `$i + 2 < $len` the guard goes false, the `\` is treated as
     * literal text, and `x` reaches the output as itself instead of being
     * consumed by the escape.
     */
    public function test_a_backslash_one_character_from_the_end_still_escapes_that_character(): void
    {
        $result = EntityParser::markdownToEntities('a\\x');

        self::assertSame('ax', $result['text'], 'the escaped character replaces the backslash: both are consumed');
        self::assertSame([], $result['entities'], 'literal text emits no entity');
    }

    /**
     * THE SAME BOUNDARY, IMMEDIATELY AFTER A CLOSED CONSTRUCT.
     *
     * This is the pair that makes the lookahead a live question twice in one
     * message: `![x](tg://emoji?id=7)` is consumed as an entity, and the scan
     * RESUMES on the character right after it — which here is the last
     * character of the whole input. The offset of that resumption is what
     * `$i = $closeParen + 1` (line 192) owns, and the escape lookahead is
     * re-evaluated at the new position.
     *
     * Both mutations therefore die here: `$i = $closeParen + 2` leaves the `)`
     * to be re-scanned as text, and `$i + 1 < $len` → `$i + 1 <= $len` drops the
     * final backslash.
     */
    public function test_a_backslash_immediately_after_a_closed_custom_emoji_is_at_the_end_of_the_input(): void
    {
        $result = EntityParser::markdownToEntities('![x](tg://emoji?id=7)\\');

        self::assertSame('x\\', $result['text'], 'the emoji consumed its markup; the trailing backslash is literal');
        self::assertSame(
            [
                ['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 1, 'document_id' => 7],
            ],
            $result['entities'],
            'exactly one entity: the custom emoji, before the literal backslash',
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // LINE 96 — `if ($c === '`' && $i + 2 < $len && $chars[$i + 1] === '`'
    //           && $chars[$i + 2] === '`')`: the FENCE-OPEN lookahead.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * THE FENCE PROBE'S THREE OPERANDS MUST DISAGREE TO BE OBSERVABLE.
     *
     * A code block is EXACTLY three backticks — this is specified by Telegram,
     * not inferred. So with only two backticks in reach, the probe must not
     * open a fence, and the pair must parse as inline code instead.
     *
     * Mutated so the FIRST operand reads `$chars[$i + 0]` or `$chars[$i + 2]`,
     * the probe collapses to "is `$chars[$i + 2]` a backtick?" and opens a
     * fence here; the fence then finds no closing triple, falls through to the
     * inline-code branch, and the output loses both the backticks and the
     * entity.
     */
    public function test_a_backtick_pair_around_one_character_is_inline_code_and_not_a_fence(): void
    {
        $result = EntityParser::markdownToEntities('`a`');

        self::assertSame('a', $result['text'], 'both backticks are delimiters and neither reaches the text');
        self::assertSame(
            [['_' => 'messageEntityCode', 'offset' => 0, 'length' => 1]],
            $result['entities'],
            'a pair is inline code: one code entity spanning the character between them',
        );
    }

    /**
     * THE THIRD OPERAND LOOKS THREE POSITIONS AHEAD, AND MUST.
     *
     * Two backticks then a non-backtick: the third operand must read
     * `$chars[$i + 2]` and find `a`, so no fence opens. Mutated to
     * `$chars[$i + 1]` the probe asks the same index twice, both times about a
     * backtick, and opens a fence that does not exist — the two backticks then
     * get consumed as an empty code span and the character between them is
     * measured against a delimiter pair that was never there.
     */
    public function test_the_fence_probe_must_read_three_positions_ahead_to_reject_two_backticks(): void
    {
        $result = EntityParser::markdownToEntities('``a');

        self::assertSame('a', $result['text'], 'two backticks form one empty code span; the character survives');
        self::assertSame([], $result['entities'], 'an empty code span is a zero-length entity, which is never emitted');
    }

    // ─────────────────────────────────────────────────────────────────────
    // LINE 101 — `if ($chars[$j] === '`' && $chars[$j + 1] === '`'
    //            && $chars[$j + 2] === '`')`: the FENCE-CLOSE probe.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * THE CLOSING PROBE NEEDS THREE ADJACENT BACKTICKS, NOT ONE PLUS A MATCH.
     *
     * A fenced block closes on exactly three adjacent backticks — specified by
     * Telegram. Inside `` ```a`x`z``` `` there are single backticks at
     * positions 4 and 6, one character apart, and neither closes the block.
     *
     * Mutated so the second operand reads `$chars[$j + 0]` or `$chars[$j + 2]`,
     * the probe degenerates into "is `$chars[$j]` and `$chars[$j + 2]` both
     * backticks?" and closes the block early at position 4, measuring the
     * entity as `a` instead of `a`x`z`.
     */
    public function test_a_lone_backtick_inside_a_fenced_block_does_not_close_the_block(): void
    {
        $result = EntityParser::markdownToEntities('```a`x`z```');

        self::assertSame('a`x`z', $result['text'], 'the inner backticks are body text, so both survive');
        self::assertSame(
            [['_' => 'messageEntityPre', 'offset' => 0, 'length' => 5]],
            $result['entities'],
            'the block runs to the real closing triple, so it spans all five characters',
        );
    }

    /**
     * TWO ADJACENT BACKTICKS ARE NOT THREE.
     *
     * The case that discriminates the THIRD operand: `` ```a``z``` `` has a
     * backtick pair at 4 and 5 and a triple at 7..9. Mutated to
     * `$chars[$j + 1]`, the probe asks the second index twice and closes at the
     * pair, measuring `a` instead of `a``z`.
     */
    public function test_two_adjacent_backticks_inside_a_fenced_block_do_not_close_the_block(): void
    {
        $result = EntityParser::markdownToEntities('```a``z```');

        self::assertSame('a``z', $result['text'], 'the inner pair is body text, so both survive');
        self::assertSame(
            [['_' => 'messageEntityPre', 'offset' => 0, 'length' => 4]],
            $result['entities'],
            'the block runs to the real closing triple, so it spans all four characters',
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // LINE 142 — `for ($j = $i + 1; $j < $len; $j++)`: the INLINE-CODE close
    //            scan, which must start one position AFTER the opening tick.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * THE CLOSING BACKTICK AS THE LAST CHARACTER OF THE INPUT.
     *
     * `a`` ` — two backticks, and the second is the final character of the
     * message. The close scan starts at `$i + 1`, so it must find that final
     * backtick and read it as the CLOSER, leaving an empty code span (which
     * emits nothing, because a zero-length entity is never produced).
     *
     * Mutated to start at `$i + 2`, the scan starts past the end, finds no
     * closer, and both backticks fall through to the plain-text branch — the
     * output gains two characters it must never have.
     */
    public function test_two_backticks_at_the_very_end_of_the_input_are_an_empty_code_span(): void
    {
        $result = EntityParser::markdownToEntities('a``');

        self::assertSame('a', $result['text'], 'both backticks are delimiters; neither is literal text');
        self::assertSame([], $result['entities'], 'an empty code span measures zero length and is never emitted');
    }

    // ─────────────────────────────────────────────────────────────────────
    // LINES 161 / 163 / 169 / 171 / 192 — the CUSTOM-EMOJI branch.
    //
    // This branch had NO exact-value assertion anywhere in the suite: the
    // corpus property tests assert rtrim-stability, in-bounds and
    // surrogate-alignment, and every mutant below satisfies all three while
    // producing the wrong entity. So these tests pin the VALUES.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * THE COMPLETE CUSTOM EMOJI, PINNED BY VALUE (rule 3 for the closed form).
     *
     * `![x](tg://emoji?id=7)` is the one shape Telegram fully specifies: the
     * alt text replaces the whole markup in the plain text, and the entity
     * carries the document id from the URL.
     *
     * Every probe on this path is load-bearing here: the `!` must be
     * IMMEDIATELY followed by `[`, the bracket scan must start two positions
     * in, the scan for `]` must run, the `(` must follow the `]`, the scan for
     * `)` must run, and the resume position must land past the `)`. Removing
     * or negating any of them turns this into a text-url entity, a plain-text
     * pass-through, or a double-scanned `)`.
     */
    public function test_a_complete_custom_emoji_becomes_its_alt_text_with_its_document_id(): void
    {
        $result = EntityParser::markdownToEntities('![x](tg://emoji?id=7)');

        self::assertSame('x', $result['text'], 'the alt text replaces the whole markup');
        self::assertSame(
            [['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 1, 'document_id' => 7]],
            $result['entities'],
            'exactly one custom-emoji entity, one unit wide, carrying document_id 7',
        );
    }

    /**
     * THE RESUME POSITION, MEASURED AGAINST TEXT THAT FOLLOWS.
     *
     * `hi ![x](tg://emoji?id=7) there` — the emoji does not end the message, so
     * a resume position that is off by one is visible in the text: leaving the
     * `)` to be re-scanned appends it, and re-scanning the space before
     * `there` shifts nothing visible but does double-count the offset. The
     * entity's `offset` is the sharp assertion: it is the running UTF-16
     * position at the moment the alt text was appended.
     */
    public function test_a_custom_emoji_before_more_text_offsets_the_entity_past_the_prefix(): void
    {
        $result = EntityParser::markdownToEntities('hi ![x](tg://emoji?id=7) there');

        self::assertSame('hi x there', $result['text'], 'no character is dropped or double-counted around the entity');
        self::assertSame(
            [['_' => 'messageEntityCustomEmoji', 'offset' => 3, 'length' => 1, 'document_id' => 7]],
            $result['entities'],
            'the entity opens at 3, after "hi "',
        );
    }

    /**
     * TWO EMOJI, TWO IDS — the resume position has to be right twice.
     *
     * A resume that steps back re-reads the previous `)`, which is not a
     * delimiter, so it lands in the text and shifts everything after it. The
     * second entity's offset and the id it carries are both downstream of the
     * first one's resume position.
     */
    public function test_two_custom_emoji_keep_separate_offsets_lengths_and_ids(): void
    {
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id=7)![b](tg://emoji?id=8)');

        self::assertSame('ab', $result['text'], 'both alt texts survive, neither markup does');
        self::assertSame(
            [
                ['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 1, 'document_id' => 7],
                ['_' => 'messageEntityCustomEmoji', 'offset' => 1, 'length' => 1, 'document_id' => 8],
            ],
            $result['entities'],
            'offset ASC, one unit each, and each entity carries ITS OWN id',
        );
    }

    /**
     * AN EMPTY ALT TEXT IS A ZERO-LENGTH ENTITY, WHICH IS NEVER EMITTED.
     *
     * Telegram rejects a zero-length entity with ENTITY_BOUNDS_INVALID, so
     * `![](tg://emoji?id=7)` has exactly one outcome this class can defend:
     * the markup IS recognised as a custom emoji and consumed, the alt text is
     * measured at zero, and therefore no entity is emitted. The construct is
     * not demoted to literal text — it was recognised, and what was discarded
     * is the entity, not the markup.
     *
     * The BRACKET-SCAN start position is what makes this observable, and the
     * text is the witness. The scan must begin at `$i + 2`, past the `[`;
     * started at `$i + 3` it steps over the `]` and never finds a closer, the
     * custom-emoji branch is skipped, and the scanner falls through to the
     * link branch one position later — which emits nothing but leaves the `!`
     * it has already appended to the text. Correct output is the empty string;
     * the mutant's is `"!"`.
     */
    public function test_an_empty_alt_text_custom_emoji_emits_no_entity(): void
    {
        $result = EntityParser::markdownToEntities('![](tg://emoji?id=7)');

        self::assertSame('', $result['text'], 'the recognised construct contributes its alt text, which is empty');
        self::assertSame([], $result['entities'], 'nothing is emitted: a zero-length entity would be rejected by Telegram');
    }

    /**
     * THE `[` MUST BE IMMEDIATELY AFTER THE `!`.
     *
     * `!x[t](tg://emoji?id=7)` — a bang, one character, then the emoji-shaped
     * remainder. A custom emoji is `!` + `[` with nothing between; here the
     * `[` is two positions from the `!`, so the `!` is literal text and what
     * remains is an ordinary inline link.
     *
     * Note what the link's URL is NOT allowed to change: a `tg://emoji?id=`
     * target does not promote a link into a custom emoji. The scheme is only
     * consulted on the custom-emoji path, and this path was never entered.
     */
    public function test_a_bang_two_positions_before_an_open_bracket_does_not_open_a_custom_emoji(): void
    {
        $result = EntityParser::markdownToEntities('!x[t](tg://emoji?id=7)');

        self::assertSame('!xt', $result['text'], 'the bang is literal text because it is not adjacent to the bracket');
        self::assertSame(
            [['_' => 'messageEntityTextUrl', 'offset' => 2, 'length' => 1, 'url' => 'tg://emoji?id=7']],
            $result['entities'],
            'what remains is an ordinary link, and a tg:// target does not make it a custom emoji',
        );
    }

    /**
     * THE GUARD IS ABOUT THE CURRENT CHARACTER, NOT ABOUT "SOMETHING FOLLOWS".
     *
     * `[[a](tg://emoji?id=7)` — an open bracket whose predecessor is another
     * open bracket. Mutating the guard to `$c === '!' || $i + 1 < $len` drops
     * the `$c === '!'` requirement: every position with a `[` one place ahead
     * would enter the custom-emoji branch, and this input would parse as a
     * custom emoji with alt text `a` instead of as a link.
     */
    public function test_a_bang_must_be_the_current_character_to_open_a_custom_emoji(): void
    {
        $result = EntityParser::markdownToEntities('[[a](tg://emoji?id=7)');

        self::assertSame('[a', $result['text'], 'the leading bracket is literal text; the link keeps its own text');
        self::assertSame(
            [['_' => 'messageEntityTextUrl', 'offset' => 0, 'length' => 2, 'url' => 'tg://emoji?id=7']],
            $result['entities'],
            'a link, two units wide, anchored at 0 — not a custom emoji',
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // LINES 203 / 214 / 216 — the INLINE-LINK branch.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * AN EMPTY LINK IS A ZERO-LENGTH ENTITY, WHICH IS NEVER EMITTED.
     *
     * `[]()` — the bracket scan and the paren scan both have to run for the
     * branch to decide anything. Mutated to start the bracket scan at
     * `$i + 2` it steps over the `]`, never finds a closer, and the markup
     * becomes literal text; mutated to start the paren scan at
     * `$closeBracket + 3` it steps over the `)`, and the markup becomes
     * literal text too. Both change the text, which is the whole point.
     */
    public function test_an_empty_link_target_and_text_emit_no_entity(): void
    {
        $result = EntityParser::markdownToEntities('[]()');

        self::assertSame('', $result['text'], 'both delimiters are consumed; nothing is left to send');
        self::assertSame([], $result['entities'], 'an empty link measures zero length and is never emitted');
    }

    /**
     * THE `)` AS THE LAST CHARACTER OF THE INPUT.
     *
     * `![x]()` — the closing paren is the final character of the message. The
     * URL is empty, so the custom-emoji path rejects it (`tg://emoji?id=` is
     * not a prefix of the empty string) and the SAME markup is re-read by the
     * plain-link branch, which does not check the scheme. That fall-through is
     * the pinned contract: an empty target is a link with an empty URL, not a
     * dropped entity.
     *
     * Mutated to start the paren scan at `$closeBracket + 3`, the final `)` is
     * never reached, no closer is found, and the markup stays literal text.
     */
    public function test_the_closing_paren_as_the_last_character_falls_through_to_the_plain_link_branch(): void
    {
        $result = EntityParser::markdownToEntities('![x]()');

        self::assertSame('!x', $result['text'], 'the bang is literal text and the pair is a link');
        self::assertSame(
            [['_' => 'messageEntityTextUrl', 'offset' => 1, 'length' => 1, 'url' => '']],
            $result['entities'],
            'an empty target yields a link with an empty url, not a dropped entity',
        );
    }

    /**
     * THE OPEN BRACKET AGAINST THE END OF THE INPUT (rule 3).
     *
     * `[x` — two characters, no `]`. Mutating the guard to
     * `$closeBracket !== null || ...` admits a null closer, and because `null`
     * behaves as `0` in the index arithmetic the branch then reads
     * `$chars[1]` — the `x` itself — as if a closer had been found. The
     * scanner must not go looking for a `]` that is not there.
     */
    public function test_a_link_whose_bracket_never_closes_is_literal_text(): void
    {
        $result = EntityParser::markdownToEntities('[x');

        self::assertSame('[x', $result['text'], 'an unclosed bracket is literal text, character for character');
        self::assertSame([], $result['entities'], 'no partial link is emitted');
    }

    /**
     * THE `(` AS THE LAST CHARACTER OF THE INPUT (rule 3).
     *
     * `[x](` — the open paren is the final character, so the paren scan has
     * nothing to find and the construct is incomplete.
     */
    public function test_an_open_paren_as_the_last_character_does_not_open_a_link_target(): void
    {
        $result = EntityParser::markdownToEntities('[x](');

        self::assertSame('[x](', $result['text'], 'an unterminated target is literal text');
        self::assertSame([], $result['entities'], 'no link is emitted from an empty, unterminated target');
    }

    /**
     * AN UNTERMINATED TARGET THAT FILLS THE REST OF THE INPUT (rule 3).
     *
     * `[x](https://e.org` — every character after the `(` belongs to a target
     * that never closes. The whole thing is literal text, so the URL the caller
     * wrote by hand appears verbatim in the outgoing message rather than being
     * swallowed into a link whose closing paren does not exist.
     */
    public function test_a_link_target_that_never_closes_is_literal_text(): void
    {
        $result = EntityParser::markdownToEntities('[x](https://e.org');

        self::assertSame('[x](https://e.org', $result['text'], 'an unterminated target keeps every character as text');
        self::assertSame([], $result['entities'], 'no link is emitted from an unterminated target');
    }

    /**
     * A MISSING CLOSER MUST NOT BE SUBSTITUTED BY ARITHMETIC ON `null`.
     *
     * `x([()` — there is a `[`, then a paren pair, but the bracket scan finds
     * no `]` at all. Mutating `$closeBracket !== null && ...` to
     * `$closeBracket !== null || ...` lets the branch proceed with a null
     * closer; `null + 2` is `2`, so the scan starts inside the literal text it
     * was supposed to be guarding, finds the `)` at position 4, and consumes
     * the input to the end of the string. The text loses two characters.
     */
    public function test_a_link_whose_bracket_never_closes_does_not_swallow_the_following_paren(): void
    {
        $result = EntityParser::markdownToEntities('x([()');

        self::assertSame('x([()', $result['text'], 'every character survives; the paren pair is not a link target');
        self::assertSame([], $result['entities'], 'no link is emitted');
    }

    /**
     * THE LINK'S `(` MUST BE THE CHARACTER IMMEDIATELY AFTER THE `]`.
     *
     * `[x] (a)` — a space between them, so this is not a link. Mutating the
     * `$chars[$closeBracket + 1]` probe to `$chars[$closeBracket + 2]` reads
     * the `(` one position too far and builds a link whose URL begins with the
     * `(`, consuming a construct the caller wrote as plain text.
     */
    public function test_a_space_between_the_closing_bracket_and_the_paren_is_not_a_link(): void
    {
        $result = EntityParser::markdownToEntities('[x] (a)');

        self::assertSame('[x] (a)', $result['text'], 'the space breaks the construct, so every character is text');
        self::assertSame([], $result['entities'], 'no link is emitted');
    }

    /**
     * THE SAME PROBE, READ ONE POSITION TOO EARLY.
     *
     * `![(] tg://emoji?id=7)` — the character BEFORE the `]` is a `(`, the
     * character AFTER it is a space. Mutating the probe to
     * `$chars[$closeBracket - 1]` finds the earlier paren and reads the
     * remainder of the input as an emoji URL, manufacturing an entity out of
     * text the caller never delimited.
     */
    public function test_a_paren_before_the_closing_bracket_is_not_the_link_target_open(): void
    {
        $result = EntityParser::markdownToEntities('![(] tg://emoji?id=7)');

        self::assertSame('![(] tg://emoji?id=7)', $result['text'], 'the space after the bracket breaks the construct');
        self::assertSame([], $result['entities'], 'no custom emoji is manufactured out of literal text');
    }
}
