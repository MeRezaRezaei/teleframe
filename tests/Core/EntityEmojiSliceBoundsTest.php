<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE CUSTOM-EMOJI SCAN'S SLICE BOUNDS, WITH A SECOND DELIMITER PRESENT.
 *
 * ## WHY THIS FILE EXISTS
 *
 * Eleven mutants survive in the `![alt](tg://emoji?id=N)` branch, at `:193`, `:195`,
 * `:201`, `:203`, `:210` and `:211`. Every one is a bound — a loop limit or a slice
 * length — and every one is invisible for the same reason: **each scan closes on the
 * FIRST delimiter it meets, and the existing corpus has exactly one of each.**
 *
 * A bound mutation only shows up when the delimiter is at a *different distance*
 * from the scan's start. So every test here supplies a SECOND `]` or a SECOND `)`,
 * which is the only way to tell "stops at the first delimiter" from "stops one
 * earlier" or "stops one later".
 *
 * ## WHAT IS ASSERTED
 *
 * `text` and each entity's `_`, `offset`, `length`, `document_id`. No scan index.
 */
final class EntityEmojiSliceBoundsTest extends TestCase
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
                return $out + array_intersect_key($e, array_flip(['document_id', 'url']));
            },
            $result['entities'],
        );

        self::assertSame($expected, $actual);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // :193 — THE OPENER: `!` FOLLOWED BY `[`
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * :193's `$i + 1 < $len` — a `!` as the LAST character.
     *
     * The bound is false, so the branch is not entered and the `!` stays literal.
     * `+1` on that bound admits the next position and reads a character that does
     * not exist.
     */
    public function test_a_bang_as_the_last_character_is_literal_text(): void
    {
        self::assertParses('x!', 'x!', []);
    }

    /** `![` with nothing after it — the opener matches, both scans find nothing. */
    public function test_a_bang_and_bracket_with_nothing_after_them_are_literal(): void
    {
        self::assertParses('![', '![', []);
    }

    /** A bare `!` — the `[` probe is false at the bound. */
    public function test_a_lone_bang_is_literal_text(): void
    {
        self::assertParses('!', '!', []);
    }

    /**
     * The same construct WITHOUT the leading `!` — it falls through to the link
     * branch instead, which is what makes the `!` load-bearing at `:193`.
     */
    public function test_the_same_construct_without_the_bang_is_a_link_not_an_emoji(): void
    {
        self::assertParses(
            '[a](tg://emoji?id=1)',
            'a',
            [['_' => 'messageEntityTextUrl', 'offset' => 0, 'length' => 1, 'url' => 'tg://emoji?id=1']],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // :195 — THE BRACKET SCAN, WITH A SECOND `]`
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ **A SECOND `]` DOES NOT CLOSE THE ALT — the construct is rejected.**
     *
     * `![a]b](tg://emoji?id=1)`: the scan at `:195` finds the FIRST `]` at index 3,
     * so the alt is `a` and the character after that bracket is `b`, not `(`. The
     * `&&` at `:201` is therefore false and nothing is recognised — the whole input
     * stays literal.
     *
     * I expected the emoji to be recognised with alt `a` and the trailing `b](…)`
     * ignored. It is not, and the test caught me. This is the behaviour that gives
     * the `:195` and `:201` mutants their teeth: a scan that ran to the SECOND `]`
     * would find a bracket followed by `(` and wrongly recognise the construct.
     */
    public function test_a_second_bracket_in_the_alt_rejects_the_construct(): void
    {
        self::assertParses('![a]b](tg://emoji?id=1)', '![a]b](tg://emoji?id=1)', []);
    }

    /** A bracket nested inside the alt, same outcome. */
    public function test_a_nested_bracket_in_the_alt_rejects_the_construct(): void
    {
        self::assertParses('![a[b]](tg://emoji?id=1)', '![a[b]](tg://emoji?id=1)', []);
    }

    /**
     * An EMPTY alt — `![](tg://emoji?id=1)`.
     *
     * The slices at `:210` and `:211` are both length 0, so the alt is `''` and
     * `pushEntity()` rejects the zero-length entity. Measured: the text is `''` and
     * there is no entity.
     */
    public function test_an_empty_alt_text_yields_no_entity(): void
    {
        self::assertParses('![](tg://emoji?id=1)', '', []);
    }

    /** A long alt — the slice at `:210` is 10 characters, which a bound error shows. */
    public function test_a_long_alt_text_is_sliced_at_its_own_length(): void
    {
        self::assertParses(
            '![abcdefghij](tg://emoji?id=1)',
            'abcdefghij',
            [['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 10, 'document_id' => 1]],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // :201 — THE `&&` CHAIN
    // ─────────────────────────────────────────────────────────────────────────

    /** `]` present, `(` absent — the chain is false and the input stays literal. */
    public function test_a_bracket_without_a_following_paren_stays_literal(): void
    {
        self::assertParses('![a]b', '![a]b', []);
    }

    /** The bracket as the very last character, so `$closeBracket + 1 < $len` is false. */
    public function test_a_trailing_bracket_with_nothing_after_it_stays_literal(): void
    {
        self::assertParses('![a]', '![a]', []);
    }

    /**
     * `(` present but the URL is not an emoji URL — the branch falls through to the
     * link, so the `!` stays literal and the `[alt](url)` becomes a TextUrl.
     *
     * The `!` in the output is the point: the emoji branch reached `:212` and
     * `str_starts_with` failed, so the link branch then matched at offset 1.
     */
    public function test_a_non_emoji_url_falls_through_to_the_link_branch(): void
    {
        self::assertParses(
            '![a](https://x)',
            '!a',
            [['_' => 'messageEntityTextUrl', 'offset' => 1, 'length' => 1, 'url' => 'https://x']],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // :203 — THE PAREN SCAN, WITH A SECOND `)`
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ **A SECOND `)` IN THE URL IS NOT PART OF THE URL.**
     *
     * `![a](tg://emoji?id=1)x)` — the paren scan at `:203` closes at the FIRST `)`
     * at index 19, so the url is `tg://emoji?id=1` and the id is 1. The trailing
     * `x)` is scanned afterwards as ordinary text, giving `ax)`.
     *
     * A scan that ran to the SECOND `)` would produce the id `1)x` and cast it to
     * `1` — the same integer, but the text would differ. This is the test that
     * separates the `:203` bound mutants.
     */
    public function test_a_second_paren_after_the_url_is_not_part_of_the_url(): void
    {
        self::assertParses(
            '![a](tg://emoji?id=1)x)',
            'ax)',
            [['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 1, 'document_id' => 1]],
        );
    }

    /** No closing paren at all — the scan finds none and nothing is recognised. */
    public function test_a_custom_emoji_without_a_closing_paren_stays_literal(): void
    {
        self::assertParses('![a](tg://emoji?id=1', '![a](tg://emoji?id=1', []);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // :211 — THE URL SLICE, AND THE `(int)` CAST AT `:213`
    // ─────────────────────────────────────────────────────────────────────────

    /** A multi-digit id — the url slice at `:211` is 15 characters, not 14. */
    public function test_a_multi_digit_id_is_sliced_and_cast_from_the_full_url(): void
    {
        self::assertParses(
            '![a](tg://emoji?id=123)',
            'a',
            [['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => 1, 'document_id' => 123]],
        );
    }

    /**
     * The id is empty — `tg://emoji?id=` and nothing after it.
     *
     * **REVERSED 2026-10-18.** This asserted `document_id => 0` as measured
     * behaviour. `substr($url, 14)` is `''` and `(int)''` is 0, so the cast produced
     * a reference to document 0 from user-supplied text with no error.
     *
     * An empty id is not a document id, so the custom-emoji branch now declines and
     * the construct falls through to the LINK branch: the text is `!a` and what
     * remains is a `messageEntityTextUrl` pointing at the literal url.
     *
     * The forward-progress point matters more than the value here: the emoji branch
     * owns the whole `![alt](...)` run, so declining must still advance the scan
     * index. A bare `continue` hung the parser — see the docblock on
     * `isValidEmojiDocumentId`'s callsite.
     */
    public function test_an_empty_id_is_not_a_custom_emoji(): void
    {
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id=)');

        self::assertSame('!a', $result['text'], 'the construct falls through as literal text');
        self::assertSame(
            [['messageEntityTextUrl', 1, 1, 'tg://emoji?id=']],
            array_map(
                static fn (array $e): array => [$e['_'], $e['offset'], $e['length'], $e['url']],
                $result['entities'],
            ),
            'no custom-emoji entity: an empty id is not a document reference',
        );
    }

    /** A `(` inside the url — the paren scan closes there and the id casts to 0. */
    /**
     * **REVERSED 2026-10-18** — this asserted `document_id => 0` and now asserts
     * the emoji branch declines, for the same reason as the empty-id case above.
     *
     * The part that is still pinned here is the SCAN: the `)` inside the url closes
     * the paren search at index 9, so the url is `tg://emoji?id=(1` and the id is
     * `(1` — which `ctype_digit` rejects. The text is `!a`.
     */
    public function test_a_paren_inside_the_url_closes_the_scan_early_and_yields_no_emoji(): void
    {
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id=(1)');

        self::assertSame('!a', $result['text']);
        self::assertSame(
            [],
            array_values(array_filter(
                $result['entities'],
                static fn (array $e): bool => $e['_'] === 'messageEntityCustomEmoji',
            )),
            'the id "(1" is not a document reference, so no custom emoji is emitted',
        );
    }
}
