<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

use PHPUnit\Framework\TestCase;

/**
 * A doc claim about the backup driver's accepted set, its default, or how many
 * of the accepted drivers are durable must not disagree with the code.
 *
 * WHY THIS EXISTS. Four of the five defects this gate was written for were
 * found BY HAND, and a hand-found instance proves the hand sweep was
 * incomplete (D-010). `docs/backup.md`, `docs/quickstart.md` and
 * `docs/usage/store-backup.md` all shipped "driver 'memory' (default)" while
 * the shipped config says `env('TELEFRAME_BACKUP_DRIVER', 'telegram')`, and
 * all three said `telegram` was the ONLY durable driver while `s3` is a
 * second one. Every one of those lines sends a host to a config value that
 * does the opposite of what the doc promised.
 *
 * WHAT IS DERIVED, NEVER TRANSCRIBED. Nothing in this file names a driver.
 * The accepted set is parsed out of the provider's own refusal message, the
 * default out of the shipped config, the volatile driver out of the branch
 * that constructs `InMemoryVault`, and the set of config keys out of the
 * shipped config — so a rename or a new driver changes what this gate checks
 * with no edit here, and a stale copy of a driver name cannot survive here
 * to mislead a reader somewhere else.
 *
 * WHY THE VERBATIM EXEMPTION IS NARROW. The operator's own words name
 * `memory` as the shipped default (`.brain/verbatim/2026-09-26-notes.md:3702`
 * quotes the exact `'driver' => env('TELEFRAME_BACKUP_DRIVER', 'memory')`
 * line, and line 3704 says "the default must not be a lie"), and name
 * `ftp`/`rsync` as transports (`.brain/verbatim/2026-09-24-…:38-41`). A
 * verbatim is REPORTED speech, not a doc claim: reproducing it is how the
 * record stays honest, and a gate that failed on it would train the operator
 * to disable the gate, which is strictly worse than having no gate. So a
 * line is exempt when it is a blockquote (`>`) or carries an ATTRIBUTION —
 * `(verbatim`, `[verbatim`, `.brain/verbatim`, `verbatim 20`. It is NOT
 * exempt for the bare adjective: "wins verbatim", "printed verbatim" and
 * "forwarded verbatim" appear all over `docs/` and exempting those would let
 * a lie hide behind an ordinary English word. `test_it_stays_silent_on_the_
 * verbatim` pins both directions with the operator's real sentences.
 *
 * SCOPE, STATED HONESTLY. This is a claim detector, not a prose parser. It
 * fires on a driver VALUE bound to a claim — a default, an accepted set, a
 * sole-durable claim. It cannot read a sentence that names the wrong driver
 * without binding it to one of those, and it deliberately does not try: a
 * gate that cries wolf is switched off. Every pattern here was written because
 * a real doc line or a real verbatim line demanded it, and the coarse versions
 * that were tried first (co-occurrence, "only" + "durable" anywhere on a line)
 * were measured against the corpus and removed for firing on correct docs.
 * `docs/decisions.md` and the generated schema docs are scanned too; the
 * verbatim exemption is what keeps the former honest rather than an exclusion
 * list.
 *
 * REGEX. This file uses `preg_*`, which the repo bans in `src/Teleframe` and
 * `src/Core` and phpstan therefore analyses with `disallowedFunctionCalls`.
 * Tests are outside those analysis paths, 19 test files here already use
 * `preg_*`, and the ten patterns below are adjacency grammars that string
 * functions would express far less legibly. Stated here so it reads as a
 * decision rather than an oversight.
 */
final class BackupDriverDocClaimsTest extends TestCase
{
    private const PROVIDER = 'src/Laravel/Providers/TeleframeServiceProvider.php';

    private const CONFIG = 'src/Laravel/config/teleframe.php';

    /**
     * Tokens a default-claim pattern can capture that name no config value:
     * "never the default" captures `never`, "default to the real driver"
     * captures `the`, "`schedule.enabled` defaults to `true`" captures `true`.
     * Without this list each becomes a "prescribed driver", which is how a gate
     * starts crying wolf and gets switched off.
     *
     * @var list<string>
     */
    private const NOT_A_CONFIG_VALUE = [
        'the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'is', 'are', 'it',
        'its', 'this', 'that', 'these', 'those', 'either', 'neither', 'both',
        'never', 'not', 'only', 'same', 'other', 'another', 'real', 'actual',
        'current', 'new', 'old', 'prior', 'previous', 'original', 'shipped',
        'default', 'defaults', 'driver', 'drivers', 'one', 'two', 'three',
        'first', 'last', 'own', 'whole', 'entire', 'single', 'every', 'each',
        'true', 'false', 'null', 'none', 'off', 'unset', 'empty', 'empty string',
    ];

