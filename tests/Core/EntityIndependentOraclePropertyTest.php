<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

/**
 * AN INDEPENDENT ORACLE + PROPERTY TEST for the Telegram entity wire contract.
 *
 * ## WHY THIS FILE EXISTS
 *
 * A real defect shipped here: `messageEntityPre` length INCLUDED trailing
 * whitespace, so Telegram rendered the trailing newlines *inside* the code
 * block. The old suite stayed GREEN through it. The reason is the named
 * "copying computed values" mistake — the suite recomputed its expected
 * values with the SAME production primitive it was meant to check
 * (`EntityParser::getUtf16Length()`). A bug in that primitive therefore
 * passed silently: the test and the code agreed because they were the same
 * computation.
 *
 * The cure is not a better hand-written case. It is an oracle that CANNOT be
 * wrong in the same way, plus a property stated over generated input.
 *
 * ## THE PROPERTY
 *
 * For generated text and the entities produced from it, for EVERY produced
 * entity `e`:
 *
 *     rtrim( substr_utf16(text, e.offset, e.length) ) === substr_utf16(text, e.offset, e.length)
 *
 * i.e. no produced entity's slice may end in whitespace. That is the rule at
 * core.telegram.org/api/entities, verbatim:
 *
 *   "the _length_ of an entity must not include the length of trailing
 *    newlines or whitespaces, `rtrim` entities before computing their length"
 *
 * Stating it as a property over a generator is what makes the file a *net*:
 * a per-type regression test only pins the types somebody already thought of.
 *
 * ## ⚠️ THE ORACLE IS NOT `mb_strlen($s, 'UTF-16')` — MEASURED, AND IT IS WRONG
 *
 * The obvious way to measure UTF-16 independently is `mb_strlen($s, 'UTF-16')`.
 * It does not work, and using it would have produced a confidently incorrect
 * oracle. MEASURED on PHP 8.3.33 + this mbstring:
 *
 *     mb_strlen("abc",            'UTF-16') === 2   // must be 3
 *     mb_strlen("hi \u{1F44D} there", 'UTF-16') === 7   // must be 11
 *
 * The returned value is `ceil(strlen($s) / 2)` — mbstring does not CONVERT
 * UTF-8 bytes into UTF-16, it REINTERPRETS the very same bytes as UTF-16 code
 * units. So it silently reports the wrong length for any string whose UTF-8
 * byte count is odd, and `mb_substr($s, 0, $n, 'UTF-16')` round-trips corruptly
 * (`mb_substr("hi \u{1F44D} there", 0, 7, 'UTF-16')` returns `hi ` + U+FFFD).
 * `UTF-16LE` and `UTF-16BE` misbehave identically. An oracle built on it would
 * have "verified" garbage and passed everything.
 *
 * So the oracle below is built the other way round: it walks CODE POINTS with
 * `mb_str_split($s, 1, 'UTF-8')` and applies the UTF-16 width rule by hand
 * (>= U+10000 costs 2 units, everything else costs 1). That is a structurally
 * different computation from production's `mb_convert_encoding(...)/strlen/2`,
 * it shares no primitive with it, and it is pinned against hand-computed
 * ground truth in `test_the_oracle_is_independently_correct` so it cannot
 * itself rot into a second copy of the code under test.
 *
 * ## ZERO REGEX
 *
 * No `preg_*()` anywhere: the oracle walks code points with `mb_str_split` /
 * `mb_ord`, and the whitespace check is `rtrim()` — the same default character
 * list production and the shipped fix use. Verified by grep; see the report.
 */
final class EntityIndependentOraclePropertyTest extends TestCase
{
    /** 😀 — astral, 2 UTF-16 units, 4 UTF-8 bytes. */
    private const EMOJI = "\u{1F600}";

    /** 👍 — a second astral char, so an emoji+emoji mix is 4 units. */
    private const THUMB = "\u{1F44D}";

    /** 🇷🇹 — TWO astral code points (regional indicators) = 4 UTF-16 units. */
    private const FLAG = "\u{1F1F7}\u{1F1F9}";

    /** U+200B ZWSP — BMP, 1 unit. The code-point vs code-unit contrast case. */
    private const ZWSP = "\u{200B}";

