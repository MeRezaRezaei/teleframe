<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * THE CUSTOM-EMOJI `document_id`, VALIDATED INSTEAD OF CAST.
 *
 * ## WHY THIS FILE EXISTS
 *
 * `messageEntityCustomEmoji.document_id` is `long` and non-flagged in the committed
 * TL — a reference to a document a real account can see. The parser produced it with
 *
 *     ['document_id' => (int) $emojiId]
 *
 * and **`(int)` is not validation**: `(int)"abc"` is `0`, `(int)"9abc"` is `9`, and
 * `(int)"-5"` is `-5`. All three were emitted silently.
 *
 * That matters more than an ordinary parse bug because `markdownToEntities()` runs on
 * **user-supplied message text**. `![a](tg://emoji?id=abc)` is one keystroke away from
 * any sender, and it produced an entity pointing at document 0.
 *
 * ## THE LOOP THIS FILE IS ALSO PROVING ABSENT
 *
 * The first attempt at the fix branched on validity and used a bare `continue` to
 * decline the construct. **That hung the parser for ever** — the branch owns the whole
 * `![alt](...)` run, so without advancing the scan index the loop re-entered on the
 * same `!`. Measured: `![a](tg://emoji?id=9223372036854775808)` exited `124`
 * (timeout) with a bare `continue`, and returns normally now.
 *
 * Every case below therefore asserts **termination** as well as shape. A parser that
 * hangs is worse than one that emits a wrong value, and a test that only checks the
 * output value would have passed against the hanging build only by hanging too.
 *
 * ## WHAT IS ASSERTED
 *
 * `text`, each entity's `_`, `offset`, `length`, `url` and `document_id`. No scan
 * index — and no test here is allowed to hang, which is why each is a single
 * non-recursive call.
 */
final class EntityEmojiDocumentIdValidationTest extends TestCase
{
    /**
     * Every valid id this parser must accept, with the expected document_id.
     *
     * @return iterable<string, array{string, int}>
     */
    public static function validIdProvider(): iterable
    {
        yield 'single digit' => ['1', 1];
        yield 'two digits' => ['42', 42];
        yield 'a real Telegram document id' => ['5368324170671202286', 5368324170671202286];
        yield 'int64 max' => ['9223372036854775807', 9223372036854775807];
        yield 'int64 max minus one' => ['9223372036854775806', 9223372036854775806];
        yield 'leading zeros are still digits' => ['007', 7];
        yield 'one below the digit-count boundary' => ['999999999999999999', 999999999999999999];
    }

    #[DataProvider('validIdProvider')]
    public function test_a_valid_document_id_is_carried_as_an_int(string $id, int $expected): void
    {
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id='.$id.')');