    /**
     * The pipe-separated accepted set, parsed from the provider's own refusal.
     *
     * `unknown backup driver "x" — expected memory|telegram|s3.` is the code
     * stating its contract in the one place it cannot be misread as a doc, so
     * that string is the source of truth for the accepted set.
     *
     * @return list<string>
     */
    public static function acceptedDrivers(): array
    {
        $source = self::source(self::PROVIDER);
        $sets = [];
        $offset = 0;

        while (($at = strpos($source, 'unknown backup driver', $offset)) !== false) {
            $tail = substr($source, $at);
            $expected = strpos($tail, 'expected ');
            if ($expected !== false) {
                $list = substr($tail, $expected + strlen('expected '));
                $list = substr($list, 0, strcspn($list, '."'));
                $parsed = self::parsePipeList($list);
                if ($parsed !== []) {
                    $sets[] = $parsed;
                }
            }
            $offset = $at + strlen('unknown backup driver');
        }

        self::assertNotSame(
            [],
            $sets,
            self::PROVIDER.' no longer names the accepted backup driver set in a'
            .' "unknown backup driver ... expected a|b|c" refusal. If that message'
            .' was reworded, fix THIS derivation — do not transcribe the set into it.',
        );

        // Two different accepted sets in one provider is a contradiction in the
        // code itself, and a derived "truth" that disagrees with itself is not
        // a truth this gate may compare docs against.
        $distinct = [];
        foreach ($sets as $set) {
            $distinct[implode('|', $set)] = true;
        }
        self::assertCount(
            1,
            $distinct,
            self::PROVIDER.' names '.count($distinct).' different accepted backup driver'
            .' sets ('.implode(' / ', array_keys($distinct)).'). The code contradicts itself;'
            .' no doc claim can be adjudicated against it.',
        );

        $first = $sets[0];
        foreach ($sets as $set) {
            self::assertSame($first, $set);
        }

        return $first;
    }

    /**
     * The shipped default, from `src/Laravel/config/teleframe.php` — the file a
     * host actually publishes — and cross-checked against the provider's own
     * fallback literal, because the two drifting apart is itself a defect.
     */
    public static function defaultDriver(): string
    {
        $block = self::backupConfigBlock();
        $at = strpos($block, "'driver' =>");
        self::assertNotFalse($at, self::CONFIG." declares no 'driver' => inside 'backup' => [.");

        $envAt = strpos($block, 'env(', $at);
        self::assertNotFalse($envAt, "the backup 'driver' key is not an env() read; fix THIS derivation.");

        $args = substr($block, $envAt + 4, strcspn(substr($block, $envAt + 4), ')'));
        $parts = array_map('trim', explode(',', $args));
        self::assertGreaterThanOrEqual(
            2,
            count($parts),
            self::CONFIG."'s backup driver env() read carries no default; fix THIS derivation.",
        );

        $fromConfig = trim($parts[1], "'\"");

        $fromProvider = self::providerFallbackDefault();
        self::assertSame(
            $fromProvider,
            $fromConfig,
            'The shipped config default ('.$fromConfig.') and the provider\'s own fallback'
            .' ('.self::PROVIDER.') are different backup drivers. A doc cannot be right'
            .' about the default while the two code paths disagree about it.',
        );

        return $fromConfig;
    }

    /**
     * The drivers whose vault is process-local: the branches that construct an
     * `InMemoryVault`. Derived from the code, so "which driver is volatile" is
     * never a claim this file makes on its own.
     *
     * @return list<string>
     */
    public static function volatileDrivers(): array
    {
        $source = self::source(self::PROVIDER);

        // Every `$driver === 'x'` / `$driver !== 'x'` branch, in order, with the
        // name and where its body starts. One pass: a body runs to the next
        // branch, and a body that constructs an `InMemoryVault` is a driver that
        // cannot survive the process — derived, never asserted here.
        $branches = [];
        $offset = 0;
        while (($branch = self::nextDriverBranch($source, $offset)) !== null) {
            [$name, $at, $bodyStart] = $branch;
            $branches[] = [$name, $at, $bodyStart];
            $offset = $bodyStart;
        }

        $volatile = [];
        foreach ($branches as $index => [$name, , $bodyStart]) {
            $end = $branches[$index + 1][1] ?? strlen($source);
            if ($name !== null && str_contains(substr($source, $bodyStart, $end - $bodyStart), 'new InMemoryVault')) {
                $volatile[] = $name;
            }
        }

        self::assertNotSame(
            [],
            $volatile,
            self::PROVIDER.' constructs no InMemoryVault, so no driver is derivably'
            .' volatile and the sole-durable rule below would be vacuous. If the'
            .' offline vault moved, fix THIS derivation.',
        );

        return array_values(array_unique($volatile));
    }

    /**
     * Every config key the shipped config declares, so a claim about a DIFFERENT
     * key (`chunk_size` defaults to 4194304) is not mistaken for a claim about
     * the driver. Derived for the same reason as everything else here.
     *
     * @return list<string>
     */
    public static function declaredConfigKeys(): array
    {
        $source = self::source(self::CONFIG);
        $keys = [];
        $offset = 0;

        while (($at = strpos($source, "'", $offset)) !== false) {
            $close = strpos($source, "'", $at + 1);
            if ($close === false) {
                break;
            }
            $token = substr($source, $at + 1, $close - $at - 1);
            $after = substr($source, $close + 1);
            if (str_starts_with(ltrim($after), '=>') && self::isKeyShape($token)) {
                $keys[] = $token;
            }
            $offset = $close + 1;
        }

        return array_values(array_unique($keys));
    }