    /**
     * PAYLOADS. Every one is a body that an entity wrapper can wrap.
     *
     * The four shapes the generator must cover are marked:
     *   TRAILING-WS · ASTRAL · MIXED · EMPTY
     */
    private static function payloads(): array
    {
        return [
            // TRAILING-WS — the shape the shipped defect was made of.
            'trailing-space' => 'code ',
            'trailing-tab' => "code\t",
            'trailing-newline' => "code\n",
            'trailing-two-newlines' => "code\n\n",
            'only-whitespace' => ' ',
            // LEADING-WS — the OTHER half of the rule: legal, and the offset of
            // a LATER entity must still include it. Nothing here may regress.
            'leading-space' => ' code',
            'leading-newline' => "\ncode",
            // ASTRAL — 2 units, not 1.
            'astral' => self::EMOJI,
            'astral-trailing-space' => self::EMOJI.' ',
            'astral-two' => self::EMOJI.self::THUMB,
            'flag-two-astral' => self::FLAG,
            // MIXED — emoji AND text in one entity.
            'mixed' => 'a'.self::EMOJI.'b',
            'mixed-trailing-space' => 'a'.self::EMOJI.'b ',
            'mixed-zwsp' => 'a'.self::ZWSP.'b',
            'zwsp' => self::ZWSP,
            // EMPTY — rtrim is trivially satisfied; still must be exercised.
            'empty' => '',
            'empty-space' => '',
        ];
    }

    /**
     * MARKDOWN WRAPPERS. Every delimiter form that emits an entity.
     *
     * Note `messageEntityPre` is in here too: the SHIPPED fix rtrims only the
     * fenced-block branch. If the property still fails for the other wrappers,
     * that is the point — the fix was applied at one call site, not to the
     * rule.
     *
     * @return array<string, callable(string): string>
     */
    private static function markdownWrappers(): array
    {
        return [
            'bold' => static fn (string $p): string => '*'.$p.'*',
            'italic' => static fn (string $p): string => '_'.$p.'_',
            'strike' => static fn (string $p): string => '~'.$p.'~',
            'underline' => static fn (string $p): string => '__'.$p.'__',
            'spoiler' => static fn (string $p): string => '||'.$p.'||',
            'code-inline' => static fn (string $p): string => '`'.$p.'`',
            'pre-fenced' => static fn (string $p): string => "```\n".$p."\n```",
            'text-url' => static fn (string $p): string => '['.$p.'](https://t.example/x)',
            'custom-emoji' => static fn (string $p): string => '!['.$p.'](tg://emoji?id=7)',
        ];
    }

    /**
     * HTML WRAPPERS. Every tag the DOM path maps to an entity type.
     *
     * @return array<string, callable(string): string>
     */
    private static function htmlWrappers(): array
    {
        return [
            'b' => static fn (string $p): string => '<b>'.$p.'</b>',
            'i' => static fn (string $p): string => '<i>'.$p.'</i>',
            'u' => static fn (string $p): string => '<u>'.$p.'</u>',
            's' => static fn (string $p): string => '<s>'.$p.'</s>',
            'code' => static fn (string $p): string => '<code>'.$p.'</code>',
            'pre' => static fn (string $p): string => '<pre>'.$p.'</pre>',
            'a' => static fn (string $p): string => '<a href="https://t.example/x">'.$p.'</a>',
            'tg-emoji' => static fn (string $p): string => '<tg-emoji emoji-id="7">'.$p.'</tg-emoji>',
            'blockquote' => static fn (string $p): string => '<blockquote>'.$p.'</blockquote>',
            'tg-spoiler' => static fn (string $p): string => '<tg-spoiler>'.$p.'</tg-spoiler>',
        ];
    }

    /**
     * Surrounding plain text, so entity offsets are not all zero and the
     * "next offset must include preceding whitespace" half of the rule is under
     * load. Returns [prefix, suffix] pairs.
     *
     * @return list<array{0: string, 1: string}>
     */
    private static function decorations(): array
    {
        return [
            'none' => ['', ''],
            'space' => [' ', ' '],
            'newline' => ["\n", "\n"],
            'astral' => [self::EMOJI.' ', ' '.self::THUMB],
            'flag' => [self::FLAG.' ', ' '],
            'zwsp' => [self::ZWSP, self::ZWSP],
            'mixed' => ['a'.self::EMOJI." \n", "\n ".self::EMOJI.'b'],
        ];
    }

    /**
     * THE MARKDOWN CORPUS. Cross product of wrapper x payload x decoration,
     * plus the documents that yield genuinely EMPTY entities (the payload loop
     * cannot make those: `*` + `*` collapses to the `**` delimiter, and
     * `htmlToEntities` guards with `$length > 0`).
     *
     * @return array<string, string>
     */
    private static function markdownCorpus(): array
    {
        $corpus = [
            // The two that actually emit length-0 entities in the markdown path.
            'empty-code-inline' => '``',
            'empty-text-url' => '[](https://t.example/x)',
            'empty-custom-emoji' => '![](tg://emoji?id=7)',
        ];

        foreach (self::markdownWrappers() as $wName => $wrap) {
            foreach (self::payloads() as $pName => $payload) {
                foreach (self::decorations() as $dName => [$pre, $post]) {
                    $corpus["md/$wName/$pName/$dName"] = $pre.$wrap($payload).$post;
                }
            }
        }

        return $corpus;
    }

