<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * THE HTML TAG → ENTITY-TYPE MAP, arm by arm.
 *
 * `EntityParser::htmlToEntities()` is the entry point every HTML path in the
 * package lands on, and `messageEntityBold` / `Italic` / `Underline` /
 * `Strike` / `Pre` / `Spoiler` / `CustomEmoji` arrive FROM CLIENTS AS HTML —
 * Telegram's own documented message-format list names `<b>`, `<strong>`,
 * `<i>`, `<em>`, `<u>`, `<ins>`, `<s>`, `<strike>`, `<del>`, `<code>`,
 * `<pre>`, `<tg-spoiler>`, `<blockquote>`, `<a href>`, `<tg-emoji
 * emoji-id>`. So every arm of the map is a shipped input format, and a test
 * that pins it pins the specification rather than the implementation.
 *
 * WHY THIS FILE EXISTS AT ALL. Infection measured
 * `src/Core/Entities/EntityParser.php` and found ELEVEN `MatchArmRemoval`
 * mutants SURVIVING on `mapTagToEntityType()`'s single `match` expression:
 * dropping the `'strong'`, `'em'`, `'ins'`, `'u'`, `'s'`, `'strike'`,
 * `'del'`, `'pre'`, `'tg-spoiler'` and `'tg-emoji'` arms, and dropping
 * `default => null`, each left the WHOLE suite green. A surviving mutant is a
 * demonstrated hole: the tool changed the code and nothing noticed.
 *
 * The measured root cause is not that these formats are exotic. It is that the
 * existing HTML coverage CANNOT SEE A MISSING ENTITY. Two facts, both
 * measured before this file was written:
 *
 *   1. The property suite (EntityIndependentOraclePropertyTest) DOES feed
 *      `<u>`, `<s>`, `<pre>`, `<a href>`, `<tg-emoji>`, `<blockquote>` and
 *      `<tg-spoiler>`, and it DOES assert that every entity type is produced —
 *      but its property is `rtrim`-stability OF THE ENTITIES THAT CAME BACK.
 *      Drop an arm and fewer entities come back; each survivor is still
 *      rtrim-stable, and `messageEntityUnderline` is still produced by the
 *      markdown corpus's `__underline__`. A property test over a REDUCED
 *      output cannot detect the reduction. That is the hole, and it is a
 *      property of the assertion, not of the parser.
 *   2. The alias tags `strong` / `em` / `ins` / `strike` / `del` were in no
 *      HTML input anywhere in the suite at all, and no test ever fed an
 *      UNMAPPED element, so `default => null` had nothing to hold it down.
 *
 * So every test below asserts the entity EXISTS — type, offset, length — not
 * a property of it. `test_each_html_tag_alias_emits_its_own_entity_type`
 * exists precisely to be the assertion a property test cannot make.
 */
final class EntityParserHtmlTagMappingTest extends TestCase
{
    /**
     * Each alias/deprecated spelling a real client sends, and the entity type
     * it must produce. Every row here corresponds to a `MatchArmRemoval`
     * survivor; see the class docblock.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function htmlTagAliasProvider(): array
    {
        return [
            // 'b', 'strong' => messageEntityBold      — `<strong>` is the HTML5
            // spelling and is what Telegram's own docs list alongside `<b>`.
            'strong-is-bold' => ['<strong>x</strong>', 'messageEntityBold'],
            // 'i', 'em' => messageEntityItalic
            'em-is-italic' => ['<em>x</em>', 'messageEntityItalic'],
            // 'u', 'ins' => messageEntityUnderline — `<ins>` is the HTML5
            // spelling of underline; `<u>` alone is the deprecated one.
            'ins-is-underline' => ['<ins>x</ins>', 'messageEntityUnderline'],
            'u-is-underline' => ['<u>x</u>', 'messageEntityUnderline'],
            // 's', 'strike', 'del' => messageEntityStrike — all three are HTML
            // spellings of strike-through; `<del>` additionally means
            // "deleted" semantically, `<s>` is the deprecated one.
            's-is-strike' => ['<s>x</s>', 'messageEntityStrike'],
            'strike-is-strike' => ['<strike>x</strike>', 'messageEntityStrike'],
            'del-is-strike' => ['<del>x</del>', 'messageEntityStrike'],
            // 'pre' => messageEntityPre — NOT cosmetic. `pre` is the exact
            // entity type of the shipped rtrim defect this parser was fixed
            // for (EntityLengthExcludesTrailingWhitespaceTest), so the arm
            // that produces it from HTML matters twice over.
            'pre-is-pre' => ['<pre>x</pre>', 'messageEntityPre'],
            // 'tg-spoiler' => messageEntitySpoiler — Telegram's own tag name,
            // dash included.
            'tg-spoiler-is-spoiler' => ['<tg-spoiler>x</tg-spoiler>', 'messageEntitySpoiler'],
            // 'tg-emoji' => messageEntityCustomEmoji
            'tg-emoji-is-custom-emoji' => ['<tg-emoji emoji-id="7">x</tg-emoji>', 'messageEntityCustomEmoji'],
        ];
    }

    /**
     * ONE produced entity, of the right type, covering the tag's own text.
     *
     * `assertSame` rather than `assertEquals` throughout: the lengths are
     * UTF-16 CODE UNITS, so `1` must be `1` and not `'1'`, and a loose
     * comparison is exactly the kind of comparison that lets a type mutation
     * through unremarked.
     */
    #[DataProvider('htmlTagAliasProvider')]
    public function test_each_html_tag_alias_emits_its_own_entity_type(string $html, string $expectedType): void
    {
        $result = EntityParser::htmlToEntities($html);

        self::assertSame('x', $result['text'], "the tag's text must survive the DOM walk: {$html}");

        self::assertCount(
            1,
            $result['entities'],
            "{$html} must produce exactly ONE entity of type {$expectedType} — a DROPPED match arm produces ZERO entities, "
            .'which is the whole mutation this test exists to catch',
        );

        self::assertSame($expectedType, $result['entities'][0]['_'], "{$html}: wrong entity constructor");
        self::assertSame(0, $result['entities'][0]['offset'], "{$html}: the entity starts where the text starts");
        self::assertSame(1, $result['entities'][0]['length'], "{$html}: the entity covers exactly its own one character");
    }

