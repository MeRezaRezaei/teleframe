<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Entities;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Standard DOM-based HTML & MarkdownV2 Telegram MessageEntity Parser.
 * Uses PHP's native DOMDocument parser to handle nested and malformed tags cleanly.
 *
 * CONTRACT (W-17): both entity parsers share the same wire contract —
 * `{'_' => 'messageEntity*', offset, length}` in UTF-16 code units, with
 * `offset`/`length` as ints, `_` as the Telegram `MessageEntity` constructor
 * name; text always travels with its entities so the two can never drift.
 * This class is the DOM / MarkdownV2 token-stream side; the Message-layer
 * adapter (conventional `spans()`, `html()`/`markdown()` delegation) lives in
 * `MeRezaRezaei\Teleframe\Message\MarkdownEntityParser`. The Message layer's
 * class was renamed away from `EntityParser` so neither the engine nor
 * consumers can import the wrong one.
 */
class EntityParser
{
    /**
     * Converts HTML to plain text and Telegram MessageEntity structures using native DOMDocument.
     *
     * @return array{text: string, entities: list<array<string, mixed>>}
     */
    public static function htmlToEntities(string $html): array
    {
        if (trim($html) === '') {
            return ['text' => '', 'entities' => []];
        }

        // Wrap in HTML5 UTF-8 wrapper for DOMDocument
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return ['text' => strip_tags($html), 'entities' => []];
        }

        $plainText = '';
        $entities = [];
        $utf16Offset = 0;

        self::traverseDomNode($body, $plainText, $utf16Offset, $entities);