    /**
     * THE HTML CORPUS. Same cross product over the tag wrappers.
     *
     * @return array<string, string>
     */
    private static function htmlCorpus(): array
    {
        $corpus = [];

        foreach (self::htmlWrappers() as $wName => $wrap) {
            foreach (self::payloads() as $pName => $payload) {
                foreach (self::decorations() as $dName => [$pre, $post]) {
                    $corpus["html/$wName/$pName/$dName"] = $pre.$wrap($payload).$post;
                }
            }
        }

        return $corpus;
    }

    // ─────────────────────────────────────────────────────────────────────
    // THE ORACLE. Independent by construction; see the class docblock for the
    // measured proof that the mbstring UTF-16 route does not work.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * UTF-16 code-unit length, computed by walking code points.
     *
     * Deliberately NOT `mb_convert_encoding` + `strlen()/2` (that is production's
     * algorithm) and deliberately NOT `mb_strlen($s, 'UTF-16')` (that is wrong —
     * it returns ceil(strlen/2)).
     */
    private static function oracleUtf16Length(string $text): int
    {
        $units = 0;

        foreach (mb_str_split($text, 1, 'UTF-8') as $char) {
            $units += mb_ord($char, 'UTF-8') >= 0x10000 ? 2 : 1;
        }

        return $units;
    }

    /**
     * The slice of `$text` covering UTF-16 units [$offset, $offset+$length).
     */
    private static function oracleSlice(string $text, int $offset, int $length): string
    {
        if ($length <= 0) {
            return '';
        }

        $slice = '';
        $position = 0;
        $taken = 0;

        foreach (mb_str_split($text, 1, 'UTF-8') as $char) {
            $width = mb_ord($char, 'UTF-8') >= 0x10000 ? 2 : 1;

            // A code point contributes when it STARTS at or after $offset and
            // the slice is not already full.
            if ($position + $width > $offset && $taken < $length) {
                $slice .= $char;
                $taken += $width;
            }

            $position += $width;
        }

        return $slice;
    }

    /** Does UTF-16 index `$index` land inside a surrogate pair? */
    private static function oracleSplitsSurrogatePair(string $text, int $index): bool
    {
        $position = 0;

        foreach (mb_str_split($text, 1, 'UTF-8') as $char) {
            $width = mb_ord($char, 'UTF-8') >= 0x10000 ? 2 : 1;

            if ($position < $index && $index < $position + $width) {
                return true;
            }

            $position += $width;
        }

        return false;
    }

    /** Parsed corpus flattened to one row per entity, with the oracle applied. */
    private static function observe(string $parser, string $docName, string $doc): array
    {
        $result = $parser === 'markdown'
            ? EntityParser::markdownToEntities($doc)
            : EntityParser::htmlToEntities($doc);

        $text = $result['text'];
        $textUnits = self::oracleUtf16Length($text);
        $rows = [];

        foreach ($result['entities'] as $entity) {
            $offset = $entity['offset'];
            $length = $entity['length'];
            $slice = self::oracleSlice($text, $offset, $length);

            $rows[] = [
                'parser' => $parser,
                'doc' => $docName,
                'docRaw' => $doc,
                'type' => $entity['_'],
                'offset' => $offset,
                'length' => $length,
                'text' => $text,
                'textUnits' => $textUnits,
                'slice' => $slice,
                'sliceUnits' => self::oracleUtf16Length($slice),
                'rtrimStable' => rtrim($slice) === $slice,
                'inBounds' => $offset >= 0 && $length >= 0 && ($offset + $length) <= $textUnits,
                'alignsPairs' => ! self::oracleSplitsSurrogatePair($text, $offset)
                    && ! self::oracleSplitsSurrogatePair($text, $offset + $length),
            ];
        }

        return $rows;
    }