    /**
     * `<pre>` in HTML carries the rtrim rule AND the offset-advance half of it,
     * in one document.
     *
     * Measured by hand on `"code after bold"`: `<pre>` covers units 0-3
     * ("code"), then " after " is 7 units, so `<b>` opens at 11. If the offset
     * did not advance over the code block the bold entity would land at 7; if
     * the length were not rtrimmed, `<pre>` would report 9 and the whole
     * document's geometry would shift.
     */
    public function test_html_pre_entity_rtrims_its_length_and_later_offsets_still_advance(): void
    {
        $result = EntityParser::htmlToEntities('<pre>code</pre> after <b>bold</b>');

        self::assertSame('code after bold', $result['text'], 'the block element contributes its text and nothing else');
        self::assertCount(2, $result['entities'], 'the <pre> and the <b> are two entities');

        self::assertSame('messageEntityPre', $result['entities'][0]['_'], 'the code block is a pre entity');
        self::assertSame(0, $result['entities'][0]['offset'], 'the pre entity opens at 0');
        self::assertSame(4, $result['entities'][0]['length'], 'the pre entity covers "code" and no trailing whitespace');

        self::assertSame('messageEntityBold', $result['entities'][1]['_'], 'the bold span is a bold entity');
        self::assertSame(11, $result['entities'][1]['offset'], 'the bold span opens at 11: 4 units of code + 7 of " after "');
        self::assertSame(4, $result['entities'][1]['length'], 'the bold span covers "bold"');
    }

    /**
     * THE `default => null` ARM.
     *
     * An element the map does not know is TEXT, not an entity: its content
     * stays in the plain text and it produces no entity of its own. A `<div>`
     * wrapper around real content is ordinary HTML — a caller wrapping a
     * Telegram payload in markup must not lose the payload, and must not get
     * an entity type invented for it.
     *
     * With the `default` arm deleted, PHP's `match` raises
     * `UnhandledMatchError` on any unmapped tag, so this document turns a
     * thrown Error into a failed assertion.
     */
    public function test_unmapped_elements_produce_their_text_and_no_entity(): void
    {
        $result = EntityParser::htmlToEntities('before <div>middle</div> after');

        self::assertSame('before middle after', $result['text'], 'an unmapped wrapper must not swallow or duplicate its content');
        self::assertSame([], $result['entities'], 'an unmapped element produces NO entity — Telegram has no constructor for it');
    }

