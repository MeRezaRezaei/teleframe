<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * THE CONFIG-KEY DECLARATION GATE — a read may not name a path no merged config declares.
 *
 * THE FRICTION CLASS THIS EXISTS FOR. A config key is read by the code under a path
 * the published config never declares, so the read silently returns its default
 * forever. The key looks configurable to anybody reading the code and is not
 * configurable at all. It has bitten this repository three times:
 *
 *   1. `StoreBackupServiceProvider` reads `teleframe.store.backup.schedule.*`, which
 *      resolves ONLY because that provider merges `src/Laravel/config/store-backup.php`
 *      at the prefix `teleframe.store.backup`. The other config file,
 *      `src/Laravel/config/teleframe.php`, carries a TOP-LEVEL `backup` key and is a
 *      DIFFERENT FILE under a DIFFERENT prefix (`teleframe`). An agent grepped the
 *      wrong file, concluded the key had no config entry, and shipped 61 lines
 *      declaring `teleframe.backup.schedule.package_schedule` — a key under a prefix
 *      nothing reads. Running it was the only thing that caught it.
 *   2. The F12 backup peer keys (`peer_type`/`peer_id`/`premium`) shipped as defaults
 *      applied in `TeleframeServiceProvider` instead of being declared in config, so a
 *      doc claimed the wrong provenance (`docs/backup.md:106`).
 *   3. The F13 dump-trigger declaration read `teleframe.store.backup.schedule.*` while
 *      the operator-facing env var lived somewhere else.
 *
 * WHY A GATE IS THE ONLY ANSWER. Every one of those three was found by reading a
 * diff, running something, or trusting a doc — never by a check. The information is
 * all there and all machine-readable: the reads are literals, and the declarations
 * are literals in `mergeConfigFrom` calls. Nothing was ever going to notice they
 * disagreed.
 *
 * THE METHOD, and the two things it refuses to do:
 *
 *   It DERIVES the declared key set. The prefixes come from reading every
 *   `mergeConfigFrom(...)` call in `src/`, and the file each one names is resolved
 *   from that call's own path expression. It NEVER assumes a config file's name
 *   matches the prefix it is merged at. That mismatch is not a detail of the
 *   tooling — it IS the defect, and any gate that guesses the prefix from the
 *   filename would reproduce instance 1 rather than prevent it.
 *
 *   It does not read `.env` and it dials nothing. The declared set is built from
 *   PHP array KEYS only; the values the config files compute from `env()` are
 *   discarded unread-for-purpose, so the result is identical on a machine with a
 *   `.env` and on CI without one. No provider is booted, no container is built, no
 *   socket is opened, no credential is needed.
 *
 * THE HONESTY CONSTRAINT, which is the hard part. Most config reads are
 * `->get($key, $default)` or `A ?? B`, and an absent key there is NOT a defect —
 * the fallback IS the contract. Reporting those would train a reader to ignore the
 * gate, and a gate that cries wolf gets switched off. So absence is tolerated in
 * exactly two mechanically-stated ways, and in nothing else:
 *
 *   (i)  A read that is the RIGHT-HAND OPERAND OF `??` is a fallback by construction:
 *        the `??` exists precisely because the author expected the key to be absent.
 *        Such a read is tolerated only if the key on the LEFT of that `??` — the
 *        cascade's primary — IS declared. A cascade whose primary is also undeclared
 *        is a defect, and this gate says so.
 *   (ii) A key listed in UNRESOLVED_BY_DESIGN below, each entry carrying WHY the
 *        absence is the design and not an oversight. This list is the repo's
 *        narrow-justified-allowlist convention, modelled on
 *        `src/Teleframe/Warm/LiveEndpointPolicy.php`. It is deny-by-default: an
 *        unlisted unresolved key fails. It is also self-policed — a third test
 *        proves every entry has a reason, that the entry is still genuinely
 *        undeclared, and that the scanner itself still flags a deliberately dead
 *        path, so this file cannot decay into a green nothing.
 *
 * KNOWN LIMITS, stated rather than hidden. Reads through a variable-cased key
 * (`config($prefix.'.x')`), and non-`teleframe.` namespaces (`telegram.*`, which
 * deliberately probes host-owned keys), are out of scope: this gate resolves
 * LITERAL `teleframe.` paths only, which is the shape the friction class takes.
 */