    /** Every entity from both corpora. */
    private static function allObservations(): array
    {
        $rows = [];

        foreach (self::markdownCorpus() as $name => $doc) {
            foreach (self::observe('markdown', $name, $doc) as $row) {
                $rows[] = $row;
            }
        }

        foreach (self::htmlCorpus() as $name => $doc) {
            foreach (self::observe('html', $name, $doc) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private static function show(array $row): string
    {
        return sprintf(
            '%s / %s: %s offset=%d length=%d text=%s slice=%s',
            $row['parser'], $row['doc'], $row['type'], $row['offset'], $row['length'],
            self::printable($row['text']), self::printable($row['slice'])
        );
    }

    /** Show whitespace and astral chars explicitly so a counterexample is legible. */
    private static function printable(string $s): string
    {
        return '"'.strtr($s, [
            ' ' => '␠', "\n" => '␊', "\t" => '␉', "\r" => '␍', "\0" => '␀',
            self::EMOJI => '<EMOJI>', self::THUMB => '<THUMB>', self::FLAG => '<FLAG>',
            self::ZWSP => '<ZWSP>',
        ]).'"';
    }

    // ─────────────────────────────────────────────────────────────────────
    // TESTS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * THE ORACLE IS PINNED FIRST. An oracle that is itself wrong is worse than
     * no oracle, because it launders a broken parser into a green suite — the
     * exact failure mode this file was written to kill.
     */
    public function test_the_oracle_is_independently_correct(): void
    {
        // Hand-computed UTF-16 unit counts. The emoji row is the trap:
        // 😀 is ONE code point, FOUR UTF-8 bytes, and TWO UTF-16 units.
        $lengths = [
            '' => 0,
            'abc' => 3,
            self::EMOJI => 2,
            'a'.self::EMOJI.'b' => 4,
            self::EMOJI.self::THUMB => 4,
            self::FLAG => 4,
            self::ZWSP => 1,
            'hi '.self::THUMB.' there' => 11,
            "\u{10FFFF}" => 2,
            "\n\n" => 2,
        ];

        foreach ($lengths as $text => $expected) {
            self::assertSame(
                $expected,
                self::oracleUtf16Length((string) $text),
                'oracle length wrong for '.self::printable((string) $text),
            );
        }

        // The oracle's slices, hand-computed.
        $slices = [
            ['abc', 0, 2, 'ab'],
            ['abc', 0, 0, ''],
            ['a'.self::EMOJI.'b', 0, 3, 'a'.self::EMOJI],
            ['a'.self::EMOJI.'b', 1, 2, self::EMOJI],
            ['a'.self::EMOJI.'b', 3, 1, 'b'],
            ['hi '.self::THUMB.' there', 0, 3, 'hi '],
            ['hi '.self::THUMB.' there', 3, 2, self::THUMB],
            // FLAG is TWO code points (regional indicators), each 2 units, so 4
            // in total — a slice that cannot tell [0,+2] (first indicator only)
            // from [0,+4] (the whole flag) is measuring bytes, not units.
            [self::FLAG, 0, 2, mb_str_split(self::FLAG, 1, 'UTF-8')[0]],
            [self::FLAG, 2, 2, mb_str_split(self::FLAG, 1, 'UTF-8')[1]],
            [self::FLAG, 0, 4, self::FLAG],
            [self::FLAG, 4, 0, ''],
        ];

        foreach ($slices as [$text, $offset, $length, $expected]) {
            self::assertSame(
                $expected,
                self::oracleSlice($text, $offset, $length),
                sprintf('oracle slice wrong for %s [%d,+%d]', self::printable($text), $offset, $length),
            );
        }

        // WHY the mbstring UTF-16 route was rejected, asserted so the reasoning
        // is executable rather than a comment somebody can delete.
        self::assertSame(
            2,
            mb_strlen('abc', 'UTF-16'),
            'mbstring mb_strlen(UTF-16) counts REINTERPRETED BYTES, so this '
            .'assertion documents the trap: it returns 2 for a 3-unit string.',
        );
        self::assertNotSame(
            mb_strlen('abc', 'UTF-16'),
            self::oracleUtf16Length('abc'),
            'The oracle and mbstring UTF-16 MUST disagree, or one of them is broken.',
        );
    }

    /**
     * THE GENERATOR IS NOT VACUOUS. A property test over a corpus that never
     * produced a trailing-whitespace entity, an astral slice, a mixed slice or
     * an empty entity would pass forever and mean nothing.
     *
     * ⚠️ THE TRAILING-WHITESPACE AND EMPTY SHAPES ARE GUARDED ON THE *INPUT*,
     * NOT ON THE OUTPUT. They used to be guarded like this:
     *
     *     $withTrailingWhitespace = array_filter($rows, fn ($r) => ! $r['rtrimStable']);
     *     self::assertNotSame([], $withTrailingWhitespace, '...');
     *     $empty = array_filter($rows, fn ($r) => $r['length'] === 0);
     *     self::assertNotSame([], $empty, '...');
     *
     * Both are the exact logical NEGATION of a property test in this same file:
     * one demands that the parser produce a trailing-whitespace entity, the
     * other demands that it produce a length-0 one. So the file could not go
     * green until both defects were fixed — the anti-vacuity guard was
     * DEMANDING the defect it exists to protect against. An anti-vacuity guard
     * written from the output is exactly the mistake this file was written to
     * kill (the suite agreeing with the code because it recomputed the code);
     * here it agreed because it had snapshotted the BUG.
     *
     * The corpus is the input, so the corpus is what gets guarded. These
     * assertions still fail the moment somebody deletes a payload from
     * `payloads()` or an empty-content document from `markdownCorpus()`, which
     * is the vacuity actually worth preventing.
     */
    public function test_the_generator_covers_every_required_shape(): void
    {
        $rows = self::allObservations();

        self::assertNotSame([], $rows, 'the generator produced no entities at all');

        $corpus = self::markdownCorpus() + self::htmlCorpus();

        // TRAILING-WS — the shape the shipped defect was made of.
        foreach (['trailing-space', 'trailing-tab', 'trailing-newline', 'trailing-two-newlines', 'only-whitespace'] as $shape) {
            self::assertArrayHasKey(
                'md/bold/'.$shape.'/none',
                $corpus,
                'the corpus no longer contains the "'.$shape.'" payload, so the '
                .'trailing-whitespace property is vacuous for that shape',
            );
        }

        // EMPTY — plus the three documents whose ENTIRE wrapped content is
        // empty. Those are the ones that actually emitted length-0 entities
        // before the fix, and asserting they now produce NO entity is strictly
        // stronger than asserting the corpus contains one: it pins the rule
        // itself at the three documents that motivated it.
        foreach (['empty', 'empty-space'] as $shape) {
            self::assertArrayHasKey(
                'md/code-inline/'.$shape.'/none',
                $corpus,
                'the corpus no longer contains the "'.$shape.'" payload, so the '
                .'zero-length property is vacuous for that shape',
            );
        }

        foreach (['empty-code-inline', 'empty-text-url', 'empty-custom-emoji'] as $docName) {
            self::assertArrayHasKey(
                $docName,
                $corpus,
                'the empty-content document "'.$docName.'" is gone from the corpus, '
                .'so nothing covers the empty-wrapper case any more',
            );

            self::assertSame(
                [],
                EntityParser::markdownToEntities($corpus[$docName])['entities'],
                'a document whose wrapped content is empty must produce NO entity at '
                .'all, not a length-0 one — Telegram answers ENTITY_BOUNDS_INVALID, '
                .'so the message fails to send',
            );
        }

        $astral = array_filter($rows, static fn (array $r): bool => $r['slice'] !== ''
            && (str_contains($r['slice'], self::EMOJI)
                || str_contains($r['slice'], self::THUMB)
                || str_contains($r['slice'], self::FLAG)));
        self::assertNotSame([], $astral, 'corpus must contain entity slices with astral-plane characters');

        $mixed = array_filter($rows, static function (array $r): bool {
            $hasAstral = str_contains($r['slice'], self::EMOJI) || str_contains($r['slice'], self::THUMB);
            $hasText = str_contains($r['slice'], 'a') || str_contains($r['slice'], 'b');

            return $hasAstral && $hasText;
        });
        self::assertNotSame([], $mixed, 'corpus must contain slices mixing emoji AND text');

        // The generator must reach EVERY entity type the parser can emit,
        // otherwise an untested branch could hide a violation. 10 is the whole
        // surface: mapTagToEntityType() has 13 tags, but strong/em/ins/del/
        // strike are aliases, and <a>/<tg-emoji> land on TextUrl/CustomEmoji
        // which the markdown corpus already covers.
        $expectedTypes = [
            'messageEntityBold', 'messageEntityItalic', 'messageEntityUnderline',
            'messageEntityStrike', 'messageEntitySpoiler', 'messageEntityCode',
            'messageEntityPre', 'messageEntityTextUrl', 'messageEntityCustomEmoji',
            'messageEntityBlockquote',
        ];
        $types = array_values(array_unique(array_column($rows, 'type')));

        self::assertSame(
            [],
            array_values(array_diff($expectedTypes, $types)),
            'the corpus never produced these entity types: '.implode(', ', array_diff($expectedTypes, $types)),
        );
        self::assertSame(
            [],
            array_values(array_diff($types, $expectedTypes)),
            'the corpus produced entity types outside the known surface: '.implode(', ', array_diff($types, $expectedTypes)),
        );
    }

    /**
     * Entities as [type, offset, length] triples, sorted by type.
     *
     * For assertions about WHICH entities exist rather than in which order —
     * the only honest form when the contract does not fix the order of entities
     * covering the identical span.
     *
     * @param  list<array<string, mixed>>  $entities
     * @return list<array{0: string, 1: int, 2: int}>
     */
    private static function entityTuplesSortedByType(array $entities): array
    {
        $tuples = array_map(
            static fn (array $e): array => [$e['_'], $e['offset'], $e['length']],
            $entities,
        );

        usort($tuples, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return $tuples;
    }

    /**
     * THE PROPERTY. For every produced entity, the slice named by its own
     * offset/length must be stable under `rtrim`.
     *
     * core.telegram.org/api/entities: "the length of an entity must not include
     * the length of trailing newlines or whitespaces, rtrim entities before
     * computing their length".
     */
    public function test_no_produced_entity_slice_ends_in_whitespace(): void
    {
        $violations = [];

        foreach (self::allObservations() as $row) {
            if (! $row['rtrimStable']) {
                $violations[] = $row;
            }
        }

        if ($violations === []) {
            self::assertTrue(true, 'no produced entity slice ends in whitespace');

            return;
        }

        // One representative per (parser, entity type) so the failure names the
        // broken branches, plus the true totals so nothing is understated.
        $representatives = [];

        foreach ($violations as $row) {
            $representatives[$row['parser'].'/'.$row['type']] ??= $row;
        }

        $message = sprintf(
            'THE rtrim RULE IS VIOLATED by %d of %d produced entities, across %d '
            ."branches (types: %s).\n"
            ."core.telegram.org/api/entities: an entity's length must exclude trailing "
            ."whitespace, or Telegram renders that whitespace INSIDE the formatted span.\n"
            ."Representative counterexample per branch:\n  %s",
            count($violations),
            count(self::allObservations()),
            count($representatives),
            implode(', ', array_keys($representatives)),
            implode("\n  ", array_map([self::class, 'show'], array_values($representatives))),
        );

        self::fail($message);
    }

    /**
     * GEOMETRY, cross-checked against the oracle rather than against itself.
     *
     * A `length` that disagrees with the UTF-16 width of the very slice it
     * names is incoherent no matter what the slice contains, so this is a
     * separate property from trailing whitespace — and unlike the old suite it
     * is computed without production's primitive.
     */
    public function test_entity_geometry_agrees_with_the_independent_oracle(): void
    {
        $bad = [];

        foreach (self::allObservations() as $row) {
            $problems = [];

            if ($row['sliceUnits'] !== $row['length']) {
                $problems[] = sprintf('length=%d but its own slice is %d units', $row['length'], $row['sliceUnits']);
            }

            if (! $row['inBounds']) {
                $problems[] = sprintf('offset+length=%d exceeds text length %d', $row['offset'] + $row['length'], $row['textUnits']);
            }

            if (! $row['alignsPairs']) {
                $problems[] = 'offset or offset+length splits a surrogate pair';
            }

            if ($problems !== []) {
                $bad[] = self::show($row).' -> '.implode('; ', $problems);
            }
        }

        self::assertSame([], $bad, "incoherent entity geometry:\n  ".implode("\n  ", $bad));
    }

    /**
     * THE SHIPPED FIX MUST STAY FIXED. The markdown fenced-block branch is the
     * one call site that rtrims today; a future refactor that moves the measure
     * back onto the untrimmed content has to fail HERE, not silently.
     */
    public function test_the_shipped_markdown_pre_block_fix_still_holds(): void
    {
        // Each row is [document, expected rtrimped slice, expected UTF-16
        // length of that slice]. Writing the SLICE out, not just the number, is
        // what keeps this a hand-derived expectation rather than a value copied
        // back out of the parser — the mistake this whole file exists to avoid.
        // The leading "\n" is INSIDE the fence in every case, so it counts.
        $cases = [
            ["```\ncode here\n\n```", "\ncode here", 10],
            ["```\ncode here \n```", "\ncode here", 10],
            ["```\ncode here\t```", "\ncode here", 10],
            ["```\ncode here```", "\ncode here", 10],
            // Language line "php" is stripped before measuring, so the measured
            // slice is "$x = 1;" — 7 units. Built by concatenation so the `$`
            // is unambiguous (in a double-quoted PHP string "\$" would vanish).
            ['```php'."\n".'$x = 1;'."\n".'```', '$x = 1;', 7],
            // "\n😀 here" is 10 UTF-8 BYTES but 8 UTF-16 UNITS. If this row
            // ever expects 10, the oracle has silently become a byte counter.
            ["```\n".self::EMOJI." here \n```", "\n".self::EMOJI.' here', 8],
        ];

        foreach ($cases as [$markdown, $expectedSlice, $expectedLength]) {
            self::assertSame(
                $expectedLength,
                self::oracleUtf16Length($expectedSlice),
                'the hand-written expectation itself is wrong for '.self::printable($expectedSlice),
            );

            $result = EntityParser::markdownToEntities($markdown);
            $pre = null;

            foreach ($result['entities'] as $entity) {
                if ($entity['_'] === 'messageEntityPre') {
                    $pre = $entity;
                }
            }

            self::assertNotNull($pre, 'no messageEntityPre produced for '.self::printable($markdown));
            self::assertSame(
                $expectedLength,
                $pre['length'],
                'regression on the shipped rtrim fix for '.self::printable($markdown),
            );

            $slice = self::oracleSlice($result['text'], $pre['offset'], $pre['length']);
            self::assertSame(
                $expectedSlice,
                $slice,
                'the entity slice must be exactly the rtrimmed content for '.self::printable($markdown),
            );
            self::assertSame(
                $slice,
                rtrim($slice),
                'pre slice must be rtrim-stable for '.self::printable($markdown),
            );
        }
    }

    /**
     * NESTED ENTITIES MUST COME OUT CONTAINER-FIRST.
     *
     * VERIFIED, NOT ASSUMED (2026-10-17). The Telegram docs do NOT state an
     * ordering: core.telegram.org/api/entities says only "Nested entities are
     * supported" and never mentions order. The contract therefore comes from
     * the two reference implementations the SAME PAGE names, which agree:
     *
     *   - tdlib, `MessageEntity::operator<` (td/telegram/MessageEntity.h):
     *     offset ascending, then `length > other.length` — the LONGER entity
     *     first. `check_is_sorted()` asserts that invariant wherever tdlib
     *     builds an entity list.
     *   - danog/telegram-entities, `Entities::toHtml()`: each entity's closing
     *     tag is PREPENDED at its offset, so the entity rendered last is closed
     *     last and the FIRST entity in the array is the OUTERMOST one.
     *
     * The defect: `traverseDomNode` recursed BEFORE pushing its own parent, so
     * HTML emitted nested entities inner-first, and markdown pushed at CLOSE
     * time with a stable sort, so a same-offset nesting was inner-first too.
     */
    public function test_nested_entities_come_out_container_before_content(): void
    {
        // (1) DIFFERENT OFFSETS — the plain case. "<b>a<i>b</i>c</b>" is text
        // "abc": bold covers all 3 units, italic the middle 1. Container first
        // means bold THEN italic; inner-first is the defect.
        $html = EntityParser::htmlToEntities('<b>a<i>b</i>c</b>');

        self::assertSame('abc', $html['text'], 'case 1 <b>a<i>b</i>c</b>: the extracted text is abc, with no tag residue');
        self::assertCount(2, $html['entities'], 'case 1: the nesting must emit exactly TWO entities, neither dropped nor duplicated');
        self::assertSame('messageEntityBold', $html['entities'][0]['_'], 'case 1: entity 0 of 2 must be the CONTAINER (bold, covering all 3 units) — inner-first is the defect');
        self::assertSame(0, $html['entities'][0]['offset'], 'case 1: the container starts at unit 0 of abc');
        self::assertSame(3, $html['entities'][0]['length'], 'bold covers all of "abc"');
        self::assertSame('messageEntityItalic', $html['entities'][1]['_'], 'case 1: entity 1 of 2 must be the CONTENT (italic, covering the middle unit)');
        self::assertSame(1, $html['entities'][1]['offset'], 'case 1: the inner italic starts at unit 1 of abc, not at 0');
        self::assertSame(1, $html['entities'][1]['length'], 'italic covers just "b"');

        // (2) SAME OFFSET, DIFFERENT LENGTH — the case an offset-only sort gets
        // wrong, and the reason the comparator cannot stop at the offset.
        // "<b><i>x</i> y</b>" is text "x y": bold is (0,3), italic is (0,1).
        // Both start at 0, so the tie is broken by LENGTH and the CONTAINER —
        // the longer one — must come first.
        $sameOffset = EntityParser::htmlToEntities('<b><i>x</i> y</b>');

        self::assertSame('x y', $sameOffset['text'], 'case 2 <b><i>x</i> y</b>: the extracted text is x y');
        self::assertCount(2, $sameOffset['entities'], 'case 2: the same-offset nesting must emit exactly TWO entities');
        self::assertSame(0, $sameOffset['entities'][0]['offset'], 'case 2: entity 0 of 2 starts at unit 0 — this case is only interesting if both offsets are 0');
        self::assertSame(0, $sameOffset['entities'][1]['offset'], 'this case is only interesting if the two offsets are equal');
        self::assertGreaterThan(
            $sameOffset['entities'][1]['length'],
            $sameOffset['entities'][0]['length'],
            'the FIRST entity must be the longer one — that is the container',
        );
        self::assertSame('messageEntityBold', $sameOffset['entities'][0]['_'], 'case 2: with both offsets equal, the LONGER entity (bold, 3 units) must be listed first');
        self::assertSame('messageEntityItalic', $sameOffset['entities'][1]['_'], 'case 2: the SHORTER entity (italic, 1 unit) must be listed second');

        // (3) THE PROPERTY, over the whole corpus: no entity may be preceded by
        // a strictly shorter entity that starts at the same offset. A
        // per-case test pins only the cases somebody already thought of.
        $inversions = [];

        foreach ([self::markdownCorpus(), self::htmlCorpus()] as $corpus) {
            foreach ($corpus as $docName => $doc) {
                $result = str_contains($docName, 'md/')
                    ? EntityParser::markdownToEntities($doc)
                    : EntityParser::htmlToEntities($doc);

                $previous = null;

                foreach ($result['entities'] as $entity) {
                    if ($previous !== null
                        && $previous['offset'] === $entity['offset']
                        && $previous['length'] < $entity['length']) {
                        $inversions[] = $docName.' emitted '.$previous['_']
                            .'('.$previous['offset'].','.$previous['length'].') before '
                            .$entity['_'].'('.$entity['offset'].','.$entity['length'].')';
                    }

                    $previous = $entity;
                }
            }
        }

        self::assertSame([], $inversions, "a contained entity came before its container:\n  ".implode("\n  ", $inversions));

        // (4) THE ONE CASE NO IMPLEMENTATION CAN ORDER — two entities covering
        // the IDENTICAL span. `<b><i>x</i></b>` makes bold and italic both
        // (0,1): neither contains the other, they coincide, so "container
        // first" has no referent and tdlib falls through to TYPE PRIORITY
        // there rather than nesting. Both must still be emitted and neither may
        // be dropped.
        $identical = EntityParser::htmlToEntities('<b><i>x</i></b>');

        self::assertSame('x', $identical['text'], 'case 4 <b><i>x</i></b>: the extracted text is x');
        self::assertCount(2, $identical['entities'], 'both entities must survive');
        // Sorted by type so the assertion does not itself impose an order the
        // contract does not define: what is pinned is that BOTH survive and
        // cover the identical span, not which one is listed first.
        self::assertSame(
            [
                ['messageEntityBold', 0, 1],
                ['messageEntityItalic', 0, 1],
            ],
            self::entityTuplesSortedByType($identical['entities']),
            'both entities must survive, each covering the identical single unit',
        );
    }

    /**
     * EMPTY ENTITIES. `htmlToEntities` guards with `if ($length > 0)`; the
     * markdown path guards only the two-char/single-char delimiter branches and
     * NOT the inline-code, text-url or custom-emoji branches, so a document like
     * "``" emits `messageEntityCode` with length 0.
     *
     * Telegram rejects that: an entity with length 0 fails with
     * ENTITY_BOUNDS_INVALID. Kept as its own test so this finding is never
     * conflated with the rtrim one — it is a different rule and a different
     * failure at the API.
     */
    public function test_no_entity_is_emitted_with_zero_length(): void
    {
        $zeroLength = [];

        foreach (self::allObservations() as $row) {
            if ($row['length'] === 0) {
                $zeroLength[] = $row;
            }
        }

        if ($zeroLength === []) {
            self::assertTrue(true, 'no zero-length entity emitted');

            return;
        }

        $representatives = [];

        foreach ($zeroLength as $row) {
            $representatives[$row['parser'].'/'.$row['type']] ??= $row;
        }

        self::fail(sprintf(
            '%d zero-length entities emitted, across %d branches (types: %s). '
            .'Telegram answers ENTITY_BOUNDS_INVALID for length 0, so these messages '
            ."fail to send rather than merely rendering wrong.\nRepresentative:\n  %s",
            count($zeroLength),
            count($representatives),
            implode(', ', array_keys($representatives)),
            implode("\n  ", array_map([self::class, 'show'], array_values($representatives))),
        ));
    }
}
