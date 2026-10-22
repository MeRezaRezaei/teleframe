<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * THE FENCE'S CLOSE-SCAN AND ITS LANGUAGE LINE.
 *
 * ## WHY THIS FILE EXISTS
 *
 * Seven mutants in the fence branch are still unobserved, at `:98`, `:101` (×2),
 * `:104`, `:107` (×1) and the `:125` run scan (×5). Every one of them is a
 * decision the fence branch makes about **where the closing fence is**, and all of
 * them are invisible for the same reason: no case puts a closing fence anywhere
 * other than the very end of the input, so the scan's arithmetic has nothing to
 * distinguish.
 *
 * A closing fence in the MIDDLE, with content after it, is what separates them:
 *
 *   ```a``` + trailing text
 *   ```a\n``` + more
 *   ```lang\nbody\n``` + text after
 *
 * ## THE LANGUAGE-LINE RULE, restated
 *
 * A first line consisting only of `[A-Za-z0-9_-]` is stripped as the language tag
 * and the block content is what remains. A first line with any other character is
 * NOT a language line and stays in the content. Both halves are driven below,
 * because `:125`'s `strspn` chain only has teeth on the negative half.
 *
 * ## WHAT IS ASSERTED
 *
 * `text` and each entity's `_`, `offset`, `length`, and the `language` attribute.
 */