final class ConfigKeyDeclarationGateTest extends TestCase
{
    /**
     * Accessors that constitute a CONFIG VALUE READ. Deny-by-default: a quoted
     * `teleframe.*` reached through anything else is not a read and is not
     * considered here. That is the whole filter — the ~270 non-read occurrences
     * this file scans past are class constants, PSR-3 log event names, Laravel
     * route names, container bind keys, PSR-16 cache-key prefixes and publish
     * paths, none of which a config array is supposed to declare.
     *
     * `invoke` is the `($this->config)('teleframe.x')` shape — an injected config
     * resolver invoked as a closure. It exists in this tree exactly once, and the
     * alternative was to miss a real read.
     */
    private const READ_ACCESSORS = [
        'config', 'get', 'has', 'string', 'int', 'integer', 'bool', 'boolean',
        'float', 'double', 'array', 'value', 'invoke',
    ];

    /**
     * THE ALLOWLIST. Keys the code reads that NO merged config declares, where the
     * absence is a DECIDED THING and not an oversight. Deny-by-default: one entry
     * today, and adding one is an act with a reason attached.
     *
     * @var array<string, string> config key => why its absence is the design
     */
    private const UNRESOLVED_BY_DESIGN = [
        'teleframe.mirror.result_methods' => 'A deliberate operator escape hatch that is NOT in the shipped config, and says so in '
            .'the code at the read site (src/Laravel/MirrorIngesterFactory.php:317-323): "The key is '
            .'deliberately NOT added to the shipped teleframe.php: an operator who wants to narrow or '
            .'widen the list publishes the config and adds the key, and a host that has not touched it '
            .'gets the catalog-derived default. A missing key and a null key both fall back." The read '
            .'has no default argument precisely because the guard is the very next statement '
            .'(`if (! is_array($configured)) return MirrorResultPolicy::default();`), which is a '
            .'deliberate internal fallback, not an accidental one. Declaring it in the shipped config '
            .'would make every host publish a 4-method list it never chose, so absence is the '
            .'correct shipped state. Documented as a supported override at '
            .'src/Laravel/Console/BackfillCommand.php:1116-1123 and covered by '
            .'tests/Console/MirrorResultSyncPlacesRowTest.php:192.',
    ];