    /**
     * THE GATE. Every claim-shaped backup-driver passage under `docs/`, as
     * `path:line — reason` strings. Empty is the pass condition.
     *
     * @return list<string>
     */
    public static function docFindings(): array
    {
        $accepted = self::acceptedDrivers();
        $default = self::defaultDriver();
        $volatile = self::volatileDrivers();
        $durable = array_values(array_diff($accepted, $volatile));
        $keys = self::declaredConfigKeys();

        $findings = [];
        foreach (self::docFiles() as $relative) {
            if (self::isVerbatimArchive($relative)) {
                continue;
            }

            foreach (self::scanDocument((string) file_get_contents(self::root().'/'.$relative)) as $hit) {
                $findings[] = $relative.':'.$hit['line'].' — '.$hit['reason']."\n    ".$hit['text'];
            }
        }

        return $findings;
    }

    /**
     * The whole path a document takes: chunk, exempt the reported speech, then
     * judge what is left. Split out of docFindings so a test can drive it with
     * the operator's own sentences and prove the EXEMPTION is what spares them
     * — testing the claim detector alone would pass a document that the real
     * scan never even exempted.
     *
     * @return list<array{line: int, text: string, reason: string}>
     */
    public static function scanDocument(string $document): array
    {
        $accepted = self::acceptedDrivers();
        $default = self::defaultDriver();
        $durable = array_values(array_diff($accepted, self::volatileDrivers()));
        $keys = self::declaredConfigKeys();

        $hits = [];
        foreach (self::chunks($document) as $chunk) {
            if (self::reportsVerbatim($chunk['text'])) {
                continue;
            }

            foreach (self::findingsInClaim($chunk['text'], $accepted, $default, $durable, $keys) as $reason) {
                $hits[] = ['line' => $chunk['line'], 'text' => $chunk['text'], 'reason' => $reason];
            }
        }

        return $hits;
    }

    /**
     * The claim rules, one CLAIM at a time, so a test can pin them with text.
     *
     * @param  list<string>  $accepted
     * @param  list<string>  $durable
     * @param  list<string>  $keys
     * @return list<string>
     */
    private static function findingsInClaim(
        string $claim,
        array $accepted,
        string $default,
        array $durable,
        array $keys,
    ): array {
        $findings = [];
        $lower = strtolower($claim);
        $mentionsDriver = str_contains($lower, 'driver');
        $mentionsBackup = str_contains($lower, 'backup');
        $quoted = self::quotedTokens($claim);

        // RULE 1 — the default. A driver VALUE bound to a default marker must be
        // the derived default. The patterns are ADJACENCY, never co-occurrence:
        // a line that says "`telegram` is the default; `memory` is the offline
        // one" is correct and must not fire on the second clause.
        foreach (self::defaultClaims($claim) as $claimed) {
            if ($claimed === $default) {
                continue;
            }
            if (in_array($claimed, $accepted, true)) {
                $findings[] = 'claims `'.$claimed.'` is the default backup driver; the shipped'
                    .' default is `'.$default.'` ('.self::CONFIG.')';

                continue;
            }
            // A claim about some OTHER config key ("`chunk_size` defaults to
            // 4194304") is out of scope, and a claim about a name that is neither
            // a driver nor a config key is English. Only a QUOTED value on a line
            // that talks about the backup driver is a driver being prescribed that
            // the provider would throw on.
            if (! in_array($claimed, $keys, true) && in_array($claimed, $quoted, true) && $mentionsDriver && $mentionsBackup) {
                $findings[] = 'prescribes `'.$claimed.'` as the default backup driver; it is not'
                    .' one of the accepted drivers ('.implode('|', $accepted).')'
                    .' and the default is `'.$default.'`';
            }
        }

        // RULE 2 — the accepted set, in its two documented shapes.
        //   2a. a pipe list whose every part is an accepted driver is an
        //       enumeration, and must be the WHOLE set: "telegram|memory" lists
        //       two of the three and is a host sent to a wrong config.
        //   2b. an explicitly MARKED enumeration ("accepted values are ...") must
        //       name the whole set too. An unmarked "or" between two drivers is
        //       prose and is NOT judged — see the scope note on the class, and
        //       the matrix rows that say `null` or `temporal` are not this rule's
        //       business.
        foreach (self::pipeEnumerations($claim, $accepted) as $enumerated) {
            if (count($enumerated) === count($accepted)) {
                continue;
            }
            $findings[] = 'enumerates the backup drivers as `'.implode('|', $enumerated)
                .'`; the accepted set is `'.implode('|', $accepted).'`'
                .' ('.self::PROVIDER.')';
        }

        if (self::hasAcceptedSetMarker($lower)) {
            $named = array_values(array_unique(array_merge(
                self::driverTokensIn($claim, $accepted),
                self::barePipeTokens($claim),
            )));
            if (count($named) >= 2 && count($accepted) > count($named)) {
                $findings[] = 'states the accepted backup drivers as `'.implode('`, `', $named)
                    .'`; the accepted set is `'.implode('|', $accepted).'`'
                    .' ('.self::PROVIDER.')';
            }
        }

        // RULE 3 — sole durability. "X is the ONLY durable driver" is false as
        // soon as a second accepted driver is durable, and one of them is a
        // bucket. These are four fixed shapes, NOT "the words only and durable
        // appear on the same line": a loose version fires on `memory` being the
        // only NON-durable driver, and on every table row that happens to say
        // "only" near "stores". Measured, not guessed.
        if (count($durable) > 1) {
            foreach (self::soleDurableClaims($claim) as $claimed) {
                if (in_array($claimed, $durable, true)) {
                    $findings[] = 'calls `'.$claimed.'` the ONLY durable backup driver; `'
                        .implode('` and `', array_values(array_diff($durable, [$claimed])))
                        .'` outlive the process as well';
                }
            }
        }

        return $findings;
    }

