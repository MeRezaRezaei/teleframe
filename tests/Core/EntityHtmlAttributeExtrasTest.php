<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * HTML ATTRIBUTE EXTRAS: WHICH TAG CARRIES WHICH ATTRIBUTE, AND WHAT HAPPENS WHEN
 * IT IS MISSING OR MALFORMED.
 *
 * ## WHY THIS FILE EXISTS
 *
 * `:520`'s `elementExtras()` is two `&&` chains:
 *
 *   $tagName === 'a'         && $element->hasAttribute('href')
 *   $tagName === 'tg-emoji'  && $element->hasAttribute('emoji-id')
 *
 * A `LogicalAnd` mutant survives unless BOTH halves are exercised independently:
 * a tag that matches with the attribute, a tag that matches without it, and a tag
 * that does NOT match while carrying the attribute anyway. The third is the only
 * one that gives the `&&` its teeth — without it, `||` and `&&` agree.
 *
 * ## TWO BEHAVIOURS, AND THE FIRST WAS A DEFECT THIS FILE PROTECTED
 *
 * 1. ~~**A missing attribute yields a `messageEntityTextUrl` with NO `url` key.**~~
 *    **REVERSED 2026-10-18.** This file originally recorded that as measured and
 *    declined to call it a bug, on the grounds that only a live send could settle
 *    it. **That reasoning was wrong: the question was answerable from the committed
 *    schema.**
 *
 *        messageEntityTextUrl#76a6d327 offset:int length:int url:string
 *
 *    `url:string` is unflagged, therefore required, and
 *    `MessageEntityTextUrlData::__construct()` types it `public string $url` with no
 *    default. An entity without `url` cannot be built at all — it raises a
 *    `TypeError` — so the parser was emitting entities it could not serialise.
 *
 *    The same applies to `messageEntityCustomEmoji.document_id:long` and a
 *    `<tg-emoji>` with no `emoji-id`. `pushEntity()` now refuses any entity whose
 *    required wire fields are absent, and the text is still kept: parsing is not
 *    sanitising.
 *
 * 2. **`emoji-id="abc"` yields `document_id => 0`.** The `(int)` cast of a
 *    non-numeric string is 0, and 0 is still emitted when the attribute IS
 *    present. `document_id` is an int64 document reference; 0 is not a document
 *    Telegram has.
 *
 *    **This one stays open.** Unlike item 1 it is a *value* question rather than a
 *    *shape* question: the entity is wire-valid with `document_id: 0`, and only a
 *    live send can say whether the server rejects it. It is pinned as measured, and
 *    deliberately NOT called a bug.
 *
 * The distinction that matters, learned the hard way: a SHAPE defect (a required
 * field missing) is decidable from the schema in the repo. A VALUE defect (a
 * present field holding a meaningless value) needs the server. Filing both under
 * "needs a live account" hid a real, locally-provable bug behind a question that
 * was never actually open.
 */