final class EntityFenceCloseScanTest extends TestCase
{
    /**
     * @param  list<array{0: string, 1: int, 2: int, 3?: string}>  $expected
     */
    private static function assertParses(string $markdown, string $expectedText, array $expected): void
    {
        $result = EntityParser::markdownToEntities($markdown);

        self::assertSame($expectedText, $result['text']);

        $actual = array_map(
            static fn (array $e): array => [$e['_'], $e['offset'], $e['length']] + (isset($e['language']) ? [3 => $e['language']] : []),
            $result['entities'],
        );

        self::assertSame($expected, $actual);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE CLOSING FENCE IN THE MIDDLE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * :101 / :104 — the close scan finds a fence that is NOT at the end.
     *
     * The scan bound `$j + 2 < $len` has to admit a `j` well before the end for
     * this to be found at all, and the `break` at `:104` is what records it. After
     * the block, `tail` must be scanned as ordinary text — if the scan ran past
     * the closing fence instead, the block would swallow it.
     */
    public function test_a_closing_fence_in_the_middle_ends_the_block(): void
    {
        self::assertParses('```a```tail', 'atail', [['messageEntityPre', 0, 1]]);
    }

    /**
     * The same, with a newline before the closer and more text after — and this
     * one produces NO entity, which is CORRECT and took real work to establish.
     *
     * ```a\n``` — the block content is `a\n`. Its first line, `a`, is entirely
     * within `[A-Za-z0-9_-]`, so the language rule at `:143` fires and strips it
     * as the tag. What remains is the empty string, the rtrimmed length is 0, and
     * `pushEntity()` rejects a zero-length entity — Telegram answers
     * `ENTITY_BOUNDS_INVALID` for one. The text is `tail`, the content after the
     * block.
     *
     * I expected a `messageEntityPre` here and the test caught me. The chain is
     * three rules deep — valid-language-tag strip, then rtrim, then the
     * zero-length rejection — and no single one of them is wrong.
     *
     * The contrast case is the test below: an INVALID tag (`a b`, containing a
     * space) is not stripped, the content survives, and the entity is emitted.
     * Together the two pin both halves of the language rule.
     */
    public function test_a_closing_fence_whose_only_line_is_a_language_tag_yields_no_entity(): void
    {
        self::assertParses("```a\n```tail", 'tail', []);
    }

    /**
     * The two-line version of the same: `a` is stripped as the tag and a bare
     * newline remains, whose rtrimmed length is also 0.
     */
    public function test_a_two_line_block_whose_content_is_only_newlines_yields_no_entity(): void
    {
        self::assertParses("```a\n\n```tail", "\ntail", []);
    }

    /**
     * The empty-first-line case: `""` is not a valid tag (`:143` requires
     * `$firstLine !== ''`), so nothing is stripped and the content survives whole.
     */
    public function test_an_empty_first_line_is_not_stripped_and_the_entity_survives(): void
    {
        self::assertParses("```\na\n```tail", "\na\ntail", [['messageEntityPre', 0, 2]]);
    }

    /**
     * Two blocks separated by text — each scan finds its own closer.
     *
     * This is the case that separates the two `:101` mutants from each other: the
     * first scan must stop at the FIRST fence, and the second must not be able to
     * reuse the first one's position.
     */
    public function test_two_blocks_each_find_their_own_closing_fence(): void
    {
        self::assertParses(
            '```a```x```b```y',
            'axby',
            [['messageEntityPre', 0, 1], ['messageEntityPre', 2, 1]],
        );
    }

    /** A closing fence followed by a newline. */
    public function test_a_closing_fence_followed_by_a_newline(): void
    {
        self::assertParses("```a```\n", "a\n", [['messageEntityPre', 0, 1]]);
    }

    /** A closing fence followed by a single trailing space. */
    public function test_a_closing_fence_followed_by_a_trailing_space(): void
    {
        self::assertParses("```a``` ", "a ", [['messageEntityPre', 0, 1]]);
    }

    /**
     * :107 — the `$closePos === null` decision, with a closer present.
     *
     * The unclosed path restores the backtick run; the closed path must NOT, or
     * the opener would reappear in the text. `"```a```"` has a real closer, so the
     * output is `a` with one entity and no stray backticks.
     */
    public function test_a_closed_fence_does_not_restore_the_opener(): void
    {
        self::assertParses('```a```', 'a', [['messageEntityPre', 0, 1]]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE LANGUAGE LINE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * :125 — the `strspn` chain's POSITIVE half: a valid language line IS stripped.
     *
     * `php` is entirely `[A-Za-z0-9_-]`, so it becomes the tag and is not part of
     * the content. The block content is `x\n`, measured at 1 after rtrim, and the
     * entity carries `language: php`.
     */
    public function test_a_valid_language_line_is_stripped_and_carried_as_the_tag(): void
    {
        self::assertParses("```php\nx\n```", "x\n", [['messageEntityPre', 0, 1, 'php']]);
    }

    /**
     * A language line with the underscore and dash the character class allows.
     *
     * `c_cpp-2` exercises both non-alphabet members of `[A-Za-z0-9_-]`, which a
     * narrower class would reject and leave in the content.
     */
    public function test_a_language_line_with_underscore_and_dash_is_stripped(): void
    {
        self::assertParses("```c_cpp-2\nx\n```", "x\n", [['messageEntityPre', 0, 1, 'c_cpp-2']]);
    }

    /**
     * The NEGATIVE half: a first line with a space is not a language line.
     *
     * `not a lang` contains spaces, so `strspn` stops at the first one and the
     * check fails. The whole line stays in the content — dropping the entity
     * instead would be the mutant.
     */
    public function test_a_first_line_with_a_space_is_not_a_language_line(): void
    {
        self::assertParses("```not a lang\nx\n```", "not a lang\nx\n", [['messageEntityPre', 0, 12]]);
    }

    /** A first line that is empty — also not a language line, and it stays. */
    public function test_an_empty_first_line_is_not_a_language_line(): void
    {
        self::assertParses("```\nx\n```", "\nx\n", [['messageEntityPre', 0, 2]]);
    }

    /**
     * A language-only block: the tag is stripped and nothing is left.
     *
     * ` ```php\n``` ` leaves no content, so the zero-length entity is rejected and
     * the text is empty. This is the documented zero-length rule, not a defect —
     * Telegram answers ENTITY_BOUNDS_INVALID for a zero-length entity.
     */
    public function test_a_language_only_block_yields_no_entity(): void
    {
        self::assertParses("```php\n```", '', []);
    }

    /**
     * :125's `While_` and the run scan, with a fence followed by MORE backticks.
     *
     * ` ```a```` `` — the closer is found, and the extra backticks after it are
     * scanned on their own. They must not be folded back into the block.
     */
    public function test_backticks_after_the_closing_fence_are_scanned_separately(): void
    {
        self::assertParses('```a````', 'a`', [['messageEntityPre', 0, 1]]);
    }

    /**
     * The offset arithmetic across a mid-string block plus a following span.
     *
     * ` ```a```*b* ` — the block contributes 1 UTF-16 unit, so the bold span after
     * it starts at 1. Measured text is `ab`: the block's content `a`, then the
     * bold content `b`.
     */
    public function test_the_offset_after_a_mid_string_block_counts_only_the_block(): void
    {
        self::assertParses('```a```*b*', 'ab', [['messageEntityPre', 0, 1], ['messageEntityBold', 1, 1]]);
    }
}