    /**
     * Tokens the claim says are THE default. ADJACENCY only — the seven shapes
     * below are the whole grammar, and every one of them was written because a
     * real doc line or a real verbatim line needed it. A co-occurrence rule
     * ("default" and a driver name anywhere in the same paragraph) was measured
     * against the corpus and rejected: it fired on the `queue`/`auto`/`laravel`
     * notify row and on "`chunk_size` defaults to 4194304".
     *
     * @return list<string>
     */
    private static function defaultClaims(string $claim): array
    {
        $patterns = [
            // `'memory' (default)`, `` `memory` (default) ``, and the real
            // backup.md:92 shape, `` `driver => 'memory'` (default) `` — a RUN of
            // fences, because the quoted value is inside a quoted key.
            '/\b([a-z][a-z0-9_]{1,31})[\'"`]*\s*\(\s*(?:[a-z][a-z-]*\s+){0,2}default\s*\)/i',
            // `memory` is the default / as the default / remains the default
            '/\b([a-z][a-z0-9_]{1,31})[\'"`]?\s+(?:as the |is the |remains the |stays the |is |are )default\b/i',
            // `memory` driver is the default (backup.md:13's real shape)
            '/\b([a-z][a-z0-9_]{1,31})[\'"`]?\s+driver\s+(?:is|remains|stays|becomes)\s+the\s+(?:default|shipped\s+default)\b/i',
            // default is `memory` / default to `memory`
            '/\bdefault\s+(?:is|to)\s+[`\'"]?([a-z][a-z0-9_]{1,31})/i',
            // default: `memory`
            '/\bdefault\s*:\s*[`\'"]?([a-z][a-z0-9_]{1,31})/i',
            // defaults to `memory`
            '/\bdefaults\s+to\s+[`\'"]?([a-z][a-z0-9_]{1,31})/i',
            // the default (backup) driver is `memory`
            '/\bthe\s+default\s+(?:backup\s+)?driver\s+(?:is|=|=>)\s*[`\'"]?([a-z][a-z0-9_]{1,31})/i',
            // the config line itself, quoted: 'driver' => env('X', 'memory')
            '/driver\'?\s*=>\s*env\([^)]*?,\s*[\'"]([a-z][a-z0-9_]{1,31})[\'"]/',
        ];

        $claims = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $claim, $matches) === false) {
                continue;
            }
            foreach ($matches[1] ?? [] as $token) {
                $token = strtolower(trim($token, '`\'"'));
                if (self::isValueShape($token) && ! in_array($token, self::NOT_A_CONFIG_VALUE, true)) {
                    $claims[] = $token;
                }
            }
        }

        return array_values(array_unique($claims));
    }

    /**
     * The four shapes that actually assert SOLE durability, in a fixed list
     * rather than "the words `only` and `durable` are both on this line". The
     * loose version was measured against the corpus and rejected: it fired on
     * "`memory` is the only NON-durable driver" and on any table row where
     * `only` sat near `stores`.
     *
     * @return list<string>
     */
    private static function soleDurableClaims(string $claim): array
    {
        $patterns = [
            // 'telegram' is the ONLY durable driver
            '/\b([a-z][a-z0-9_]{1,31})[\'"`]?\s+(?:is|are)\s+the\s+(?:only|sole)\s+durable\b/i',
            // the only durable driver is 'telegram'
            '/\bthe\s+(?:only|sole)\s+durable\s+driver\s+(?:is|=|=>)\s*[`\'"]?([a-z][a-z0-9_]{1,31})/i',
            // 'telegram' — the only durable driver
            '/\b([a-z][a-z0-9_]{1,31})[\'"`]?\s*[—:-]+\s+the\s+(?:only|sole)\s+durable\b/i',
        ];

        $claims = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $claim, $matches) === false) {
                continue;
            }
            foreach ($matches[1] ?? [] as $token) {
                $token = strtolower(trim($token, '`\'"'));
                if (self::isValueShape($token)) {
                    $claims[] = $token;
                }
            }
        }

        return array_values(array_unique($claims));
    }

    /**
     * Pipe lists in the claim, kept only when EVERY part is an accepted driver:
     * `telegram|memory(volatile)` enumerates two of the three, while
     * `memory|volatile` is an annotation and is not a list at all.
     *
     * @param  list<string>  $accepted
     * @return list<list<string>>
     */
    private static function pipeEnumerations(string $claim, array $accepted): array
    {
        $runs = [];
        foreach (preg_split('/\s+/', $claim) ?: [] as $word) {
            if (! str_contains($word, '|')) {
                continue;
            }
            $parsed = self::parsePipeList($word);
            if (count($parsed) < 2) {
                continue;
            }
            foreach ($parsed as $part) {
                if (! in_array($part, $accepted, true)) {
                    continue 2;
                }
            }
            $runs[] = $parsed;
        }

        return $runs;
    }

    /**
     * The same pipe lists, flattened — a docs enumeration in a code comment is
     * bare (`memory|telegram|s3`), not quoted, and a marked enumeration that
     * names only two of three must still be caught.
     *
     * @return list<string>
     */
    private static function barePipeTokens(string $claim): array
    {
        $names = [];
        foreach (preg_split('/\s+/', $claim) ?: [] as $word) {
            if (! str_contains($word, '|')) {
                continue;
            }
            foreach (self::parsePipeList($word) as $name) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Every quoted or backticked name in the claim that the accepted set
     * recognises as one of its own.
     *
     * @param  list<string>  $accepted
     * @return list<string>
     */
    private static function driverTokensIn(string $claim, array $accepted): array
    {
        $found = [];
        foreach (self::quotedTokens($claim) as $token) {
            if (in_array($token, $accepted, true)) {
                $found[] = $token;
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Backticked, single-quoted and double-quoted runs, stripped of their
     * fences. A token that is not name-shaped is not a config value: it is a
     * path, a sentence or an apostrophe's neighbour.
     *
     * @return list<string>
     */
    private static function quotedTokens(string $line): array
    {
        $tokens = [];
        foreach (['`', "'", '"'] as $fence) {
            if (substr_count($line, $fence) < 2) {
                continue;
            }
            $parts = explode($fence, $line);
            for ($i = 1, $n = count($parts); $i < $n; $i += 2) {
                $token = trim($parts[$i]);
                if ($token !== '') {
                    $tokens[] = $token;
                }
            }
        }

        return $tokens;
    }

    /**
     * Marks that the line CLAIMS the whole accepted set, as opposed to merely
     * mentioning two drivers in a sentence.
     */
    private static function hasAcceptedSetMarker(string $lower): bool
    {
        foreach ([
            'accepted value', 'accepted set', 'accepted driver', 'accepted are',
            'expected ', 'one of', 'valid value', 'valid driver', 'either',
            'choose between', 'must be',
        ] as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * CLAIM chunks, not raw lines. A claim is a sentence, and markdown wraps it
     * across lines at ~80 columns: `the accepted values are `memory`, `telegram`
     * and` on one line and `` `s3` `` on the next is ONE claim, and a line-scoped
     * gate cannot see the third member — it under-detects exactly where docs are
     * most enumerative, which is where the enumeration errors are.
     *
     * A chunk is therefore a maximal run of lines continuing one thought. It ends
     * at a blank line, at a table row (each `|` row is its own claim — a table
     * must never merge its rows, or the `backup.driver` row bleeds into the
     * `notify.queue` row and both get judged by the wrong rule), or at a new
     * bullet. Each chunk carries its 1-based start line, so a finding still points
     * at the place a human has to edit.
     *
     * @return list<array{line: int, text: string}>
     */
    private static function chunks(string $document): array
    {
        $chunks = [];
        $buffer = [];
        $bufferLine = 1;

        foreach (explode("\n", $document) as $index => $line) {
            $number = $index + 1;
            $trimmed = trim($line);
            $isItem = self::startsListItem($line);

            // A new bullet or a table row ENDS the previous claim; a wrapped
            // line does not. A bullet is not a one-line claim: store-backup.md
            // splits its driver enumeration across three lines, and reading the
            // first of them alone is how the enumeration "loses" a driver.
            $endsClaim = $trimmed === '' || str_starts_with(ltrim($line), '|') || $isItem;
            if ($endsClaim && $buffer !== []) {
                $chunks[] = ['line' => $bufferLine, 'text' => trim(implode(' ', $buffer))];
                $buffer = [];
            }

            if ($buffer === []) {
                $bufferLine = $number;
            }
            $buffer[] = $trimmed;
        }

        if ($buffer !== []) {
            $chunks[] = ['line' => $bufferLine, 'text' => trim(implode(' ', $buffer))];
        }

        return $chunks;
    }

    private static function startsListItem(string $line): bool
    {
        $trimmed = ltrim($line);

        return (bool) preg_match('/^(?:[-*+]\s|\d+[.)]\s)/', $trimmed);
    }

    /**
     * A blockquote is reported speech, and an ATTRIBUTION is the marker the repo
     * already uses for it. The bare adjective ("wins verbatim") is not one: it
     * is ordinary English in ordinary sentences, and exempting it would let any
     * claim hide behind a word.
     */
    private static function reportsVerbatim(string $chunk): bool
    {
        $trimmed = ltrim($chunk);
        if (str_starts_with($trimmed, '>')) {
            return true;
        }
        if (! str_contains($lower = strtolower($trimmed), 'verbatim')) {
            return false;
        }

        foreach (['(verbatim', '[verbatim', '.brain/verbatim'] as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        // `verbatim 2026-09-26`, `verbatim (2026-09-20)`, `verbatim: 2026-09-24` —
        // the attribution is the word followed by a DATE, not the word alone.
        return preg_match('/verbatim[\s:]*[\(\[]?\s*\d{4}-\d{2}-\d{2}/', $lower) === 1;
    }

    /** @return list<string> every `.md` file under `docs/`, repo-relative. */
    private static function docFiles(): array
    {
        $root = self::root().'/docs';
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && strtolower($file->getExtension()) === 'md') {
                $files[] = substr($file->getPathname(), strlen(self::root()) + 1);
            }
        }
        sort($files);

        return $files;
    }

    /**
     * The verbatim archive is not documentation: it is the record of what the
     * operator said, and it is allowed to name a driver that no longer exists.
     * Any path segment that starts with `verbatim` is that archive.
     */
    private static function isVerbatimArchive(string $relative): bool
    {
        foreach (explode('/', $relative) as $segment) {
            if (str_starts_with(strtolower($segment), 'verbatim')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function parsePipeList(string $list): array
    {
        $names = [];
        foreach (explode('|', $list) as $part) {
            // `memory(volatile)` annotates the name, it is not part of it.
            $name = strtolower(trim(explode('(', $part)[0]));
            $name = trim($name, "'\".` \t");
            if ($name !== '' && self::isKeyShape($name)) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The next `$driver === 'x'` or `$driver !== 'x'` branch in the source, as
     * [name, markerAt, bodyStart]. The marker ends ON the opening quote, so the
     * name is what lies between it and the closing one.
     *
     * @return array{0: string|null, 1: int, 2: int}|null
     */
    private static function nextDriverBranch(string $source, int $offset): ?array
    {
        $best = null;
        foreach (["\$driver === '", "\$driver !== '"] as $marker) {
            $at = strpos($source, $marker, $offset);
            if ($at !== false && ($best === null || $at < $best[1])) {
                $best = [$marker, $at];
            }
        }

        if ($best === null) {
            return null;
        }

        [$marker, $at] = $best;
        $bodyStart = $at + strlen($marker);
        $close = strpos($source, "'", $bodyStart);
        $name = $close === false ? null : strtolower(substr($source, $bodyStart, $close - $bodyStart));

        return [$name === '' ? null : $name, $at, $bodyStart];
    }

    /** The provider's own `?: 'telegram'`-style fallback for the driver key. */
    private static function providerFallbackDefault(): string
    {
        $source = self::source(self::PROVIDER);
        $offset = 0;
        $last = null;

        while (($at = strpos($source, 'teleframe.backup.driver', $offset)) !== false) {
            // Step back over the opening quote, or the token that follows the
            // key would be read as if it sat OUTSIDE its quotes.
            $tail = substr($source, $at > 0 ? $at - 1 : $at);
            $end = strcspn($tail, ';\n');
            $read = substr($tail, 0, $end);
            $close = strpos($read, ')');
            if ($close !== false) {
                $literals = self::quotedTokens(substr($read, 0, $close));
                $values = array_values(array_filter(
                    $literals,
                    static fn (string $t): bool => $t !== 'teleframe.backup.driver' && self::isValueShape($t),
                ));
                if ($values !== []) {
                    $last = $values[count($values) - 1];
                }
            }
            $offset = $at + strlen('teleframe.backup.driver');
        }

        self::assertNotNull(
            $last,
            self::PROVIDER.' reads teleframe.backup.driver with no literal fallback;'
            .' fix THIS derivation rather than transcribing a default into it.',
        );

        return (string) $last;
    }

    /** The `'backup' => [ ... ]` slice of the shipped config. */
    private static function backupConfigBlock(): string
    {
        $source = self::source(self::CONFIG);
        $at = strpos($source, "'backup' =>");
        self::assertNotFalse($at, self::CONFIG." declares no 'backup' => key.");

        $tail = substr($source, $at);
        $next = strpos($tail, "\n    '", 1);

        return $next === false ? $tail : substr($tail, 0, $next);
    }

    /** A config key / driver name: lowercase word characters, 2-32 of them. */
    private static function isKeyShape(string $token): bool
    {
        $len = strlen($token);

        return $len >= 2
            && $len <= 32
            && $token[0] >= 'a' && $token[0] <= 'z'
            && strspn($token, 'abcdefghijklmnopqrstuvwxyz0123456789_') === $len;
    }

    /**
     * As isKeyShape, but a VALUE may not carry underscores' neighbours: this is
     * what separates a driver name from a prose fragment.
     */
    private static function isValueShape(string $token): bool
    {
        return self::isKeyShape($token) && ! str_contains($token, '_');
    }

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function source(string $relative): string
    {
        $path = self::root().'/'.$relative;
        self::assertFileExists($path, $relative.' must exist for this gate to derive anything from it.');

        return (string) file_get_contents($path);
    }

    // -----------------------------------------------------------------------
    // the gate
    // -----------------------------------------------------------------------

    public function test_the_accepted_set_and_the_default_are_derived_not_transcribed(): void
    {
        $accepted = self::acceptedDrivers();
        $default = self::defaultDriver();

        self::assertGreaterThanOrEqual(2, count($accepted), 'the accepted driver set must not be a single value');
        self::assertContains($default, $accepted, 'the shipped default must be one of the accepted drivers');

        $volatile = self::volatileDrivers();
        self::assertNotEmpty(array_intersect($volatile, $accepted), 'the volatile set must be accepted drivers');

        // Nothing in the DERIVATION names a driver; assert that, and assert it
        // only there. A transcribed name is how a stale copy of this very fact
        // reaches a reader of the gate and becomes the next doc lie. The prose
        // and the planted fixtures below are allowed to name drivers — they are
        // not where a value could be believed.
        $own = (string) file_get_contents(__FILE__);
        $derivation = substr(
            $own,
            (int) strpos($own, 'public static function acceptedDrivers'),
            (int) strpos($own, 'THE GATE.') - (int) strpos($own, 'public static function acceptedDrivers'),
        );
        self::assertNotSame('', $derivation, 'the derivation slice must exist for this assertion to mean anything');

        foreach (array_merge($accepted, ['telegram', 'memory', 's3', 'ftp', 'rsync']) as $name) {
            foreach (["'".$name."'", '`'.$name.'`'] as $form) {
                self::assertStringNotContainsString(
                    $form,
                    $derivation,
                    'the derivation must not transcribe '.$form.' — derive it from the code.',
                );
            }
        }
    }

    public function test_no_doc_contradicts_the_accepted_driver_set_the_default_or_durability(): void
    {
        $findings = self::docFindings();

        self::assertSame(
            [],
            $findings,
            "A doc claim about the backup driver disagrees with the code.\n"
            .'Derived from '.self::PROVIDER.' and '.self::CONFIG.", not typed in here.\n\n  "
            .implode("\n  ", $findings)
            ."\n\nFix the DOC (the code is the authority) — or, if the code is wrong,"
            ."\nchange the code and every doc with it in the same change.",
        );
    }

    /**
     * DIRECTION 1 — the gate BITES. Each text below is a defect this repo
     * actually shipped, in the shape it actually shipped in, so a reader can
     * match the failure to the file it came from.
     */
    public function test_it_fires_on_a_planted_contradiction(): void
    {
        $plants = [
            'the default lie (quickstart.md:217, verbatim)' => "// driver 'memory' (default) is the offline smoke-test driver.",
            'the default lie, backticked (backup.md:13)' => 'everything; the offline `memory` driver is the default.',
            'the default as prose (backup.md:92)' => "`driver => 'memory'` (default) is the offline driver: an in-memory vault.",
            'the offline-default phrasing (store-backup.md:161)' => '`teleframe.backup.sets.<id>`; driver is `memory` (offline default) or `telegram`.',
            'the shipped config line quoted with the old default' => "'driver' => env('TELEFRAME_BACKUP_DRIVER', 'memory'),",
            'the short pipe list (backup.md:80)' => "'driver' => env('TELEFRAME_BACKUP_DRIVER', 'telegram'), // telegram|memory(volatile)",
            'the marked enumeration missing one' => 'The accepted values are `memory` and `telegram` for teleframe.backup.driver.',
            'the sole-durable claim (backup.md:76)' => "// 'telegram' is the ONLY durable driver and the default.",
            'a rejected driver prescribed as the default' => 'The default backup driver is `ftp`; set teleframe.backup.driver to match.',
            'an enumeration wrapped across lines (the shape a line-scoped gate misses)' => "the accepted values are `memory` and\n`telegram`, and anything else throws",
        ];

        foreach ($plants as $label => $text) {
            $hits = self::scanDocument($text);
            self::assertNotSame(
                [],
                $hits,
                'the gate did NOT bite on: '.$label."\n  text: ".$text,
            );
        }
    }

    /**
     * DIRECTION 2 — the gate does NOT fire on the operator's own words, and does
     * not fire on the corrected doc text either. These are the operator's REAL
     * sentences, from `.brain/verbatim/`. Half of them name a driver and a
     * default that the code no longer has, which is the whole point: a
     * verbatim is REPORTED speech, and the gate spares it because it is
     * reported, not because its words were spared.
     *
     * The gate that fires here is worse than no gate: it teaches its reader to
     * trust it without checking, and it gets switched off — taking the four real
     * defects above with it.
     */
    public function test_it_stays_silent_on_the_verbatim(): void
    {
        $accepted = self::acceptedDrivers();
        $default = self::defaultDriver();
        $durable = array_values(array_diff($accepted, self::volatileDrivers()));
        $keys = self::declaredConfigKeys();

        // .brain/verbatim/2026-09-24-…:38-41 — ftp/rsync/s3 as transports.
        $transports = [
            '> next thing is the app that converts telegram into a permanent backup storage',
            '> with ftp or rsync or s3 api the s3 is good and it should treat telegram as a',
            '> backup storage it make files into 4 gig chunks and put a pass and sends them',
            '> also one thing is the default behaviour is it will be installed in a laravel',
        ];

        // .brain/verbatim/2026-09-26-notes.md:3702-3704 — quotes the OLD config
        // line, in which `memory` genuinely WAS the shipped default.
        $oldDefault = [
            '> DEFECT 1 — OUT OF THE BOX THE COMMAND IS A SILENT NO-OP.',
            "> `'driver' => env('TELEFRAME_BACKUP_DRIVER', 'memory')` in src/Laravel/config/teleframe.php",
            '> resolves to `InMemoryVault` (TeleframeServiceProvider.php around 355-362)',
            '> FIX: the default must not be a lie. Either default to the real Telegram driver',
            '> or keep memory as an explicit opt-in for tests and make the default refuse loudly,',
            '>   - the backup durability default changed from a silent in-memory no-op to the',
            '> real Telegram vault',
        ];

        // The same words, quoted inside a prose doc with the repo's own
        // attribution markers rather than a blockquote.
        $attributed = [
            '> doc [verbatim 2026-09-26](/.brain/verbatim/2026-09-26-notes.md) names `memory`',
            'Fix: the default must not be a lie (verbatim 2026-09-26:3704) — keep `memory` (default)',
            'The storage verbatim (2026-09-20) surface is `memory`-free.',
        ];

        foreach ([$transports, $oldDefault, $attributed] as $group) {
            foreach ($group as $line) {
                self::assertTrue(
                    self::reportsVerbatim($line),
                    'this line must be recognised as reported speech: '.$line,
                );
                self::assertSame(
                    [],
                    self::scanDocument($line),
                    'the gate fired on the OPERATOR\'S OWN WORDS, which is the over-match'
                    ."\n".'that trains the operator to disable it: '.$line,
                );
            }
        }

        // They really would be caught if they were NOT reported: the exemption is
        // scoped to the report, never to the words. Same driver, same default
        // marker, no quote and no attribution.
        $asInstruction = [
            'Set teleframe.backup.driver: `memory` (default) for smoke tests.',
            'The default backup driver is `ftp` for large sets.',
            'Set '."'driver'".' => '."'".'memory'."'".' (default) in config/teleframe.php.',
        ];
        foreach ($asInstruction as $line) {
            self::assertFalse(
                self::reportsVerbatim($line),
                'an instruction must never be mistaken for reported speech: '.$line,
            );
            self::assertNotSame(
                [],
                self::scanDocument($line),
                'an unquoted, unattributed instruction must still be caught: '.$line,
            );
        }

        // And the ARCHIVE itself is exempt path-wide, not line by line: a
        // verbatim file is the record of what was said, and it is allowed to be
        // wrong about the code because it was never a claim about the code.
        foreach (['docs/verbatim/2026-09-24-notes.md', 'docs/verbatims/x.md', 'docs/reference/verbatim-reply.md'] as $path) {
            self::assertTrue(self::isVerbatimArchive($path), 'this path must be exempt wholesale: '.$path);
        }
        foreach (['docs/backup.md', 'docs/usage/store-backup.md', 'docs/reference/telegram-passport-official.md'] as $path) {
            self::assertFalse(self::isVerbatimArchive($path), 'this path must NOT be exempt: '.$path);
        }

        // And the bare adjective is NOT an attribution. "wins verbatim" is
        // ordinary English in ordinary sentences, and a claim behind it stands.
        self::assertFalse(self::reportsVerbatim('the host queue wins verbatim'));
        self::assertNotSame(
            [],
            self::scanDocument("the host queue wins verbatim: 'memory' (default)"),
            'the bare adjective must not exempt a claim',
        );

        // The corrected docs, as they now stand, and the neighbouring claims
        // that are about OTHER keys: silent, and each for a stated reason.
        $correct = [
            'everything; the shipped `driver` default is `telegram`, so a default install',
            '// The accepted set is memory|telegram|s3 (any other value throws at bind',
            '- The set holds `paths` under'."\n"
            .'  `teleframe.backup.sets.<id>`; `driver` is one of `memory`, `telegram` or'."\n"
            .'  `s3`, defaults to `telegram` (needs `account`; `memory` is the volatile'."\n"
            .'  offline driver, `s3` a durable bucket); `chunk_size` defaults to 4194304.',
            'the accepted values are `memory`, `telegram` and `s3`, and any other value',
            '**only** when the driver is `telegram` — steps 5+ are NOT-ALLOWLISTED.',
            '**`memory` driver** never persists — it exists for offline tests.',
            'driver: '."'pgsql'".', database: '."'".'app'."'".', host: '."'".'127.0.0.1'."'",
            '`schedule.enabled` defaults to `true`, and `backup.missing` is the topic.',
            '`auto` (the shipped default), `laravel`, `amqp` or `sync`; anything else throws.',
            '**`memory` is the only NON-durable driver** — process-local, stores nothing.',
            '`ftp` and `rsync` are named in the verbatim but were never implemented.',
        ];

        foreach ($correct as $line) {
            self::assertSame(
                [],
                self::scanDocument($line),
                'the gate fired on CORRECT documentation: '.$line,
            );
        }
    }
}
