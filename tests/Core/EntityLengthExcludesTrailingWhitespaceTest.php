<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * D6 — THE rtrim RULE. AN ENTITY'S `length` MUST NOT INCLUDE TRAILING WHITESPACE.
 *
 * ## THE SPEC, VERBATIM
 *
 * `core.telegram.org/api/entities` (re-scraped 2026-10-17, line 119 of the
 * capture at `.firecrawl/verify-entities.md`):
 *
 *   "Note: the _length_ of an entity **must not** include the length of
 *    trailing newlines or whitespaces, `rtrim` entities before computing their
 *    length: however, the next _offset_ **must** include the length of newlines
 *    or whitespaces that precede it."
 *
 * Telegram names the reference implementations on the same page: **tdlib** and
 * **MadelineProto**. Both rtrim before computing a length.
 *
 * ## WHAT OUR PARSER DID
 *
 * `EntityParser::markdownToEntities()` measured the `pre` block content as-is,
 * so a fenced block ending in a newline produced a `messageEntityPre` whose
 * `length` covered the trailing whitespace. Measured before this fix:
 *
 *     text: "\ncode here\n\n"
 *     messageEntityPre off=0 len=12
 *        slice="\ncode here\n\n" trailingWS=YES -> VIOLATION
 *
 * ## WHY IT IS DANGEROUS RATHER THAN MERELY WRONG
 *
 * The offsets stay self-consistent, so the message still SENDS. Telegram then
 * renders the trailing newlines as part of the code block. Nothing errors; the
 * formatting is simply wrong — the "corrupts quietly" class.
 *
 * ## THE ASYMMETRY THIS TEST PINS
 *
 * The rule has TWO halves and it is easy to satisfy one and break the other:
 *
 *   - `length` EXCLUDES trailing whitespace (rtrim before measuring)
 *   - the NEXT `offset` INCLUDES the whitespace that precedes it
 *
 * Trimming the text itself, or trimming before advancing the running offset,
 * satisfies the first half and violates the second — which shifts every later
 * entity. So the plain text must keep the whitespace; only the measured
 * `length` is trimmed.
 */
final class EntityLengthExcludesTrailingWhitespaceTest extends TestCase
{
    /**
     * The canonical failing case: a fenced block whose content ends in "\n\n".
     */
    public function test_pre_block_length_excludes_trailing_newlines(): void
    {
        $result = EntityParser::markdownToEntities("```\ncode here\n\n```");

        self::assertSame("\ncode here\n\n", $result['text'], 'plain text must KEEP the whitespace');

        self::assertCount(1, $result['entities']);
        $pre = $result['entities'][0];

        self::assertSame('messageEntityPre', $pre['_']);
        self::assertSame(
            10,
            $pre['length'],
            'length MUST exclude the trailing newlines: "\ncode here" is 10 UTF-16 units. '
            .'Measuring the untrimmed "\ncode here\n\n" (12) violates the rtrim rule — the '
            .'trailing newlines get rendered inside the code block.',
        );
    }

    /**
     * The second half of the rule: an entity AFTER a trailing-whitespace block
     * must still start at the offset that INCLUDES that whitespace.
     */
    public function test_the_next_offset_still_includes_the_whitespace(): void
    {
        $result = EntityParser::markdownToEntities("```\ncode\n\n```**tail**");

        self::assertSame("\ncode\n\ntail", $result['text']);

        $pre = $result['entities'][0];
        self::assertSame('messageEntityPre', $pre['_']);
        self::assertSame(0, $pre['offset']);
        self::assertSame(
            5,
            $pre['length'],
            'length EXCLUDES the trailing newlines ("\ncode" is 5 UTF-16 units)',
        );

        // "\ncode\n\n" is 7 units, so the next entity MUST begin at 7. If the
        // running offset were trimmed too, this would be 5 and every later
        // entity in the message would be shifted.
        self::assertCount(2, $result['entities'], 'the trailing word must still be parsed');
        $next = $result['entities'][1];

        self::assertSame(7, $next['offset'], 'next offset MUST include the preceding whitespace');
        self::assertSame(4, $next['length']);
        self::assertSame("\ncode\n\ntail", $result['text']);
        self::assertSame('tail', substr($result['text'], $next['offset'], $next['length']));
        self::assertSame('messageEntityBold', $next['_']);
    }

    /** A block with no trailing whitespace is unaffected by the rule. */
    public function test_a_block_without_trailing_whitespace_is_unchanged(): void
    {
        $result = EntityParser::markdownToEntities('```code```');

        self::assertSame('code', $result['text']);
        self::assertSame(0, $result['entities'][0]['offset']);
        self::assertSame(4, $result['entities'][0]['length']);
    }
}