final class EntityHtmlAttributeExtrasTest extends TestCase
{
    /**
     * @param  list<array<string, int|string>>  $expected
     */
    private static function assertParses(string $html, string $expectedText, array $expected): void
    {
        $result = EntityParser::htmlToEntities($html);

        self::assertSame($expectedText, $result['text']);

        $actual = array_map(
            static function (array $e): array {
                $out = ['_' => $e['_'], 'offset' => $e['offset'], 'length' => $e['length']];
                return $out + array_intersect_key($e, array_flip(['url', 'document_id']));
            },
            $result['entities'],
        );

        self::assertSame($expected, $actual);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE `a` / href CHAIN — both halves, plus the `&&` control
    // ─────────────────────────────────────────────────────────────────────────

    /** Both halves true: the url is carried. */
    public function test_an_anchor_with_an_href_carries_the_url(): void
    {
        self::assertParses(
            '<a href="http://example.com">t</a>',
            't',
            [['_' => 'messageEntityTextUrl', 'offset' => 0, 'length' => 1, 'url' => 'http://example.com']],
        );
    }

    /**
     * First half true, second false: **NO ENTITY** (2026-10-18, reversed).
     *
     * This previously asserted that the entity WAS emitted with no `url`, and
     * recorded that as measured behaviour. It was a defect, and the pin would have
     * protected it from every future fix.
     *
     *     messageEntityTextUrl#76a6d327 offset:int length:int url:string
     *
     * `url:string` is unflagged, so required; `MessageEntityTextUrlData` types it
     * `public string $url` with no default. Building that DTO from an entity with
     * no `url` raises a TypeError, so the entity the parser used to emit here could
     * not be sent.
     *
     * The `&&` still has teeth — see the next test, which is the one that kills the
     * `||` mutant. This case kills "emit it anyway with no url".
     */
    public function test_an_anchor_without_an_href_yields_no_entity(): void
    {
        $result = EntityParser::htmlToEntities('<a>t</a>');

        self::assertSame('t', $result['text'], 'the anchor text is kept');
        self::assertSame([], $result['entities'], 'a link with no destination is not a link');
    }

    /**
     * **The `&&` control.** First half false, second half TRUE.
     *
     * `<span href="…">` carries an `href` but is not an `a`, so `extras()` returns
     * `[]` and the element carries no url. With `||` instead of `&&`, the `href`
     * alone would satisfy the chain and this element would wrongly become a link.
     */
    public function test_a_non_anchor_with_an_href_carries_no_url(): void
    {
        $result = EntityParser::htmlToEntities('<span href="http://example.com">t</span>');

        self::assertSame('t', $result['text']);
        self::assertSame([], $result['entities'], 'an href on a non-anchor is not a link');
    }

    /** An empty href is still an href, so it is carried verbatim. */
    public function test_an_anchor_with_an_empty_href_carries_the_empty_string(): void
    {
        $result = EntityParser::htmlToEntities('<a href="">t</a>');

        self::assertSame('t', $result['text']);
        self::assertSame('', $result['entities'][0]['url'], 'an empty attribute is present, so it is carried');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE `tg-emoji` / emoji-id CHAIN
    // ─────────────────────────────────────────────────────────────────────────

    /** Both halves true: the id is carried as an int. */
    public function test_a_custom_emoji_with_an_id_carries_the_document_id_as_an_int(): void
    {
        $result = EntityParser::htmlToEntities('<tg-emoji emoji-id="42">e</tg-emoji>');

        self::assertSame('e', $result['text']);
        self::assertSame(42, $result['entities'][0]['document_id']);
    }

    /** **The `&&` control** for this chain: not a `tg-emoji`, but carries `emoji-id`. */
    public function test_a_non_emoji_element_with_an_emoji_id_carries_no_document_id(): void
    {
        $result = EntityParser::htmlToEntities('<span emoji-id="42">e</span>');

        self::assertSame('e', $result['text']);
        self::assertSame([], $result['entities'], 'emoji-id on a non-tg-emoji element is not a document reference');
    }

    /**
     * An `emoji-id` on an `<a>` — the two chains must not cross.
     *
     * `<a emoji-id="42">` is an anchor, so the `a`-branch decides; `emoji-id` is
     * not read, so no `document_id` appears.
     */
    public function test_an_anchor_with_an_emoji_id_carries_no_document_id(): void
    {
        $result = EntityParser::htmlToEntities('<a emoji-id="42">t</a>');

        self::assertSame('t', $result['text']);
        self::assertSame([], $result['entities'], 'no href means no link, whatever else the tag carries');
    }

    /** And the mirror: a `tg-emoji` with an `href` gets no url. */
    public function test_a_custom_emoji_with_an_href_carries_no_url(): void
    {
        $result = EntityParser::htmlToEntities('<tg-emoji href="x">e</tg-emoji>');

        self::assertSame('e', $result['text']);
        self::assertSame([], $result['entities'], 'no emoji-id means no custom emoji, whatever else the tag carries');
    }

    /**
     * A missing `emoji-id` yields **NO ENTITY** (2026-10-18, reversed).
     *
     * Same class of defect as the anchor: `messageEntityCustomEmoji` declares
     * `document_id:long` with no `?`, so it is required, and
     * `MessageEntityCustomEmojiData` types it `public int $documentId` — see the
     * note on `test_a_custom_emoji_document_id_is_carried_as_an_int` in
     * `EntityEmojiSliceBoundsTest` for why the wire shape is unserialisable without
     * it. The text is kept.
     */
    public function test_a_custom_emoji_without_an_id_yields_no_entity(): void
    {
        $result = EntityParser::htmlToEntities('<tg-emoji>e</tg-emoji>');

        self::assertSame('e', $result['text'], 'the emoji text is kept');
        self::assertSame([], $result['entities'], 'a custom emoji with no document reference is not an emoji');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // THE (int) CAST — measured, including the cases that worry me
    // ─────────────────────────────────────────────────────────────────────────

    /** A large id survives the cast as an int64 — the round-trip must not truncate. */
    public function test_a_large_emoji_id_survives_the_int_cast(): void
    {
        $result = EntityParser::htmlToEntities('<tg-emoji emoji-id="5368324170671202286">e</tg-emoji>');

        self::assertSame(5368324170671202286, $result['entities'][0]['document_id']);
    }

    /** A negative id is carried as-is. */
    public function test_a_negative_emoji_id_is_carried_as_is(): void
    {
        $result = EntityParser::htmlToEntities('<tg-emoji emoji-id="-5">e</tg-emoji>');

        self::assertSame(-5, $result['entities'][0]['document_id']);
    }

    /**
     * ⛔ **A NON-NUMERIC ID SILENTLY BECOMES 0.**
     *
     * `emoji-id="abc"` casts to `0`, and `0` is emitted with no error and no
     * warning. `document_id` is an int64 document reference — 0 is not a document
     * Telegram has, so this entity references nothing.
     *
     * **Pinned as measured, not asserted as a bug.** What Telegram's server does
     * with it cannot be checked without a live send against a real bot, and this
     * repo has no live account. The open question is named rather than guessed.
     * The same applies to `emoji-id=""` and `emoji-id="0"`.
     */
    public function test_a_non_numeric_emoji_id_casts_to_zero(): void
    {
        $result = EntityParser::htmlToEntities('<tg-emoji emoji-id="abc">e</tg-emoji>');

        self::assertSame('e', $result['text']);
        self::assertSame(0, $result['entities'][0]['document_id'], 'measured: (int)"abc" === 0, emitted silently');
    }

    /** An empty `emoji-id` also casts to 0. */
    public function test_an_empty_emoji_id_casts_to_zero(): void
    {
        $result = EntityParser::htmlToEntities('<tg-emoji emoji-id="">e</tg-emoji>');

        self::assertSame(0, $result['entities'][0]['document_id']);
    }

    /** A literal `0` is carried as 0 — indistinguishable from the cast above. */
    public function test_a_zero_emoji_id_is_carried_as_zero(): void
    {
        $result = EntityParser::htmlToEntities('<tg-emoji emoji-id="0">e</tg-emoji>');

        self::assertSame(0, $result['entities'][0]['document_id']);
    }
}