    /**
     * `<a>` WITHOUT `href` is still an anchor as far as the tag map is
     * concerned, so it still yields `messageEntityTextUrl` — but there is no
     * URL to carry, and the entity must NOT carry an empty one.
     *
     * Both halves of that are the `&&` at EntityParser.php:488. Flip it to
     * `||` and `getAttribute('href')` returns `''` for an attribute that is
     * not there, and the entity goes out with `url => ''` — a link to nowhere,
     * which Telegram renders as a clickable span.
     */
    /**
     * AN ANCHOR WITH NO `href` EMITS NO ENTITY (2026-10-18 — reversed).
     *
     * This test previously asserted the OPPOSITE and named it as correct:
     *
     *     '<a> still maps to messageEntityTextUrl'
     *     'an anchor with no href attribute has NO url'
     *
     * That pin was wrong, and it was mine — written in the html-tags lane while
     * measuring survivors, before the wire-shape question was asked at all. It
     * documented a defect as if it were a contract, which is worse than having no
     * test: it would have protected the defect from every future fix.
     *
     * Why it is a defect, from the committed schema rather than from opinion:
     *
     *     messageEntityTextUrl#76a6d327 offset:int length:int url:string
     *
     * `url:string` carries no `?`, so it is REQUIRED, and
     * `MessageEntityTextUrlData::__construct()` types it `public string $url`
     * with no default. An entity with no `url` therefore cannot be built:
     *
     *     TypeError: Argument #3 ($url) must be of type string, null given
     *
     * The entity TYPE is chosen by tag name while the required FIELD is supplied
     * separately by `elementExtras()`, which correctly returns [] for a missing
     * href. Nothing joined the two, so the parser produced an entity it could not
     * serialise.
     *
     * The text is still kept: the caller asked to parse, not to sanitise, so the
     * words inside the anchor are sent and only the entity is withheld.
     */
    public function test_anchor_without_href_yields_no_entity(): void
    {
        $result = EntityParser::htmlToEntities('<a>no href</a>');

        self::assertSame('no href', $result['text'], 'the anchor text is kept — parsing is not sanitising');
        self::assertSame([], $result['entities'], 'a link with no destination is not a link');
    }

    /**
     * The other half of the same `&&`: `href` is only ANCHOR business. A
     * formatting tag that happens to carry an `href` attribute must not have
     * it promoted to a link, because `messageEntityBold` has no `url` field on
     * the wire and Telegram rejects the entity as malformed.
     */
    public function test_non_anchor_element_carrying_href_is_not_given_a_url(): void
    {
        $result = EntityParser::htmlToEntities('<b href="https://t.example/x">y</b>');

        self::assertSame('y', $result['text'], 'the tag content is kept');
        self::assertCount(1, $result['entities'], 'the bold tag produces its one entity');
        self::assertSame('messageEntityBold', $result['entities'][0]['_'], 'a bold tag is bold, whatever attributes it carries');
        self::assertSame(1, $result['entities'][0]['length'], 'the entity covers its one character');

        self::assertArrayNotHasKey(
            'url',
            $result['entities'][0],
            'only an <a> may contribute a url — `$tagName === \'a\'` is the half of the `&&` that names the anchor, '
            .'and nothing observed it',
        );
    }

    /**
     * `document_id` is an INT64 on the wire
     * (`messageEntityCustomEmoji.document_id:long`), and the attribute it is
     * parsed from is a STRING. Two rows: one proving the cast happens, one
     * proving it is an int and not a string even when the digits are long
     * enough to look numeric.
     */
    public function test_tg_emoji_document_id_is_an_int_on_the_wire(): void
    {
        $short = EntityParser::htmlToEntities('<tg-emoji emoji-id="007">X</tg-emoji>');
        self::assertSame('messageEntityCustomEmoji', $short['entities'][0]['_'], 'the tag maps to a custom-emoji entity');
        self::assertSame(
            7,
            $short['entities'][0]['document_id'],
            'document_id is an int: the attribute arrives as the string "007", and an uncast value would ship "007" — '
            .'a string where the wire type is long',
        );

        // A real emoji document id, past the 32-bit range.
        $long = EntityParser::htmlToEntities('<tg-emoji emoji-id="5368324170671202286">X</tg-emoji>');
        self::assertSame(
            5368324170671202286,
            $long['entities'][0]['document_id'],
            'document_id survives as an int64 value, not a string',
        );
    }

    /**
     * `<tg-emoji` WITHOUT `emoji-id` is a custom-emoji tag whose required wire
     * field `document_id` is missing (`messageEntityCustomEmoji.document_id:long`
     * carries no `?`), so the entity must be DROPPED — the text it wrapped stays.
     *
     * `hasRequiredWireFields()` at EntityParser.php:535 guards every emitter
     * through `pushEntity()`. If the `'messageEntityCustomEmoji' => ['document_id']`
     * match arm were removed, this tag would emit an entity with no `document_id`
     * and the generated DTO would raise a TypeError on the wire. This test pins
     * the arm and kills the `MatchArmRemoval` mutant at EntityParser.php:537.
     */
    public function test_tg_emoji_without_emoji_id_yields_no_entity(): void
    {
        $result = EntityParser::htmlToEntities('<tg-emoji>X</tg-emoji>');

        self::assertSame('X', $result['text'], 'the emoji text is kept — parsing is not sanitising');
        self::assertSame([], $result['entities'], 'a custom emoji with no document id is not a custom emoji');
    }
}