    // ---- the repository under audit -----------------------------------------

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    /** @return list<string> absolute paths of every PHP file under src/ */
    private static function srcFiles(): array
    {
        $files = [];
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::root().'/src', RecursiveDirectoryIterator::SKIP_DOTS),
        );
        /** @var SplFileInfo $f */
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
                $files[] = $f->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    /**
     * THE DERIVATION. Every `mergeConfigFrom(<path expression>, '<prefix>')` in
     * `src/`, with `<path expression>` resolved against the file that contains the
     * call. The prefix and the file come from the SAME call, so a config file whose
     * name does not match its prefix — `store-backup.php` merged at
     * `teleframe.store.backup`, versus `teleframe.php` merged at `teleframe` — is
     * handled correctly rather than assumed away.
     *
     * @param  list<string>  $files
     * @return list<array{provider: string, file: string, prefix: string, keys: list<string>}>
     */
    private static function mergedConfigs(array $files): array
    {
        $out = [];
        foreach ($files as $file) {
            $code = (string) file_get_contents($file);
            if (! str_contains($code, 'mergeConfigFrom')) {
                continue;
            }
            $hits = preg_match_all(
                '/mergeConfigFrom\(\s*__DIR__\s*\.\s*\'([^\']+)\'\s*,\s*[\'"]([A-Za-z0-9_.\-]+)[\'"]\s*\)/',
                $code,
                $m,
                PREG_SET_ORDER,
            );
            if ($hits === false) {
                continue;
            }
            foreach ($m as $hit) {
                $target = dirname($file).$hit[1];
                $out[] = [
                    'provider' => self::rel($file),
                    'file' => is_file($target) ? self::rel(realpath($target) ?: $target) : '<<UNRESOLVED '.$hit[1].'>>',
                    'prefix' => $hit[2],
                    'keys' => is_file($target) ? self::flatten((array) require $target, $hit[2]) : [],
                ];
            }
        }

        return $out;
    }

    /**
     * Every dotted key a merged config array declares, INCLUDING the interior array
     * nodes, because `config('teleframe.passport')` legitimately asks for a subtree
     * rather than a leaf.
     *
     * Only KEYS are read. The values these config files compute from `env()` are
     * loaded and dropped, which is what keeps the gate independent of `.env`.
     *
     * @return list<string>
     */
    private static function flatten(array $array, string $prefix, string $key = ''): array
    {
        $base = $key === '' ? $prefix : $key;
        // The merged node ITSELF is declared — mergeConfigFrom() creates it —
        // which is why `config('teleframe.passport')` may legitimately ask for a
        // subtree rather than a leaf.
        $out = [$base];
        foreach ($array as $k => $v) {
            $dotted = $base.'.'.$k;
            $out[] = $dotted;
            if (is_array($v)) {
                foreach (self::flatten($v, $prefix, $dotted) as $nested) {
                    $out[] = $nested;
                }
            }
        }

        return $out;
    }

    /**
     * Every quoted `teleframe.…` literal in `src/`, narrowed to the ones reached
     * through a config VALUE READ.
     *
     * @param  list<string>  $files
     * @return list<array{at: string, key: string, accessor: string, cascade: ?string}>
     */
    private static function valueReads(array $files): array
    {
        $out = [];
        foreach ($files as $file) {
            $code = (string) file_get_contents($file);
            if (! str_contains($code, 'teleframe.')) {
                continue;
            }
            // The quote anchors the literal, so a config path that merely APPEARS
            // inside a longer string (a publish map, a file path) is not a read.
            $hits = preg_match_all(
                '/([\'"])(teleframe\.[A-Za-z0-9_.\-]*(?:\$[A-Za-z_][A-Za-z0-9_]*|\{\$[A-Za-z_][A-Za-z0-9_]*\})?)\1/',
                $code,
                $m,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
            );
            if ($hits === false) {
                continue;
            }
            foreach ($m as $hit) {
                $offset = (int) $hit[0][1];
                $key = (string) $hit[2][0];
                $before = rtrim(substr($code, max(0, $offset - 90), min(90, $offset)));

                [$accessor, $cascade] = self::accessorAt($before);
                if ($accessor === null || ! in_array($accessor, self::READ_ACCESSORS, true)) {
                    continue;
                }

                $out[] = [
                    'at' => self::rel($file).':'.(substr_count(substr($code, 0, $offset), "\n") + 1),
                    // `->get('teleframe.backup.sets.'.$name, [])` names a PREFIX;
                    // the fixed part of the path is what a declaration must cover.
                    'key' => rtrim($key, '.'),
                    'accessor' => $accessor,
                    'cascade' => $cascade,
                ];
            }
        }

        return $out;
    }

    /**
     * The accessor token immediately before the literal's opening quote, and — when
     * the read is the right-hand operand of a `??` — the key on the LEFT of that
     * `??`, which is the cascade's primary and therefore the one that must be
     * declared.
     *
     * The `??` sits BEFORE the accessor (`A ?? config('key')`), so it is found by
     * asking what precedes the accessor name, not what precedes the quote. Getting
     * this backwards would exempt every `($config->get('k') ?? $fallback)` — which
     * is a read with a default, not a cascade operand — and would have hidden the
     * exact `StoreBackupServiceProvider::triggerArmed()` shape this gate was built
     * to catch.
     *
     * @return array{0: ?string, 1: ?string} [accessor, primary key of a `??` cascade]
     */
    private static function accessorAt(string $before): array
    {
        if (str_ends_with($before, ')(')) {
            return ['invoke', null];
        }
        $m = preg_match(
            '/(?:^|[^A-Za-z0-9_\\\\$])([A-Za-z_][A-Za-z0-9_]*)(?:::([A-Za-z_][A-Za-z0-9_]*))?\\($/',
            $before,
            $hit,
            PREG_OFFSET_CAPTURE,
        );
        if ($m !== 1) {
            return [null, null];
        }
        $name = (($hit[2][0] ?? '') !== '' ? $hit[2][0] : $hit[1][0]);
        $start = (int) $hit[0][1];
        $before_name = substr($before, 0, $start);
        if (preg_match('/\?\?\s*$/', $before_name) !== 1) {
            return [$name, null];
        }
        // The primary is the nearest `teleframe.` literal left of the `??`.
        if (preg_match_all('/[\'"](teleframe\.[A-Za-z0-9_.\-]*)[\'"]/', $before_name, $keys) === false
            || $keys[1] === []) {
            return [$name, null];
        }

        return [$name, rtrim(end($keys[1]), '.')];
    }

    private static function rel(string $path): string
    {
        return str_replace(self::root().'/', '', $path);
    }

    /**
     * Classify every read against the declared set.
     *
     * @param  list<string>  $files
     * @return array{declared: array<string, true>, reads: list<array<string, mixed>>, tolerated: list<array<string, mixed>>, defects: list<array<string, mixed>>}
     */
    private static function audit(array $files): array
    {
        $declared = [];
        foreach (self::mergedConfigs($files) as $merge) {
            foreach ($merge['keys'] as $k) {
                $declared[$k] = true;
            }
        }

        $reads = self::valueReads($files);
        $tolerated = [];
        $defects = [];
        foreach ($reads as $read) {
            if (isset($declared[$read['key']])) {
                continue;
            }
            if ($read['cascade'] !== null && isset($declared[$read['cascade']])) {
                $read['why'] = 'right-hand operand of a `??` whose primary '.$read['cascade'].' IS declared';
                $tolerated[] = $read;

                continue;
            }
            if (isset(self::UNRESOLVED_BY_DESIGN[$read['key']])) {
                $read['why'] = 'UNRESOLVED_BY_DESIGN: '.self::UNRESOLVED_BY_DESIGN[$read['key']];
                $tolerated[] = $read;

                continue;
            }
            $defects[] = $read;
        }

        return ['declared' => $declared, 'reads' => $reads, 'tolerated' => $tolerated, 'defects' => $defects];
    }

    // ---- the gates ----------------------------------------------------------

    /**
     * THE PRECONDITION. The declared set above is only as good as the merge calls
     * behind it, so the shape of the merge table is asserted before any read is
     * judged. Without this, a provider that stopped merging would turn every key
     * under it into a defect and bury the one real finding in a wall of noise —
     * the failure mode that gets a gate switched off.
     */
    public function test_every_config_file_on_disk_is_merged_at_exactly_one_prefix(): void
    {
        $files = self::srcFiles();
        $merges = self::mergedConfigs($files);

        self::assertNotSame([], $merges, 'no mergeConfigFrom() call found in src/ — this gate has no declared set to check against');

        $configDir = self::root().'/src/Laravel/config';
        $onDisk = glob($configDir.'/*.php') ?: [];
        self::assertNotSame([], $onDisk, $configDir.' must contain config files for this test to mean anything');

        $merged = array_map(static fn (string $p): string => $p, array_column($merges, 'file'));
        sort($merged);

        $unmerged = [];
        foreach ($onDisk as $file) {
            $rel = self::rel((string) realpath($file));
            if (! in_array($rel, $merged, true)) {
                $unmerged[] = $rel;
            }
        }

        self::assertSame(
            [],
            $unmerged,
            'config file(s) NO mergeConfigFrom() call declares: '.implode(', ', $unmerged)
            .' — keys under them would read as dead paths, but the real fault is that the file is never merged',
        );

        $duplicated = array_values(array_diff_assoc($merged, array_unique($merged)));
        self::assertSame(
            [],
            $duplicated,
            'a config file merged more than once: '.implode(', ', $duplicated)
            .' — a second merge at a different prefix is how one key gets two homes',
        );

        self::assertSame(
            count($onDisk),
            count($merged),
            'every config file on disk must be merged exactly once; found '.count($merged)
            .' merge(s) for '.count($onDisk).' file(s)',
        );
    }

    /**
     * THE GATE. Every config value read in `src/` must name a path some merged
     * config declares. A read that does not is the friction class: it looks
     * configurable and is not.
     */
    public function test_no_config_value_read_targets_a_path_no_merged_config_declares(): void
    {
        $audit = self::audit(self::srcFiles());
        $reads = $audit['reads'];
        $declared = $audit['declared'];

        self::assertNotSame([], $reads, 'no config value read found in src/ — the scanner is broken, not the tree');
        self::assertNotSame([], $declared, 'no declared key set was built — the scanner is broken, not the tree');

        $defects = $audit['defects'];
        self::assertSame(
            [],
            $defects,
            self::report($defects, $declared),
        );

        $distinct = [];
        foreach ($reads as $r) {
            $distinct[$r['key']] = true;
        }

        fwrite(STDOUT, sprintf(
            "\n  config-key declaration gate: %d read(s) across %d distinct key(s); %d key(s) declared"
            ." by %d mergeConfigFrom() call(s).\n",
            count($reads),
            count($distinct),
            count($declared),
            count(self::mergedConfigs(self::srcFiles())),
        ));
        fwrite(STDOUT, '  tolerated (NOT failures):'."\n            ".self::toleratedSummary($audit['tolerated'])."\n\n");
    }

    /**
     * THE ALLOWLIST IS NOT A RUBBER STAMP. Three properties, each of which fails if
     * the list is allowed to rot:
     *
     *   - every entry carries a stated reason (an empty one fails);
     *   - every entry is STILL genuinely undeclared — an allowlist entry that has
     *     quietly become a real declaration is dead weight hiding the read;
     *   - every entry is actually EXERCISED — an entry nothing reads is a note, not
     *     an exemption, and would let a real read at that key pass unnoticed.
     */
    public function test_the_unresolved_by_design_allowlist_is_not_a_rubber_stamp(): void
    {
        self::assertNotSame([], self::UNRESOLVED_BY_DESIGN, 'the allowlist is empty — that is fine, but say so in code, not by omission');

        foreach (self::UNRESOLVED_BY_DESIGN as $key => $reason) {
            self::assertNotSame('', trim($reason), $key.' is allowlisted with NO REASON. An entry without a reason is not an exemption, it is a habit.');
            self::assertGreaterThan(
                40,
                strlen(trim($reason)),
                $key.' carries a reason too short to be a justification: '.trim($reason),
            );
        }

        $declared = [];
        foreach (self::mergedConfigs(self::srcFiles()) as $merge) {
            foreach ($merge['keys'] as $k) {
                $declared[$k] = true;
            }
        }

        $readKeys = [];
        foreach (self::valueReads(self::srcFiles()) as $read) {
            $readKeys[$read['key']][] = $read['at'];
        }

        foreach (array_keys(self::UNRESOLVED_BY_DESIGN) as $key) {
            self::assertArrayNotHasKey(
                $key,
                $declared,
                $key.' is allowlisted as "deliberately undeclared" but a merged config now declares it'
                .' — remove the entry, and let the read be checked like any other',
            );
            self::assertArrayHasKey(
                $key,
                $readKeys,
                $key.' is allowlisted but NOTHING in src/ reads it — an entry for an unread key exempts nothing',
            );
        }
    }

    /**
     * THE GATE HAS BEEN OBSERVED WORKING. A gate that has never been seen to fail is
     * not a gate — it is a hope. This test feeds the very scanner the gate uses a
     * deliberately dead path and requires it to be flagged, so the class of defect
     * above cannot hide behind a scanner that quietly stopped matching.
     *
     * The fixture is written to a temp directory and deleted. Nothing under `src/`
     * is touched, and nothing outside this file is required.
     */
    public function test_the_scanner_flags_a_deliberately_dead_config_path(): void
    {
        $dir = sys_get_temp_dir().'/tf-cfg-gate-'.bin2hex(random_bytes(6));
        mkdir($dir.'/src', 0o777, true);
        file_put_contents($dir.'/src/Fixture.php', <<<'PHP'
        <?php

        final class Fixture
        {
            public function read(): mixed
            {
                // A path under a prefix NOTHING declares — the friction class,
                // verbatim. Not planted in src/; this fixture exists only here.
                return config('teleframe.backup.nonexistent_key_probe');
            }

            public function declared(): mixed
            {
                return config('teleframe.store.backup.schedule.package_schedule.enabled');
            }
        }
        PHP);

        try {
            $reads = self::valueReads([$dir.'/src/Fixture.php']);
            $keys = array_column($reads, 'key');

            self::assertContains(
                'teleframe.backup.nonexistent_key_probe',
                $keys,
                'the scanner did not even FIND the planted dead path — it cannot be trusted to find one in src/',
            );

            $declared = [];
            foreach (self::mergedConfigs(self::srcFiles()) as $merge) {
                foreach ($merge['keys'] as $k) {
                    $declared[$k] = true;
                }
            }

            $flagged = [];
            foreach ($reads as $read) {
                if (! isset($declared[$read['key']]) && ! isset(self::UNRESOLVED_BY_DESIGN[$read['key']])) {
                    $flagged[] = $read['key'];
                }
            }

            self::assertContains(
                'teleframe.backup.nonexistent_key_probe',
                $flagged,
                'the scanner FOUND the dead path but did not FLAG it — the resolution logic is broken',
            );
            self::assertNotContains(
                'teleframe.store.backup.schedule.package_schedule.enabled',
                $flagged,
                'the scanner flagged a path that IS declared — it flags keys rather than checking them',
            );
        } finally {
            @unlink($dir.'/src/Fixture.php');
            @rmdir($dir.'/src');
            @rmdir($dir);
        }
    }

    // ---- reporting ----------------------------------------------------------

    /** @param list<array<string, mixed>> $defects */
    private static function report(array $defects, array $declared): string
    {
        if ($defects === []) {
            return 'no defect';
        }

        $lines = [];
        foreach ($defects as $defect) {
            $hint = self::nearestDeclared($defect['key'], $declared);
            $lines[] = '  '.$defect['at'].'  '.$defect['key'].'  (read via '.$defect['accessor'].'())'
                .($hint === null
                    ? "\n      nearest declared key: NONE — no declared key shares this root; re-read the mergeConfigFrom prefix"
                    : "\n      nearest declared key: ".$hint.'   ← this is where it actually lives');
        }

        $uniq = [];
        foreach ($defects as $defect) {
            $uniq[$defect['key']] = true;
        }

        return count($defects).' read(s) of '.count($uniq)." key(s) name a path no merged config declares:\n"
            .implode("\n", $lines)
            ."\n\n  The key looks configurable and is not. Either declare it under the prefix its "
            .'provider MERGES (read the mergeConfigFrom call — the file name does not tell you), or '
            .'if the absence is the design, name it in UNRESOLVED_BY_DESIGN with a reason.';
    }

    /**
     * What the developer actually needs: the declared key this read was PROBABLY
     * meant to be. Two passes, and which one wins is decided by distance rather
     * than by a fixed preference — because a shared-root answer is often a true but
     * useless one.
     *
     * This is the whole lesson of instance 1, and it is why the hint is worth its
     * thirty lines. The 61 lines that shipped were written because
     * `teleframe.backup.schedule.package_schedule` "had no config entry" — while
     * `teleframe.store.backup.schedule.package_schedule` was declared the whole
     * time, in a different file, under a prefix only a `mergeConfigFrom` call
     * reveals. The shared ancestor `teleframe.backup` is twenty-five edits from the
     * offending key and tells the reader nothing; the real one is six edits away.
     *
     * @param  array<string, true>  $declared
     */
    private static function nearestDeclared(string $key, array $declared): ?string
    {
        if (isset($declared[$key])) {
            return $key;
        }

        // Pass 1 — the deepest dotted ANCESTOR of the offending key. Always a real
        // hint, but often a shallow one.
        $ancestor = null;
        $ancestorSegments = -1;
        foreach (array_keys($declared) as $candidate) {
            if (! str_starts_with($key, $candidate.'.')) {
                continue;
            }
            $segments = substr_count($candidate, '.') + 1;
            if ($segments > $ancestorSegments) {
                $ancestor = $candidate;
                $ancestorSegments = $segments;
            }
        }

        // Pass 2 — the nearest declared key by edit distance, offered only when it
        // is close enough to be a plausible typo rather than a coincidence.
        $nearest = null;
        $nearestDistance = null;
        foreach (array_keys($declared) as $candidate) {
            $distance = levenshtein($key, $candidate);
            if ($distance > (int) floor(max(strlen($key), strlen($candidate)) * 0.4)) {
                continue;
            }
            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearest = $candidate;
                $nearestDistance = $distance;
            }
        }

        if ($ancestor !== null && ($nearest === null || levenshtein($key, $ancestor) <= $nearestDistance)) {
            return $ancestor;
        }

        return $nearest;
    }

    /** @param list<array<string, mixed>> $tolerated */
    private static function toleratedSummary(array $tolerated): string
    {
        if ($tolerated === []) {
            return 'nothing';
        }

        $parts = [];
        foreach ($tolerated as $read) {
            // The reason is stored whole; only its first sentence is printed, so the
            // green output stays readable and the full justification stays in the file.
            $why = (string) ($read['why'] ?? '');
            $first = rtrim((string) (preg_split('/(?<=\.)\s/', $why)[0] ?? $why));
            $parts[] = $read['key'].' @ '.$read['at']."\n              why: ".$first;
        }

        return implode("\n            ", $parts);
    }
}