        return [
            'text' => $plainText,
            'entities' => self::sortEntities($entities),
        ];
    }

    /**
     * Converts Telegram Markdown / MarkdownV2 to plain text and MessageEntity structures using a deterministic token stream scanner.
     * Handles escaping, nesting, UTF-16 code units, pre blocks, custom emojis, and inline links without regex.
     *
     * @return array{text: string, entities: list<array<string, mixed>>}
     */
    public static function markdownToEntities(string $markdown): array
    {
        if (trim($markdown) === '') {
            return ['text' => '', 'entities' => []];
        }

        $chars = mb_str_split($markdown);
        $len = count($chars);
        $plainText = '';
        $utf16Offset = 0;
        $entities = [];
        $stack = [];

        $i = 0;
        while ($i < $len) {
            $c = $chars[$i];

            // 1. Escaped character (\x)
            if ($c === '\\' && $i + 1 < $len) {
                $nextChar = $chars[++$i];
                $plainText .= $nextChar;
                $utf16Offset += self::getUtf16Length($nextChar);
                $i++;

                continue;
            }

            // 2. Pre-formatted code block (```)
            if ($c === '`' && $i + 2 < $len && $chars[$i + 1] === '`' && $chars[$i + 2] === '`') {
                $fenceStart = $i;
                $i += 3;
                $lang = '';
                $closePos = null;
                for ($j = $i; $j + 2 < $len; $j++) {
                    if ($chars[$j] === '`' && $chars[$j + 1] === '`' && $chars[$j + 2] === '`') {
                        $closePos = $j;
                        break;
                    }
                }
                if ($closePos === null) {
                    // An UNCLOSED fence must not consume anything. The scan above
                    // advanced $i past the three backticks BEFORE proving a closing
                    // fence exists, so falling through from here silently ate two
                    // backticks and the first body character: "```abc" came out as
                    // "`bc". That is neither fidelity (the text is no longer the
                    // input) nor a rejection — Telegram's server rejects an
                    // unclosed fence outright, which is not what this parser does
                    // for any other malformed construct, it keeps the text literal.
                    //
                    // The whole RUN of backticks goes back into the text verbatim
                    // and the scan resumes past the run. Advancing by one instead
                    // would be wrong: on a run of four or more, the position after
                    // the first backtick still satisfies the three-character probe,
                    // so the fence branch would re-enter and emit the run twice —
                    // "````" became "``````". Resuming past the whole run is what
                    // guarantees both fidelity and forward progress.
                    $runEnd = $fenceStart;
                    while ($runEnd < $len && $chars[$runEnd] === '`') {
                        $runEnd++;
                    }
                    $run = implode('', array_slice($chars, $fenceStart, $runEnd - $fenceStart));
                    $plainText .= $run;
                    $utf16Offset += self::getUtf16Length($run);
                    $i = $runEnd;

                    continue;
                }

                // Reached only when $closePos !== null — the unclosed case above
                // continues past. Kept as a bare block so the branch is
                // structurally unconditional; reintroducing a null check here
                // would be dead code PHPStan correctly rejects as always-true.
                $blockContent = implode('', array_slice($chars, $i, $closePos - $i));
                if (str_contains($blockContent, "\n")) {
                    $firstLine = strstr($blockContent, "\n", true);
                    $ok = $firstLine !== false && $firstLine !== '' && strspn($firstLine, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-') === strlen($firstLine);
                    if ($ok) {
                        $lang = $firstLine;
                        $blockContent = substr($blockContent, strlen($firstLine) + 1);
                    }
                }
                $startOffset = $utf16Offset;
                $plainText .= $blockContent;

                // D6 (2026-10-17): the MEASURED length is computed inside
                // pushEntity(), which is the only caller of entityLength().
                // What this call site owns is the OTHER half of the rule —
                // the running offset advances over the UNTRIMMED content,
                // because the whitespace stays in the plain text and the
                // next entity's offset must include it.
                $utf16Offset += self::getUtf16Length($blockContent);
                self::pushEntity(
                    $entities,
                    'messageEntityPre',
                    $startOffset,
                    $blockContent,
                    $lang !== '' ? ['language' => $lang] : [],
                );
                $i = $closePos + 3;

                continue;
            }

            // 3. Inline fixed-width code (`)
            if ($c === '`') {
                $closePos = null;
                for ($j = $i + 1; $j < $len; $j++) {
                    if ($chars[$j] === '`') {
                        $closePos = $j;
                        break;
                    }
                }
                if ($closePos !== null) {
                    $codeContent = implode('', array_slice($chars, $i + 1, $closePos - ($i + 1)));
                    $startOffset = $utf16Offset;
                    $plainText .= $codeContent;
                    $utf16Offset += self::getUtf16Length($codeContent);
                    self::pushEntity($entities, 'messageEntityCode', $startOffset, $codeContent);
                    $i = $closePos + 1;

                    continue;
                }
            }

            // 4. Custom emoji (![alt](tg://emoji?id=123))
            if ($c === '!' && $i + 1 < $len && $chars[$i + 1] === '[') {
                $closeBracket = null;
                for ($j = $i + 2; $j < $len; $j++) {
                    if ($chars[$j] === ']') {
                        $closeBracket = $j;
                        break;
                    }
                }
                if ($closeBracket !== null && $closeBracket + 1 < $len && $chars[$closeBracket + 1] === '(') {
                    $closeParen = null;
                    for ($k = $closeBracket + 2; $k < $len; $k++) {
                        if ($chars[$k] === ')') {
                            $closeParen = $k;
                            break;
                        }
                    }
                    if ($closeParen !== null) {
                        $altText = implode('', array_slice($chars, $i + 2, $closeBracket - ($i + 2)));
                        $url = implode('', array_slice($chars, $closeBracket + 2, $closeParen - ($closeBracket + 2)));
                        if (str_starts_with($url, 'tg://emoji?id=')) {
                            $emojiId = substr($url, 14);

                            // `(int)` is NOT validation (2026-10-18).
                            //
                            // `(int)"abc"` is 0, and `(int)"9abc"` is 9. Both were
                            // emitted silently as a `document_id`, which is an int64
                            // document reference — and this parser runs on
                            // USER-SUPPLIED text, so
                            //
                            //     ![a](tg://emoji?id=abc)
                            //
                            // is one keystroke away from any sender. The library
                            // produced an entity pointing at document 0, or at a
                            // number the user typed, with no error.
                            //
                            // A valid id is a non-negative decimal integer that fits
                            // in int64. Anything else is not a custom-emoji
                            // reference, so the construct is left as literal text
                            // rather than turned into an entity that means nothing.
                            //
                            // THE INDEX MUST STILL ADVANCE. A bare `continue` here
                            // hangs the parser forever: the branch owns the whole
                            // `![alt](...)` run, so without `$i = $closeParen + 1`
                            // the scan re-enters this branch on the same `!` for
                            // ever. Measured: `![a](tg://emoji?id=9223372036854775808)`
                            // exited 124 (timeout) when this was a bare `continue`.
                            //
                            // Falling THROUGH instead is what makes the whole
                            // construct literal text: the `!` is appended by the
                            // plain-text branch at the bottom of the loop, and `[`
                            // is then re-scanned by the link branch.
                            if (self::isValidEmojiDocumentId($emojiId)) {
                                $startOffset = $utf16Offset;
                                $plainText .= $altText;
                                $utf16Offset += self::getUtf16Length($altText);
                                self::pushEntity(
                                    $entities,
                                    'messageEntityCustomEmoji',
                                    $startOffset,
                                    $altText,
                                    ['document_id' => (int) $emojiId],
                                );
                                $i = $closeParen + 1;

                                continue;
                            }
                        }
                    }
                }
            }

            // 5. Inline links ([text](url))
            if ($c === '[') {
                $closeBracket = null;
                for ($j = $i + 1; $j < $len; $j++) {
                    if ($chars[$j] === '\\') {
                        $j++;

                        continue;
                    }
                    if ($chars[$j] === ']') {
                        $closeBracket = $j;
                        break;
                    }
                }
                if ($closeBracket !== null && $closeBracket + 1 < $len && $chars[$closeBracket + 1] === '(') {
                    $closeParen = null;
                    for ($k = $closeBracket + 2; $k < $len; $k++) {
                        if ($chars[$k] === '\\') {
                            $k++;

                            continue;
                        }
                        if ($chars[$k] === ')') {
                            $closeParen = $k;
                            break;
                        }
                    }
                    if ($closeParen !== null) {
                        $linkText = implode('', array_slice($chars, $i + 1, $closeBracket - ($i + 1)));
                        $url = implode('', array_slice($chars, $closeBracket + 2, $closeParen - ($closeBracket + 2)));
                        $startOffset = $utf16Offset;
                        $plainText .= $linkText;
                        $utf16Offset += self::getUtf16Length($linkText);
                        self::pushEntity(
                            $entities,
                            'messageEntityTextUrl',
                            $startOffset,
                            $linkText,
                            ['url' => $url],
                        );
                        $i = $closeParen + 1;

                        continue;
                    }
                }
            }

            // 6. Two-char delimiters: || (spoiler), __ (underline), ** (bold)
            $twoChar = ($i + 1 < $len) ? ($c.$chars[$i + 1]) : '';
            if ($twoChar === '||' || $twoChar === '__' || $twoChar === '**') {
                $type = match ($twoChar) {
                    '||' => 'messageEntitySpoiler',
                    '__' => 'messageEntityUnderline',
                    '**' => 'messageEntityBold',
                };
                $matchedIdx = null;
                for ($k = count($stack) - 1; $k >= 0; $k--) {
                    if ($stack[$k]['delim'] === $twoChar) {
                        $matchedIdx = $k;
                        break;
                    }
                }
                if ($matchedIdx !== null) {
                    $entry = $stack[$matchedIdx];
                    array_splice($stack, $matchedIdx, 1);
                    self::pushEntity(
                        $entities,
                        $entry['type'],
                        $entry['start_offset'],
                        substr($plainText, $entry['start_byte']),
                    );
                } else {
                    $stack[] = [
                        'delim' => $twoChar,
                        'type' => $type,
                        'start_offset' => $utf16Offset,
                        'start_byte' => strlen($plainText),
                    ];
                }
                $i += 2;

                continue;
            }

            // 7. Single-char delimiters: *, _, ~
            if ($c === '*' || $c === '_' || $c === '~') {
                $type = match ($c) {
                    '*' => 'messageEntityBold',
                    '_' => 'messageEntityItalic',
                    '~' => 'messageEntityStrike',
                };
                $matchedIdx = null;
                for ($k = count($stack) - 1; $k >= 0; $k--) {
                    if ($stack[$k]['delim'] === $c) {
                        $matchedIdx = $k;
                        break;
                    }
                }
                if ($matchedIdx !== null) {
                    $entry = $stack[$matchedIdx];
                    array_splice($stack, $matchedIdx, 1);
                    self::pushEntity(
                        $entities,
                        $entry['type'],
                        $entry['start_offset'],
                        substr($plainText, $entry['start_byte']),
                    );
                } else {
                    $stack[] = [
                        'delim' => $c,
                        'type' => $type,
                        'start_offset' => $utf16Offset,
                        'start_byte' => strlen($plainText),
                    ];
                }
                $i++;

                continue;
            }

            // 8. Plain text character
            $plainText .= $c;
            $utf16Offset += self::getUtf16Length($c);
            $i++;
        }

        // Canonical order — see sortEntities() for why offset ASC is not enough.
        $entities = self::sortEntities($entities);

        return [
            'text' => $plainText,
            'entities' => $entities,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $entities
     */
    protected static function traverseDomNode(DOMNode $node, string &$plainText, int &$utf16Offset, array &$entities): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $text = $child->nodeValue ?? '';
                $plainText .= $text;
                $utf16Offset += self::getUtf16Length($text);
            } elseif ($child instanceof DOMElement) {
                $tagName = strtolower($child->tagName);
                $startOffset = $utf16Offset;
                // The BYTE position as well as the UTF-16 offset: the content an
                // entity covers has to be handed to pushEntity() so the rtrim
                // rule can be applied to it. $plainText only ever grows by
                // appending, so a byte offset taken before the recursion still
                // indexes exactly this element's text afterwards.
                $startByte = strlen($plainText);

                // Recursively parse child nodes
                self::traverseDomNode($child, $plainText, $utf16Offset, $entities);

                $entityType = self::mapTagToEntityType($tagName);

                if ($entityType !== null) {
                    self::pushEntity(
                        $entities,
                        $entityType,
                        $startOffset,
                        substr($plainText, $startByte),
                        self::elementExtras($child, $tagName),
                    );
                }
            }
        }
    }

    /**
     * THE ONLY WAY AN ENTITY LEAVES THIS CLASS.
     *
     * Two invariants hold here by construction, rather than by 18 emitting
     * sites each having to remember them:
     *
     *   1. `length` EXCLUDES trailing whitespace (entityLength).
     *   2. `length` is NEVER 0.
     *
     * (2) is not redundant with the `$length > 0` guards the delimiter
     * branches used to carry: rtrim can CREATE a zero-length span out of a
     * non-empty one, because `rtrim(" \n ")` is `""`. The fenced block
     * "```\n \n```" measured 2 units before the rule existed and measures 0
     * after it, so a guard written against the untrimmed width stops being a
     * guard exactly where the rule starts to bite. Telegram answers
     * ENTITY_BOUNDS_INVALID for a zero-length entity, so this is a message
     * that fails to send, not one that renders oddly.
     *
     * @param  list<array<string, mixed>>  $entities
     * @param  array<string, mixed>  $extras  type-specific wire fields
     */
    private static function pushEntity(array &$entities, string $type, int $offset, string $content, array $extras = []): void
    {
        $length = self::entityLength($content);

        if ($length <= 0) {
            return;
        }

        // 3. THE ENTITY'S REQUIRED WIRE FIELDS ARE PRESENT. (2026-10-18)
        //
        // Two invariants held here by construction before: length excludes trailing
        // whitespace, and length is never 0. This is the third, and it is the one
        // that a bare `<a>` slipped through.
        //
        // `htmlToEntities('<a>t</a>')` used to emit a `messageEntityTextUrl` with
        // NO `url` key. That is not a rendering question — the committed TL says
        //     messageEntityTextUrl#76a6d327 offset:int length:int url:string
        // and `url:string` carries no `?`, so it is REQUIRED, and the generated
        // DTO types it `public string $url` with no default. Building that DTO
        // raises
        //     TypeError: Argument #3 ($url) must be of type string, null given
        //
        // The cause is that the entity TYPE is chosen by tag name at :390 while
        // the required FIELD is supplied separately by `elementExtras()`, which
        // correctly returns [] for a missing href. Nothing joined the two.
        //
        // Why this check lives HERE rather than at the call site: `pushEntity()`
        // is the only way an entity leaves this class, so one guard covers every
        // emitting site. The full required-field map, read off the committed
        // `src/Schema/schema/sources/api.tl`, is below — five constructors carry a
        // non-flagged field, and this parser can currently emit three of them.
        if (! self::hasRequiredWireFields($type, $extras)) {
            // Dropped, not defaulted. A link with no destination is not a link, and
            // defaulting url to '' would make a broken anchor silently valid. The
            // text the anchor wrapped is already in `$plainText`, so the caller
            // still sends the words — only the entity is withheld.
            return;
        }

        $entities[] = ['_' => $type, 'offset' => $offset, 'length' => $length] + $extras;
    }

    /**
     * Whether `$extras` carries every REQUIRED (non-flagged) wire field of `$type`.
     *
     * THE MAP IS READ OFF THE COMMITTED SCHEMA, narrowed to what this parser can
     * actually emit. From `src/Schema/schema/sources/api.tl`, the MessageEntity
     * constructors that declare a field without a `?`:
     *
     *   messageEntityCustomEmoji   document_id:long   ← emitted, guarded below
     *   messageEntityDiffReplace   old_text:string    ← no tag maps to it
     *   messageEntityMentionName   user_id:long       ← no tag maps to it
     *   messageEntityPre           language:string    ← see the note below
     *   messageEntityTextUrl       url:string         ← emitted, guarded below
     *
     * `messageEntityFormattedDate` also has `date:int` unflagged; no tag maps to it.
     *
     * **`language` is DELIBERATELY NOT in the map, and that took a red suite to
     * establish.** The fence callsite passes `[]` when the language line is absent
     * (`$lang !== '' ? ['language' => $lang] : []`), so a first attempt at this
     * guard listed `language` as required and dropped every language-less code
     * block — 21 failures across `EntityFenceCloseScanTest` and
     * `EntityDelimiterStackBoundaryTest`.
     *
     * Measuring why that was wrong: for "```a```" the block content is non-empty
     * and `$lang` is `''`, so the entity is built with `[]`... and is then dropped
     * anyway, because the zero-length rule already refuses it. Measured: a
     * language-less fence produces NO entity at all. `language` can therefore never
     * be observed missing on an emitted entity, and guarding it here would change
     * shipped behaviour on the strength of a schema field that is unreachable.
     *
     * `messageEntityBlockquote` is the counter-example that proves the flag matters:
     * `collapsed:flags.0?true` IS flagged, so a blockquote without it is valid and
     * must NOT be dropped.
     *
     * @param  array<string, mixed>  $extras
     */
    private static function hasRequiredWireFields(string $type, array $extras): bool
    {
        $required = match ($type) {
            'messageEntityCustomEmoji' => ['document_id'],
            'messageEntityTextUrl' => ['url'],
            'messageEntityDiffReplace' => ['old_text'],
            'messageEntityMentionName' => ['user_id'],
            default => [],
        };

        foreach ($required as $field) {
            if (! array_key_exists($field, $extras)) {
                return false;
            }
        }

        return true;
    }

    /**
     * THE rtrim RULE (D6, 2026-10-17), as a RULE rather than a call site.
     *
     * core.telegram.org/api/entities, verbatim:
     *   "the _length_ of an entity must not include the length of trailing
     *    newlines or whitespaces, `rtrim` entities before computing their
     *    length: however, the next _offset_ must include the length of
     *    newlines or whitespaces that precede it."
     *
     * The rule has TWO halves and conflating them breaks the message either
     * way:
     *
     *   entityLength($content)     the MEASURED length — trailing whitespace
     *                              EXCLUDED. This is what goes on the wire.
     *   getUtf16Length($content)  the CONSUMED width — UNTRIMMED, because the
     *                              whitespace stays in the plain text and the
     *                              NEXT entity's offset must include it.
     *
     * Trimming the running offset instead would satisfy this rule and shift
     * every later entity in the message.
     *
     * MEASURED (2026-10-17): the rule was implemented at ONE of the 18
     * emitting sites — the markdown fenced block — and 882 of 2054 entities
     * produced across a wrapper x payload x decoration corpus violated it, in
     * 18 branches, including the entire HTML path and `pre` itself. That is
     * the shape of a defect that lives at call sites: the next branch added
     * quietly reintroduces it. It is a property of every entity, so it is a
     * property of ONE function now.
     */
    private static function entityLength(string $content): int
    {
        return self::getUtf16Length(rtrim($content));
    }

    /**
     * CANONICAL ENTITY ORDER: offset ASC, then length DESC — the CONTAINER
     * before the content it contains.
     *
     * This is NOT a rule stated in Telegram's documentation:
     * core.telegram.org/api/entities says only "Nested entities are supported"
     * and never mentions ordering. It is load-bearing because it is the order
     * BOTH reference implementations Telegram names on that page produce:
     *
     *   - tdlib, `MessageEntity::operator<` (td/telegram/MessageEntity.h):
     *     offset ascending, then `length > other.length` — LONGER FIRST — and
     *     `check_is_sorted()` asserts the invariant everywhere it builds an
     *     entity list.
     *   - danog/telegram-entities `Entities::toHtml()` PREPENDS each entity's
     *     closing tag at its offset, so the entity rendered last is closed
     *     last and the FIRST entity in the array is the outermost one.
     *
     * Neither emission path can produce that order on its own. `traverseDomNode`
     * recurses BEFORE it knows its own length, so HTML emits nested entities
     * inner-first (`<b>a<i>b </i></b>` came out italic then bold); markdown
     * pushes at CLOSE time, so `**_x_**` — same offset — also came out
     * inner-first. Sorting once, on the way out, makes the order a property of
     * the OUTPUT instead of a property of 18 traversal paths each getting it
     * right. `usort` is stable, so entities covering the identical span keep
     * their relative order, which is the one case where no order can be wrong:
     * they format exactly the same text.
     *
     * @param  list<array<string, mixed>>  $entities
     * @return list<array<string, mixed>>
     */
    private static function sortEntities(array $entities): array
    {
        usort(
            $entities,
            static fn (array $a, array $b): int => [$a['offset'], -$a['length']] <=> [$b['offset'], -$b['length']],
        );

        return $entities;
    }

    /**
     * The type-specific wire fields of a DOM element: `url` for `<a href>`,
     * `document_id` for `<tg-emoji emoji-id>`. Split out so traverseDomNode
     * reads as measure-then-emit with no branching in the middle of it.
     *
     * @return array<string, mixed>
     */
    private static function elementExtras(DOMElement $element, string $tagName): array
    {
        if ($tagName === 'a' && $element->hasAttribute('href')) {
            return ['url' => $element->getAttribute('href')];
        }

        if ($tagName === 'tg-emoji' && $element->hasAttribute('emoji-id')) {
            return ['document_id' => (int) $element->getAttribute('emoji-id')];
        }

        return [];
    }

    protected static function mapTagToEntityType(string $tagName): ?string
    {
        return match ($tagName) {
            'b', 'strong' => 'messageEntityBold',
            'i', 'em' => 'messageEntityItalic',
            'u', 'ins' => 'messageEntityUnderline',
            's', 'strike', 'del' => 'messageEntityStrike',
            'code' => 'messageEntityCode',
            'pre' => 'messageEntityPre',
            'tg-spoiler' => 'messageEntitySpoiler',
            'blockquote' => 'messageEntityBlockquote',
            'a' => 'messageEntityTextUrl',
            'tg-emoji' => 'messageEntityCustomEmoji',
            default => null,
        };
    }

    /**
     * Is `$id` a usable `messageEntityCustomEmoji.document_id`?
     *
     * The field is `long` (int64) and non-flagged in
     * `src/Schema/schema/sources/api.tl`, and it is a reference to a document a
     * real account can see. A valid id is therefore:
     *
     *   - non-empty;
     *   - digits ONLY — `(int)"9abc"` is 9 and `(int)"abc"` is 0, so a cast is
     *     never a validation;
     *   - non-negative — a negative document reference is not a thing, and
     *     `(int)"-5"` is -5;
     *   - within int64 — the longest value a `long` can hold is 9223372036854775807.
     *
     * `ctype_digit()` is used rather than a character-class loop because it is the
     * one predicate that answers exactly this and needs no regex (this package is
     * zero-regex by contract).
     */
    private static function isValidEmojiDocumentId(string $id): bool
    {
        if ($id === '' || ! ctype_digit($id)) {
            return false;
        }

        // Compare as strings first: an id longer than 19 digits cannot fit in
        // int64 regardless of its value, and comparing numerically on a longer
        // string would be the very cast this method exists to avoid.
        $max = '9223372036854775807';
        if (strlen($id) !== strlen($max)) {
            return strlen($id) < strlen($max);
        }

        return strcmp($id, $max) <= 0;
    }

    /**
     * Calculates string length in UTF-16 code units (surrogate pair precision).
     */
    public static function getUtf16Length(string $text): int
    {
        $utf16 = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');

        return strlen($utf16) / 2;
    }
}