        self::assertSame('a', $result['text']);
        self::assertCount(1, $result['entities']);
        self::assertSame('messageEntityCustomEmoji', $result['entities'][0]['_']);
        self::assertSame($expected, $result['entities'][0]['document_id']);
    }

    /**
     * Every id this parser must REJECT.
     *
     * @return iterable<string, array{string}>
     */
    public static function invalidIdProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'letters' => ['abc'];
        yield 'digits then letters' => ['9abc'];
        yield 'letters then digits' => ['abc9'];
        yield 'negative' => ['-5'];
        yield 'int64 max plus one' => ['9223372036854775808'];
        yield 'far past int64' => ['99999999999999999999'];
        yield 'plus sign' => ['+5'];
        yield 'decimal point' => ['5.0'];
        yield 'hex' => ['0x10'];
        yield 'whitespace padded' => [' 5'];
        yield 'whitespace suffixed' => ['5 '];
        yield 'internal space' => ['5 5'];
        yield 'unicode digits' => ['٥'];
        yield 'fullwidth digit' => ['５'];
    }

    /**
     * `id=0` IS ACCEPTED, and that is a deliberate, documented non-decision.
     *
     * `0` is a well-formed non-negative int64, so the validator cannot reject it on
     * shape grounds — only the server could say whether document 0 exists. This is
     * the residue of Q3 (46.46) and it is the ONE input where "validate rather than
     * cast" stops short of the strongest defensible answer.
     *
     * Rejecting it locally would mean asserting that Telegram has no document 0,
     * which is a claim about the server that no local artifact can support. So it is
     * accepted here, and named here, rather than quietly accepted or quietly
     * rejected.
     */
    public function test_id_zero_is_accepted_because_the_server_question_is_open(): void
    {
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id=0)');

        self::assertSame('a', $result['text']);
        self::assertSame(0, $result['entities'][0]['document_id']);
    }

    #[DataProvider('invalidIdProvider')]
    public function test_an_invalid_document_id_yields_no_custom_emoji(string $id): void
    {
        // No timeout wrapper here on purpose: if this call hangs, the test SUITE
        // hangs and CI shows it as a stuck job rather than a clean failure. The
        // value of this test is precisely that it terminates.
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id='.$id.')');

        foreach ($result['entities'] as $entity) {
            self::assertNotSame(
                'messageEntityCustomEmoji',
                $entity['_'],
                'an invalid document id must not produce a custom-emoji entity: '.json_encode($id),
            );
        }
    }

    /**
     * The rejection leaves the construct as LITERAL TEXT, and the parser makes
     * forward progress rather than re-reading the `!` for ever.
     *
     * Declining drops the custom-emoji entity; the `!` then falls through to the
     * plain-text branch and the `[alt](url)` is re-read by the LINK branch. So the
     * output is `!a` with a `messageEntityTextUrl` — a valid entity at a valid
     * offset, not a malformed one.
     */
    public function test_a_declined_construct_still_yields_a_well_formed_entity(): void
    {
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id=abc)');

        self::assertSame('!a', $result['text'], 'the ! survives as literal text');
        self::assertSame(
            [['messageEntityTextUrl', 1, 1, 'tg://emoji?id=abc']],
            array_map(
                static fn (array $e): array => [$e['_'], $e['offset'], $e['length'], $e['url']],
                $result['entities'],
            ),
            'the link branch re-reads it, at offset 1 because the ! is literal',
        );
    }

    /**
     * A malformed id must not affect the entities AFTER it in the same message.
     *
     * This is the test that would have caught the hang in a way the value checks
     * cannot: if the scan index fails to advance, the second construct is never
     * reached and this assertion never evaluates.
     */
    public function test_a_malformed_id_does_not_stop_the_scan_reaching_later_constructs(): void
    {
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id=abc) and ![b](tg://emoji?id=7)');

        self::assertSame('!a and b', $result['text']);
        self::assertSame(
            [1],
            array_values(array_map(
                static fn (array $e): int => $e['offset'],
                array_filter($result['entities'], static fn (array $e): bool => $e['_'] === 'messageEntityTextUrl'),
            )),
            'the surviving link sits at offset 1: "!a and b" is 0=! 1=a .. 7=b',
        );
        self::assertSame(
            [7],
            array_values(array_map(
                static fn (array $e): int => $e['document_id'],
                array_filter($result['entities'], static fn (array $e): bool => $e['_'] === 'messageEntityCustomEmoji'),
            )),
            'the valid id later in the message still becomes a custom emoji',
        );
    }

    /**
     * The exact input that hung during the fix.
     *
     * Named as its own test so the regression has an identity anyone can search for,
     * rather than living only in a docblock.
     */
    public function test_the_input_that_hung_the_parser_during_the_fix_now_returns(): void
    {
        $result = EntityParser::markdownToEntities('![a](tg://emoji?id=9223372036854775808)');

        self::assertSame('!a', $result['text']);
        self::assertSame(
            [],
            array_filter($result['entities'], static fn (array $e): bool => $e['_'] === 'messageEntityCustomEmoji'),
        );
    }
}
