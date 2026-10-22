#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * friction-gate.php — ONE command that runs every friction gate this repository
 * already has, so finding friction stops being manual labour.
 *
 * =====================================================================
 * THE PROBLEM, MEASURED
 * =====================================================================
 *
 * `composer verify` runs four things: scan-credentials, the full phpunit suite,
 * phpstan, standalone-smoke. Everything else this repository already ships —
 * bin/verify-shard-partition.php, bin/check-public-surface.php,
 * bin/audit-fidelity.php, bin/reap-orphan-tests.php, and the regeneration
 * idempotence gate that AGENTS.md itself admits is "not currently invoked by any
 * workflow" — is a gate nobody runs by hand. The gates that FIND friction exist;
 * the ASSEMBLY does not. That is the whole defect.
 *
 * =====================================================================
 * WHY THE INVENTORY IS DISCOVERED AND NOT WRITTEN DOWN HERE
 * =====================================================================
 *
 * A hardcoded list of gate commands is a list that rots. The day CI grows a job
 * that runs a new gate, a hardcoded runner keeps reporting a clean bill of health
 * over a set of checks that no longer matches reality — and it does so loudly
 * and confidently, which is worse than not existing.
 *
 * So this file holds NO list of gates. It reads the two surfaces that already
 * enumerate them, and it holds only the CLAIMS (what each gate is CALLED, which
 * tier it belongs to, why it matters, how long it may take):
 *
 *   1. THE CI DEFINITIONS, through bin/ci-definitions.php — the same discovery
 *      that file already does for CI dialects. `.github/workflows/*.{yml,yaml}`
 *      plus `.gitlab-ci.yml`, both dialects flattened onto one shape, every
 *      `run:`/`script:` line scanned for a gate program.
 *   2. composer.json `scripts`, with `@alias` references RESOLVED. An alias is
 *      where a hardcoded list would rot first: `@guard` hides which gate it is,
 *      and if the alias is repointed at a different gate, an unresolved reader
 *      keeps reporting the old one forever.
 *
 * Every discovered gate must be CLAIMED by exactly one catalogue entry below. A
 * discovered gate with no claim is reported as UNCOVERED and FAILS THE RUN. That
 * is the anti-rot direction that matters: adding a gate to CI turns this runner
 * red until someone says which tier it is in and what it catches — which is the
 * conversation that would otherwise happen by never happening.
 *
 * A source that cannot be read is a FAILURE, never an empty inventory. "I read
 * zero gates and all of them passed" and "I read zero gates because the parser is
 * broken" are indistinguishable from the outside, so the runner refuses to report
 * the first one.
 *
 * NORMALIZATION IS DELIBERATELY LOSSY, AND SAYS SO. A gate identity is the
 * program plus the set of long flag NAMES in its command, with every flag VALUE
 * collapsed to `*`: `php bin/regenerate --out=/tmp/regen-a` and
 * `--out=/tmp/regen-b` are one gate, not two. Positional arguments and short
 * flags are dropped. `#`-leading fragments are comments and are skipped — a gate
 * inside a comment is documentation, not a gate. `--list` prints the lossy
 * normalization next to each claim so a human can see exactly what was matched.
 *
 * =====================================================================
 * TIERS
 * =====================================================================
 *
 *   fast   seconds-scale; run by default. The promise is that a bare
 *          `php bin/friction-gate.php` finishes well inside two minutes and every
 *          check in it is individually fast, so it can be run after every edit.
 *   full   affordable but slow; `--tier=full`. Composed checks (a build, then a
 *          gate over what it built) and anything measured in tens of seconds.
 *   ci     INVENTORY ONLY, NEVER RUN HERE. Gates that need a database container,
 *          a network, real Telegram credentials, or that AGENTS.md forbids
 *          running locally (the full un-sharded suite). They are listed so the
 *          inventory is honest and so nobody re-derives that they exist —
 *          pretending a gate does not exist is the failure mode this file
 *          exists to end, and silently SKIPPING it would be the same failure.
 *
 * =====================================================================
 * EVERY CHECK IS BOUNDED
 * =====================================================================
 *
 * Each check runs through proc_open with its output redirected to temp FILES (no
 * pipe, so no deadlock on a full pipe buffer) and is polled against a wall-clock
 * deadline. On expiry the child is SIGTERM'd, given one second of grace, then
 * SIGKILL'd, and the outcome is reported as TIMEOUT with exit 124. A timeout is
 * NEVER reported as a pass: a gate that was killed at the deadline verified
 * nothing, and "nothing" is not "clean".
 *
 * SEVERITY RANKING, worst first:
 *   BROKEN     the runner could not read or spawn the check — every other
 *              verdict it is about to print is suspect, so it goes first
 *   UNCOVERED  a gate this repository names has no catalogue entry
 *   TIMEOUT    started, never finished, verified nothing
 *   NO-VERDICT the gate exited with its OWN "I could not check" code — declared
 *              per check, e.g. scan-credentials 2 = usage, 3 = scan INCOMPLETE.
 *              This is ranked above FAIL because a truncated scan that finds
 *              nothing is a hole, not a clean tree.
 *   FAIL       an ordinary non-zero verdict
 *
 * AND THE ONE DIRECTION THAT IS REPORTED RATHER THAN FAILED: a claim this runner
 * makes for a gate that NOTHING else in the repository names is printed as
 * ORPHANED and costs nothing. Asymmetric on purpose — an unnamed gate is a hole,
 * an uninvoked gate is a fact about the repository, and a runner that is red on
 * a clean tree is a runner that gets switched off.
 *
 * ZERO REGEX. bin/ enforces the project's zero-regex rule strictly (see
 * bin/check-public-surface.php:44); every matcher below is a string function.
 */
$root = dirname(__DIR__);

// bin/ci-definitions.php needs composer's autoloader for Symfony's Yaml, which
// is where the CI discovery comes from. If it cannot be loaded the inventory
// cannot be read, and that is a failure rather than an empty run.
if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "friction-gate: vendor/autoload.php is missing — run `composer install` first.\n");
    fwrite(STDERR, "              The CI definitions are read through bin/ci-definitions.php, which needs the\n");
    fwrite(STDERR, "              autoloader; without it the inventory would read as EMPTY, and an empty\n");
    fwrite(STDERR, "              inventory that reports success is a green gate that proved nothing.\n");
    exit(1);
}

require_once $root.'/vendor/autoload.php';
require_once __DIR__.'/ci-definitions.php';

/** Exit code the runner reports for a check it killed at its deadline. */
const FRICTION_TIMEOUT_EXIT = 124;

/** Longest output tail, in characters, quoted under a ranked failure. */
const FRICTION_TAIL_CHARS = 1400;

/** How deep composer script aliases (@guard -> @php bin/...) may nest. */
const FRICTION_ALIAS_DEPTH = 8;

// ===========================================================================
// DISCOVERY — what gates does this repository actually name?
// ===========================================================================

/**
 * Every gate program token, normalized into a stable identity.
 *
 * A "gate program" is anything that can carry a pass/fail verdict in this
 * repository: a PHP file under bin/, a shell gate under bin/, a composer binary
 * under vendor/bin/, a vendored .phar, or the bare `phpunit`/`phpstan` composer
 * scripts. Comments, prose and incidental path mentions are not gates, and a
 * gate named in a `#` comment is documentation rather than something CI runs.
 *
 * @return list<array{id: string, program: string, flags: list<string>, source: string, sample: string}>
 */
function frictionGateInvocations(string $root): array
{
    $found = [];

    $sources = frictionCommandSources($root);

    foreach ($sources as $source) {
        foreach (frictionSplitFragments($source['text']) as $fragment) {
            if (str_starts_with(ltrim($fragment), '#')) {
                continue;
            }

            $invocation = frictionGateFromFragment($fragment, $source['name']);

            if ($invocation !== null) {
                $found[] = $invocation;
            }
        }
    }

    return $found;
}

/**
 * Every command surface this repository has: CI jobs in both dialects, and the
 * composer scripts with their `@alias` references resolved.
 *
 * @return list<array{name: string, text: string}>
 */
function frictionCommandSources(string $root): array
{
    $sources = [];

    // ---- CI definitions, through the vocabulary file that already normalizes
    // the two dialects onto one shape.
    $definitions = ciDefinitions($root);

    foreach ($definitions as $definition) {
        foreach ($definition['commands'] as $job => $text) {
            if (trim($text) === '') {
                continue;
            }

            $sources[] = [
                'name' => $definition['path'].':'.$job,
                'text' => $text,
            ];
        }
    }

    // ---- composer scripts. The path is not hardcoded: composer.json sits beside
    // composer.lock and the package manifest, and a wrong constant here would
    // read zero scripts and look like an empty inventory.
    $composerPath = $root.'/composer.json';

    if (is_file($composerPath)) {
        $manifest = json_decode((string) file_get_contents($composerPath), true);

        if (is_array($manifest) && isset($manifest['scripts']) && is_array($manifest['scripts'])) {
            foreach (array_keys($manifest['scripts']) as $name) {
                $resolved = frictionResolveComposerScript((string) $name, $manifest['scripts'], 0);

                if ($resolved !== []) {
                    $sources[] = [
                        'name' => 'composer.json:'.$name,
                        'text' => implode("\n", $resolved),
                    ];
                }
            }
        }
    }

    return $sources;
}

/**
 * One composer script, with `@alias` references followed and `@php` stripped.
 *
 * The alias is the rot hazard this exists for: `@guard` says nothing about which
 * gate it runs, so a reader that does not follow it reports the gate it was
 * written against rather than the gate that runs. Depth-capped and cycle-guarded,
 * because a self-referential alias must terminate rather than recurse.
 *
 * @param  array<string, mixed>  $scripts
 * @return list<string>
 */
function frictionResolveComposerScript(string $name, array $scripts, int $depth): array
{
    if ($depth > FRICTION_ALIAS_DEPTH || ! array_key_exists($name, $scripts)) {
        return [];
    }

    $body = $scripts[$name];

    if (is_string($body)) {
        $body = [$body];
    }

    if (! is_array($body)) {
        return [];
    }

    $lines = [];

    foreach ($body as $line) {
        if (! is_string($line)) {
            continue;
        }

        $line = trim($line);

        if ($line === '' || $line === '@php') {
            continue;
        }

        if (str_starts_with($line, '@')) {
            $alias = substr($line, 1);

            // `@php -d x=y script` and friends keep the remainder as a literal
            // fragment; only a bare `@name` is an alias reference.
            if (str_contains($alias, ' ')) {
                $lines[] = substr($alias, strpos($alias, ' ') + 1);

                continue;
            }

            foreach (frictionResolveComposerScript($alias, $scripts, $depth + 1) as $resolved) {
                $lines[] = $resolved;
            }

            continue;
        }

        $lines[] = $line;
    }

    return $lines;
}

/**
 * Split a command surface into the fragments that can each carry one gate.
 *
 * A line that runs two gates (`a && b`) must yield two identities, not one, so
 * the shell separators are the cut points.
 *
 * @return list<string>
 */
function frictionSplitFragments(string $text): array
{
    // Every shell separator is normalized to a newline and the text is then split
    // on newlines. A `|` or `;` inside a quoted string therefore splits one
    // fragment into two, which can only ever LOSE a gate, never invent one, and
    // the coverage check would report the loss as UNCOVERED rather than hide it.
    $normalized = str_replace(['&&', '||', ';', '|'], "\n", $text);

    $fragments = [];

    foreach (explode("\n", $normalized) as $fragment) {
        $fragment = trim($fragment);

        if ($fragment !== '') {
            $fragments[] = $fragment;
        }
    }

    return $fragments;
}

/**
 * The gate identity of one command fragment, or null when it names no gate.
 *
 * @return array{id: string, program: string, flags: list<string>, source: string, sample: string}|null
 */
function frictionGateFromFragment(string $fragment, string $source): ?array
{
    // Quoted text is DATA, never a command: an `echo "run php bin/regenerate"`
    // message inside a CI run-block names a gate the way a road sign names a
    // city. It is stripped before tokenizing, or every explanatory message in a
    // workflow becomes an inventory entry.
    $clean = frictionStripQuoted($fragment);

    // Redirection targets and command substitutions are neutralized before
    // tokenizing so `> /tmp/x.log` cannot be mistaken for a positional gate
    // argument and `$(...)` cannot inject a phantom gate into the inventory.
    $clean = str_replace(["\t", "\r", "\n", '2>&1', '$(', '`'], ' ', $clean);

    $tokens = array_values(array_filter(
        explode(' ', $clean),
        static fn (string $token): bool => $token !== '',
    ));

    $programIndex = null;

    foreach ($tokens as $index => $token) {
        if (frictionIsGateProgram($token)) {
            $programIndex = $index;

            break;
        }
    }

    if ($programIndex === null) {
        return null;
    }

    $program = $tokens[$programIndex];
    $flags = [];

    foreach (array_slice($tokens, $programIndex + 1) as $token) {
        // `>` and `<` open a redirection; a redirection target is not a flag and
        // must never become part of an identity.
        if (str_starts_with($token, '>') || str_starts_with($token, '<')) {
            continue;
        }

        if (! str_starts_with($token, '--')) {
            continue;
        }

        $name = str_contains($token, '=') ? substr($token, 0, strpos($token, '=')) : $token;

        if (! in_array($name, $flags, true)) {
            $flags[] = $name;
        }
    }

    sort($flags);

    return [
        'id' => trim($program.' '.implode(' ', $flags)),
        'program' => $program,
        'flags' => $flags,
        'source' => $source,
        'sample' => trim($fragment),
    ];
}

/**
 * A command fragment with every quoted span blanked out.
 *
 * `echo "… php bin/regenerate --ship, then commit …"` is a message that MENTIONS
 * a gate; it does not run one. Without this, every explanatory message in every
 * workflow becomes an inventory entry and the inventory becomes noise. An
 * unbalanced quote blanks the rest of the line, which can only lose a gate — and
 * a lost gate is caught by the coverage check, not hidden by it.
 */
function frictionStripQuoted(string $fragment): string
{
    $out = '';
    $quote = null;

    foreach (str_split($fragment) as $char) {
        if ($quote === null) {
            if ($char === '"' || $char === "'") {
                $quote = $char;
                $out .= ' ';

                continue;
            }

            $out .= $char;

            continue;
        }

        if ($char === $quote) {
            $quote = null;
            $out .= ' ';
        }
    }

    return $out;
}

/**
 * Is this token a program that can carry a pass/fail verdict?
 *
 * Deliberately narrow. `bin/public-manifest.txt` is not a gate, `git` is not a
 * gate, and `docs/decisions.md` in a shell command is not a gate — a wide rule
 * would put the whole repository's file list into the inventory.
 */
function frictionIsGateProgram(string $token): bool
{
    $token = ltrim($token, "'\"");
    $basename = str_contains($token, '/') ? substr($token, strrpos($token, '/') + 1) : $token;

    if (str_starts_with($token, 'bin/')) {
        return str_ends_with($token, '.php')
            || str_ends_with($token, '.sh')
            || ! str_contains($basename, '.');
    }

    if (str_starts_with($token, 'vendor/bin/')) {
        return true;
    }

    if ($basename === 'phpstan.phar') {
        return true;
    }

    return in_array($basename, ['phpunit', 'phpstan'], true);
}

/**
 * The distinct gate identities the surfaces name, identity => where it was seen.
 *
 * @return array<string, array{sources: list<string>, samples: list<string>}>
 */
function frictionDiscoveredGates(string $root): array
{
    $discovered = [];

    foreach (frictionGateInvocations($root) as $invocation) {
        $id = $invocation['id'];

        if (! isset($discovered[$id])) {
            $discovered[$id] = ['sources' => [], 'samples' => []];
        }

        if (! in_array($invocation['source'], $discovered[$id]['sources'], true)) {
            $discovered[$id]['sources'][] = $invocation['source'];
        }

        if (! in_array($invocation['sample'], $discovered[$id]['samples'], true)) {
            $discovered[$id]['samples'][] = $invocation['sample'];
        }
    }

    $discovered += frictionIndirectGates($root, $discovered);

    ksort($discovered);

    return $discovered;
}

/**
 * Gates reached INDIRECTLY: a CI job runs a repo script, and that script runs
 * the gate.
 *
 * WHY THIS EXISTS — and it is not hypothetical. `bin/verify-shard-partition.php`
 * appears in .github/workflows/verify-php.yml exactly once, at line 23, and that
 * line is a COMMENT. A resolver that reads only CI command text therefore
 * concludes the partition proof runs nowhere, and reports a live, correctly
 * wired gate as orphaned. That is worse than reporting nothing: it teaches the
 * operator that this line of the report is noise, and the one time it is not
 * noise is the time it stops being read.
 *
 * The chain is real and short:
 *   .github/workflows/verify-php.yml:602  runs  bin/run-phpunit-shard.php
 *   bin/run-phpunit-shard.php:91          invokes bin/verify-shard-partition.php
 *
 * HOW IT STAYS HONEST. It follows the hop only into files that actually exist
 * and are readable, never into a file a command merely mentions in prose, and
 * it records the FULL CHAIN in `sources` so a human can audit why a gate was
 * considered covered. Comment text is not an invocation — treating a comment as
 * coverage is the silent-green failure this repository has already been bitten
 * by, which is why bin/ci-definitions.php exists.
 *
 * Depth is capped and cycles are tracked: a gate cannot cover itself, and a
 * mutual pair must terminate rather than recurse.
 *
 * @param  array<string, array{sources: list<string>, samples: list<string>}>  $discovered
 * @return array<string, array{sources: list<string>, samples: list<string>}>
 */
function frictionIndirectGates(string $root, array $discovered): array
{
    $maxDepth = 3;
    $found = [];
    $walk = [];

    foreach (array_keys($discovered) as $id) {
        $walk[] = ['from' => (string) $id, 'seen' => [(string) $id], 'chain' => [(string) $id], 'depth' => 1];
    }

    while ($walk !== []) {
        $step = array_shift($walk);
        $from = $step['from'];
        $seen = $step['seen'];
        $chain = $step['chain'];
        $depth = $step['depth'];

        if ($depth >= $maxDepth) {
            continue;
        }

        // The id is repo-relative (e.g. `bin/run-phpunit-shard.php`), so it
        // joins the root directly. Stripping the directory with basename()
        // points at a path that does not exist and silently follows nothing —
        // which is exactly the kind of quiet no-op that makes a gate lie.
        $file = $root.'/'.$from;

        // Only a real, readable repo file can be followed into. A bare mention
        // in CI prose is not a hop, and a path that does not exist proves
        // nothing about what runs.
        if (! is_file($file) || ! is_readable($file)) {
            continue;
        }

        $text = @file_get_contents($file);

        if ($text === false) {
            continue;
        }

        foreach (frictionSplitFragments($text) as $fragment) {
            // A fragment that is a comment is prose about a gate, not a gate.
            // Counting it as coverage is the silent-green failure this runner
            // exists to prevent.
            if (str_starts_with(ltrim($fragment), '#') || str_starts_with(ltrim($fragment), '*')) {
                continue;
            }

            $invocation = frictionGateFromFragment($fragment, basename($file));

            if ($invocation === null) {
                continue;
            }

            $id = $invocation['id'];

            if ($id === $from || in_array($id, $seen, true)) {
                continue;   // self-reference or cycle
            }

            // Only a plausible repo SCRIPT is a hop target. The fragment parser
            // also yields bare directory names and vendor binaries
            // (`bin/`, `phpstan`); treating those as gates invents identities
            // that nothing claims, which turns a precise report into noise.
            if (preg_match('#^[A-Za-z0-9_][A-Za-z0-9_./-]*\.(php|sh|py)$#', $id) !== 1) {
                continue;
            }

            if (! is_file($root.'/'.$id)) {
                continue;   // naming a path that does not exist proves nothing
            }

            $chain[] = $id;
            $note = 'reached via '.implode(' -> ', $chain);

            $walk[] = ['from' => $id, 'seen' => array_merge($seen, [$id]), 'chain' => $chain, 'depth' => $depth + 1];

            if (isset($discovered[$id])) {
                if (! in_array($note, $discovered[$id]['samples'], true)) {
                    $discovered[$id]['samples'][] = $note;
                }

                continue;
            }

            if (! isset($found[$id])) {
                $found[$id] = ['sources' => [], 'samples' => []];
            }

            if (! in_array($note, $found[$id]['samples'], true)) {
                $found[$id]['samples'][] = $note;
            }
        }
    }

    return $found;
}

// ===========================================================================
// THE CATALOGUE — the CLAIMS. No list of gates lives here; only what each
// discovered gate is CALLED, which tier it belongs to, why it matters, and how
// long it is allowed to take.
// ===========================================================================

/**
 * The claims. One entry per gate this repository names.
 *
 * Keys, and why each exists:
 *   name        the label in the report
 *   tier        fast | full | ci — see the header
 *   why         ONE line: the class of friction this check catches
 *   command     how it is run HERE (a composite command is allowed; a `full`
 *               check that builds a tree and gates it needs one)
 *   timeout     wall-clock seconds before the child is killed and the check is
 *               reported TIMEOUT
 *   covers      the discovered identities this entry claims. Every discovered
 *               identity must be covered by exactly one entry; an uncovered one
 *               fails the run rather than being skipped
 *   incomplete  exit codes that mean "I could not check", ranked above FAIL
 *
 * @return list<array{name: string, tier: string, why: string, command: string, timeout: int, covers: list<string>, incomplete: list<int>}>
 */
function frictionCatalogue(): array
{
    $regenA = sys_get_temp_dir().'/teleframe-friction-regen-a';
    $regenB = sys_get_temp_dir().'/teleframe-friction-regen-b';
    $export = sys_get_temp_dir().'/teleframe-friction-export';

    return [
        [
            'name' => 'standalone-smoke',
            'tier' => 'fast',
            'why' => 'a module that cannot be constructed without a host app behind it',
            'command' => escapeshellarg(PHP_BINARY).' bin/standalone-smoke.php',
            'timeout' => 60,
            'covers' => ['bin/standalone-smoke.php'],
            'incomplete' => [],
        ],
        [
            'name' => 'credential-guard',
            'tier' => 'fast',
            'why' => 'a session string in the working tree or anywhere in git history',
            'command' => escapeshellarg(PHP_BINARY).' bin/scan-credentials.php',
            'timeout' => 180,
            'covers' => ['bin/scan-credentials.php'],
            // 2 = usage error, 3 = scan INCOMPLETE. Neither is a clean tree.
            'incomplete' => [2, 3],
        ],
        [
            'name' => 'static-analysis',
            'tier' => 'fast',
            'why' => 'a type error, or a banned preg_* call, anywhere under src/',
            'command' => escapeshellarg(PHP_BINARY).' vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress',
            'timeout' => 300,
            'covers' => ['phpstan.phar'],
            'incomplete' => [],
        ],
        [
            'name' => 'claim-drift-report',
            'tier' => 'fast',
            'why' => 'docs and ledgers asserting a surface that the tree has since outgrown; a REPORT, exits 0 unless --strict',
            'command' => escapeshellarg(PHP_BINARY).' bin/check-claim-drift.php --path=docs',
            'timeout' => 180,
            'covers' => [
                // The fragment parser splits an argument at `=`, so the identity
                // a composer script contributes is the flag NAME
                // (`bin/check-claim-drift.php --path`), never the flag WITH its
                // value. Listing the `--path=docs` spelling here would be a
                // claim nothing ever satisfies, and the runner would report the
                // entry orphaned forever.
                'bin/check-claim-drift.php --path',
                'bin/friction-gate.php',
                'bin/friction-gate.php --list',
                'bin/friction-gate.php --tier',
                'bin/ci-definitions.php',
            ],
            'incomplete' => [],
            // Per-IDENTITY exemptions, not a blanket one: the claim-drift gate
            // itself IS a CI job (wired into verify-php.yml by goal 46.84). Only
            // the bare all-tiers invocation and the --tier dispatch are local.
            'local_only' => [
                'bin/friction-gate.php' => 'CI runs bin/friction-gate.php --list, and the checks themselves by name. The BARE all-tiers invocation is what `composer friction` wraps locally; running it in CI would re-prove seven checks verify-php.yml already runs.',
                'bin/friction-gate.php --tier' => 'CI does not dispatch by --tier; it names the checks it wants, so a red one is visible in the run that failed it. --tier is the local knob for running one tier without the rest.',
            ],
        ],
        [
            'name' => 'identity-invariants',
            'tier' => 'fast',
            'why' => 'a documented requirement violated by the code: an update identity that does '
                .'not identify one update (NEW-1 silently discarded 499 of 500 bus messages), or '
                .'an entity length covering trailing whitespace (D6, Telegram rtrim rule R3)',
            'command' => escapeshellarg(PHP_BINARY).' bin/check-identity-invariants.php',
            'timeout' => 120,
            'covers' => ['bin/check-identity-invariants.php'],
            // 3 = INCOMPLETE, an invariant could not be exercised. That is not a
            // clean tree: a gate that cannot check is not a passing gate.
            'incomplete' => [3],
        ],
        [
            'name' => 'test-wallclock-budget',
            'tier' => 'fast',
            'why' => 'a tests/ area whose wall-clock cost is not under a declared, MEASURED budget. '
                .'Seven areas are ~83% of 2.4h because the 61-file migration dial re-runs per test '
                .'METHOD; a budget nobody checks is how that came back',
            'command' => escapeshellarg(PHP_BINARY).' bin/check-test-wallclock-budget.php',
            'timeout' => 60,
            // Only the RUNNABLE identity. Listing ci/shard-budgets.json here
            // would be a claim nothing can ever satisfy — it is data, not
            // something a composer script or CI job executes — and the runner
            // would report this gate orphaned forever. That trap is exactly
            // what the claim-drift entry's comment above documents.
            'covers' => ['bin/check-test-wallclock-budget.php'],
            'incomplete' => [3],
        ],
        [
            'name' => 'shard-partition',
            'tier' => 'fast',
            'why' => 'a CI shard list that silently drops tests, or runs one twice',
            'command' => escapeshellarg(PHP_BINARY).' bin/verify-shard-partition.php',
            'timeout' => 180,
            'covers' => ['bin/verify-shard-partition.php'],
            'incomplete' => [],
        ],
        [
            'name' => 'orphan-reaper',
            'tier' => 'fast',
            'why' => 'a phpunit leaked by an interrupted run still holding the machine',
            'command' => escapeshellarg(PHP_BINARY).' bin/reap-orphan-tests.php',
            'timeout' => 60,
            'covers' => ['bin/reap-orphan-tests.php'],
            'incomplete' => [],
            'local_only' => 'A leaked phpunit is a MACHINE-hygiene problem and this host is where it leaks. A CI runner starts clean, kills nothing, and would prove nothing: running it there is a green that certifies an absence, which is the shape of gate this file exists to distrust.',
        ],
        [
            'name' => 'public-surface-enumerable',
            'tier' => 'fast',
            'why' => 'the publish allowlist is missing or unreadable, so the gate can enumerate nothing (exit 2)',
            'command' => escapeshellarg(PHP_BINARY).' bin/check-public-surface.php --list > /dev/null',
            'timeout' => 60,
            // The SOURCE tree is expected red by design — this repository IS the
            // private repo and holds the private files on purpose (see
            // bin/export-public-repo.sh). What must be proven cheaply is that
            // the gate can still ENUMERATE; the exported tree is gated in `full`.
            'covers' => ['bin/check-public-surface.php --list'],
            'incomplete' => [],
        ],
        [
            'name' => 'regen-idempotence',
            'tier' => 'full',
            'why' => 'regeneration is not reproducible, or the committed generated tree drifted from a fresh run',
            'command' => 'rm -rf '.escapeshellarg($regenA).' '.escapeshellarg($regenB)
                .' && '.escapeshellarg(PHP_BINARY).' bin/regenerate --out='.escapeshellarg($regenA)
                .' && '.escapeshellarg(PHP_BINARY).' bin/regenerate --out='.escapeshellarg($regenB)
                .' && diff -r '.escapeshellarg($regenA.'/src/Schema/Generated').' '.escapeshellarg($regenB.'/src/Schema/Generated')
                .' && diff -r '.escapeshellarg($regenA.'/src/Schema/Generated').' '.escapeshellarg('src/Schema/Generated'),
            'timeout' => 900,
            'covers' => ['bin/regenerate --out'],
            'incomplete' => [],
        ],
        [
            'name' => 'public-surface-export',
            'tier' => 'full',
            'why' => 'a path in the exported public tree is outside the allowlist or on the private strip set',
            'command' => 'rm -rf '.escapeshellarg($export)
                .' && bash bin/export-public-repo.sh '.escapeshellarg($export).' > /dev/null'
                .' && '.escapeshellarg(PHP_BINARY).' bin/check-public-surface.php --tree='.escapeshellarg($export)
                .'; code=$?; rm -rf '.escapeshellarg($export).'; exit $code',
            'timeout' => 600,
            'covers' => ['bin/export-public-repo.sh', 'bin/check-public-surface.php --tree'],
            'incomplete' => [],
        ],

        // ---- INVENTORY ONLY. Named by CI or composer, run by nobody here.
        [
            'name' => 'phpunit-suite',
            'tier' => 'ci',
            'why' => 'a regression somewhere in the suite (AGENTS.md forbids the un-sharded local run; CI shards it)',
            'command' => '',
            'timeout' => 0,
            'covers' => ['phpunit', 'vendor/bin/phpunit'],
            'incomplete' => [],
        ],
        [
            'name' => 'shard-runner',
            'tier' => 'ci',
            'why' => 'one CI shard of the partitioned suite',
            'command' => '',
            'timeout' => 0,
            'covers' => ['bin/run-phpunit-shard.php'],
            'incomplete' => [],
        ],
        [
            'name' => 'shard-rollup',
            'tier' => 'ci',
            'why' => 'a shard published no JUnit report, so the green build covered less than the suite',
            'command' => '',
            'timeout' => 0,
            'covers' => ['bin/rollup-shard-reports.php'],
            'incomplete' => [],
        ],
        [
            'name' => 'ship-dial-drift',
            'tier' => 'ci',
            'why' => 'the curated ship dial drifted from the committed namespace migrations',
            'command' => '',
            'timeout' => 0,
            'covers' => ['bin/regenerate --out --ship'],
            'incomplete' => [],
        ],
        [
            'name' => 'fidelity-audit',
            'tier' => 'ci',
            'why' => 'our relational schema diverges from Telegram s own data model (needs a database)',
            'command' => '',
            'timeout' => 0,
            'covers' => ['bin/audit-fidelity.php --check --engine'],
            'incomplete' => [],
        ],
        [
            'name' => 'host-install',
            'tier' => 'ci',
            'why' => 'the package does not install and boot in a clean host app (needs the network)',
            'command' => '',
            'timeout' => 0,
            'covers' => ['bin/prove-host-install.php'],
            'incomplete' => [],
        ],
        [
            'name' => 'public-pr-gate',
            'tier' => 'ci',
            'why' => 'a pull request publishes a path the manifest does not allow',
            'command' => '',
            'timeout' => 0,
            'covers' => ['bin/verify-public-pr.php --quiet'],
            'incomplete' => [],
        ],
        [
            'name' => 'live-warm',
            'tier' => 'ci',
            'why' => 'a live account cannot reach Telegram at all (needs real credentials; opt-in by policy)',
            'command' => '',
            'timeout' => 0,
            'covers' => ['bin/warm-live-host.php'],
            'incomplete' => [],
        ],
        [
            'name' => 'live-e2e',
            'tier' => 'ci',
            'why' => 'a live MTProto or Bot API call regressed end to end (needs .env credentials; opt-in by policy)',
            'command' => '',
            'timeout' => 0,
            'covers' => ['bin/test-e2e.php'],
            'incomplete' => [],
            'local_only' => 'Needs a real Telegram session in .env, which by policy never reaches a hosted runner (AGENTS.md, "Known gaps", and the credential guard). The operator runs it locally when they choose to; CI must never be the thing that holds a live credential.',
        ],
        // ---- THE PUBLISH BASELINE, AND WHY IT IS CLAIMED AT ALL.
        // Discovered from .gitlab-ci.yml:publish-public. The job that resolves
        // "which private commit produced this public tree" was answering an
        // unbounded question with a 50-commit search, so its answer EXPIRED; the
        // repository crossed 55 commits, nothing matched, and the mirror refused
        // to publish — which silently darkened every CI gate routed through it
        // (measured 2026-10-18, goal 46.82). A gate that stops answering is not
        // a gate, so the resolution is claimed here and run locally against the
        // LIVE public branch, including the case that must refuse.
        [
            'name' => 'publish-baseline',
            'tier' => 'fast',
            'why' => 'the publish-baseline resolution can no longer tell a stale public copy from a moved one, so the mirror refuses to publish and every CI gate behind it goes dark with no red',
            'command' => 'bash bin/verify-publish-baseline.sh',
            'timeout' => 600,
            'covers' => ['bin/resolve-publish-baseline.sh'],
            // 2 = usage error, or the public ref is not fetched. Neither is a
            // green tree; a silent skip here is exactly how the 2026-10-18
            // blackout lasted three days unnoticed.
            'incomplete' => [2],
        ],
        [
            'name' => 'mutation-gate',
            'tier' => 'ci',
            'why' => 'a test that cannot fail is not a test, and only a mutant knows which of ours cannot (composer scripts `mutants` / `mutants:gate`; heavy, opt-in by policy)',
            'command' => '',
            'timeout' => 0,
            'covers' => [
                'bin/run-mutation-gate.sh',
                'bin/infection-preflight.php',
                'bin/infection-result-assert.php',
            ],
            'incomplete' => [],
            'local_only' => 'Opt-in by policy and deliberately expensive: a full infection run is minutes per slice, and folding it into every push would buy nothing a scheduled mutation run does not buy better. `composer mutants` / `composer mutants:gate` are the deliberate entry points; see bin/run-mutation-gate.sh for the slice configs.',
        ],
    ];
}

/**
 * The discovered identities no catalogue entry claims.
 *
 * A gate the repository names but nobody claimed is a gate that will stop being
 * run without anyone noticing. It is reported, and it fails the run.
 *
 * @param  array<string, array{sources: list<string>, samples: list<string>}>  $discovered
 * @param  list<array<string, mixed>>  $catalogue
 * @return list<array{id: string, sources: list<string>, samples: list<string>}>
 */
function frictionUncovered(array $discovered, array $catalogue): array
{
    $claimed = [];

    foreach ($catalogue as $entry) {
        foreach ((array) $entry['covers'] as $id) {
            $claimed[(string) $id] = $entry['name'];
        }
    }

    $uncovered = [];

    foreach ($discovered as $id => $seen) {
        if (! isset($claimed[$id])) {
            $uncovered[] = ['id' => $id, 'sources' => $seen['sources'], 'samples' => $seen['samples']];
        }
    }

    return $uncovered;
}

/**
 * Gates that are named, and claimed, and run by NOTHING.
 *
 * WHY THIS IS A THIRD CLASS AND NOT A TWIST ON `frictionUncovered()`.
 * `composer.json` is one of this runner's command surfaces, so a gate named only
 * by a composer script counts as WIRED. That is true of the manifest and false of
 * the repository: `composer verify` is `@guard | @test | @stan | @smoke`, and a
 * script outside that chain is a script nobody types.
 *
 * MEASURED, 2026-10-18, goal 46.84. Four gates were named by composer.json and by
 * no workflow and no CI file at all:
 *
 *   bin/check-test-wallclock-budget.php   bin/check-identity-invariants.php
 *   bin/check-claim-drift.php             bin/reap-orphan-tests.php
 *
 * and `--list` printed `uncovered: 0  orphaned: 0` and exited 0 for all four.
 * One of them — the wall-clock budget gate — was at that moment RED on the clean
 * committed tree, for four days, and CI was structurally incapable of noticing.
 * That is the worst failure shape this file exists to prevent: a gate that has
 * stopped being run, reported as one that is wired.
 *
 * WHY IT FAILS AND `ORPHANED` DOES NOT. ORPHANED is a gate this runner claims
 * that nothing else names — the operator is already looking for it. DEAD is the
 * inverse: this repository says it matters, and nothing runs it. Silence is the
 * defect, so it is a failure.
 *
 * THE EXEMPTION, and it is narrow. A gate that genuinely cannot run in CI says
 * so ON ITS CATALOGUE ENTRY, with a reason, and the exemption is printed on every
 * run. An unexplained exemption is how a gate dies quietly, which is the sentence
 * bin/check-test-wallclock-budget.php already uses for `blocked_by`; the same rule
 * is applied here rather than invented twice.
 *
 * @param  array<string, array{sources: list<string>, samples: list<string>}>  $discovered
 * @param  list<array<string, mixed>>  $catalogue
 * @return list<array{id: string, sources: list<string>, samples: list<string>, exempt: bool, why: string|null}>
 */
function frictionDeadGates(string $root, array $discovered, array $catalogue): array
{
    // The CI half of the command surfaces: everything that is not composer.json.
    // A gate is alive if ANY of these name it, directly or through the
    // indirect-chain resolver, which records the workflow at the head of its
    // chain.
    $ciText = [];
    foreach (frictionCommandSources($root) as $source) {
        $name = (string) ($source['name'] ?? '');
        if (! str_starts_with($name, 'composer.json:')) {
            $ciText[] = (string) ($source['text'] ?? '');
        }
    }
    $ciBlob = implode("\n", $ciText);

    $covering = [];
    foreach ($catalogue as $entry) {
        foreach ((array) ($entry['covers'] ?? []) as $id) {
            $covering[(string) $id] = $entry;
        }
    }

    $dead = [];

    foreach ($discovered as $id => $seen) {
        $fromCi = false;
        $composerScripts = [];

        foreach ($seen['sources'] as $source) {
            if (str_starts_with((string) $source, 'composer.json:')) {
                $composerScripts[] = substr((string) $source, strlen('composer.json:'));

                continue;
            }
            $fromCi = true;
        }

        if ($fromCi || $composerScripts === []) {
            continue;
        }

        $reached = false;
        foreach ($composerScripts as $script) {
            foreach (['composer '.$script, 'composer run '.$script, 'composer run-script '.$script] as $form) {
                if (str_contains($ciBlob, $form)) {
                    $reached = true;
                    break 2;
                }
            }
        }

        if ($reached) {
            continue;
        }

        $entry = $covering[$id] ?? null;
        $why = '';
        if (is_array($entry)) {
            $declared = $entry['local_only'] ?? null;
            // A string exempts the whole entry; an array exempts only the
            // identities it names, which is what a catalogue entry that covers
            // several gates of different natures needs.
            if (is_string($declared)) {
                $why = trim($declared);
            } elseif (is_array($declared)) {
                $why = isset($declared[$id]) && is_string($declared[$id])
                    ? trim($declared[$id])
                    : '';
            }
        }
        $exempt = $why !== '';

        $dead[] = [
            'id' => $id,
            'sources' => $seen['sources'],
            'samples' => $seen['samples'],
            'exempt' => $exempt,
            'why' => $exempt ? $why : null,
        ];
    }

    return $dead;
}

/**
 * Catalogue entries no CI job and no composer script names: gates that exist,
 * are run HERE, and are run by nothing else in this repository.
 *
 * The mirror of `frictionUncovered()`, and deliberately NOT a failure. The
 * asymmetry is the point: a gate the repository names but nobody claims is a gate
 * that silently stopped being run, which is a hole this runner must fail on. A
 * gate this runner claims that nothing else names is not a hole — it is a gate
 * the operator was already looking for — so it is REPORTED, loudly and by name,
 * and never counted as a failure. A runner that is red on a clean tree is a
 * runner that gets switched off.
 *
 * This is not hypothetical: `bin/verify-shard-partition.php` is named by exactly
 * one line in this repository's CI, and that line is a comment in
 * .github/workflows/verify-php.yml:23. No job runs it. That is the friction this
 * whole file was written to surface, so it is printed on every run.
 *
 * @param  array<string, array{sources: list<string>, samples: list<string>}>  $discovered
 * @param  list<array<string, mixed>>  $catalogue
 * @return list<array{name: string, id: string, tier: string}>
 */
function frictionOrphanedClaims(array $discovered, array $catalogue): array
{
    $orphaned = [];

    foreach ($catalogue as $entry) {
        $names = [];

        foreach ((array) $entry['covers'] as $id) {
            if (! isset($discovered[(string) $id])) {
                $names[] = (string) $id;
            }
        }

        if ($names !== []) {
            $orphaned[] = [
                'name' => (string) $entry['name'],
                'id' => implode(', ', $names),
                'tier' => (string) $entry['tier'],
            ];
        }
    }

    return $orphaned;
}

// ===========================================================================
// EXECUTION — bounded, and a timeout is never a pass
// ===========================================================================

/**
 * Run one check with a hard wall-clock deadline.
 *
 * Output goes to temp FILES rather than pipes on purpose: a pipe whose 64K
 * buffer fills while the parent is sleeping on the deadline deadlocks, and the
 * deadlock would look exactly like a slow gate.
 *
 * @param  array<string, mixed>  $check
 * @return array<string, mixed>
 */
function frictionRunCheck(array $check, string $root): array
{
    $outFile = tempnam(sys_get_temp_dir(), 'friction-out-');
    $errFile = tempnam(sys_get_temp_dir(), 'friction-err-');

    $descriptors = [
        0 => ['file', '/dev/null', 'r'],
        1 => ['file', (string) $outFile, 'w'],
        2 => ['file', (string) $errFile, 'w'],
    ];

    $started = microtime(true);

    $process = @proc_open(
        ['bash', '-c', (string) $check['command']],
        $descriptors,
        $pipes,
        $root,
        frictionChildEnvironment($root),
    );

    if (! is_resource($process)) {
        return [
            'name' => $check['name'],
            'tier' => $check['tier'],
            'outcome' => 'BROKEN',
            'exit' => null,
            'why' => (string) $check['why'],
            'seconds' => microtime(true) - $started,
            'detail' => 'proc_open failed — the check could not be spawned at all',
            'output' => '',
        ];
    }

    $deadline = $started + max(1, (int) $check['timeout']);
    $timedOut = false;
    $lastExit = 0;

    while (true) {
        $status = proc_get_status($process);

        if (! $status['running']) {
            $lastExit = (int) $status['exitcode'];

            break;
        }

        if (microtime(true) >= $deadline) {
            $timedOut = true;

            break;
        }

        usleep(20000);
    }

    if ($timedOut) {
        // SIGTERM, one second of grace, then SIGKILL. A gate that ignores
        // SIGTERM would otherwise hold the runner open forever, which is the one
        // outcome this file is not allowed to produce.
        proc_terminate($process, 15);
        $graceUntil = microtime(true) + 1.0;

        while (microtime(true) < $graceUntil) {
            $status = proc_get_status($process);

            if (! $status['running']) {
                break;
            }

            usleep(20000);
        }

        $status = proc_get_status($process);

        if ($status['running']) {
            proc_terminate($process, 9);
            usleep(100000);
        }
    }

    @proc_close($process);

    $seconds = microtime(true) - $started;
    $output = frictionReadTail((string) $outFile, (string) $errFile);

    @unlink($outFile);
    @unlink($errFile);

    if ($timedOut) {
        return [
            'name' => $check['name'],
            'tier' => $check['tier'],
            'outcome' => 'TIMEOUT',
            'exit' => FRICTION_TIMEOUT_EXIT,
            'why' => (string) $check['why'],
            'seconds' => $seconds,
            'detail' => 'killed at the '.$check['timeout'].'s deadline — it verified nothing, which is not a pass',
            'output' => $output,
        ];
    }

    // 127 is the shell's "command not found". A gate whose command does not
    // exist has NOT FAILED a check — it never ran one, and reporting it as an
    // ordinary red would rank a missing gate BELOW a gate that ran and found
    // something. It is the same class as a spawn failure and it is ranked there.
    if ($lastExit === 127) {
        return [
            'name' => $check['name'],
            'tier' => $check['tier'],
            'outcome' => 'BROKEN',
            'exit' => 127,
            'why' => (string) $check['why'],
            'seconds' => $seconds,
            'detail' => 'exit 127 — the command does not exist, so no check was run at all',
            'output' => $output,
        ];
    }

    $incomplete = array_map('intval', (array) $check['incomplete']);
    $outcome = $lastExit === 0 ? 'PASS' : (in_array($lastExit, $incomplete, true) ? 'NO-VERDICT' : 'FAIL');

    return [
        'name' => $check['name'],
        'tier' => $check['tier'],
        'outcome' => $outcome,
        'exit' => $lastExit,
        'why' => (string) $check['why'],
        'seconds' => $seconds,
        'detail' => '',
        'output' => $output,
    ];
}

/**
 * The child's environment: this PHP binary's, plus vendor/bin on PATH so a
 * catalogue command may call a composer binary by name.
 *
 * @return array<string, string>
 */
function frictionChildEnvironment(string $root): array
{
    $environment = [];

    foreach ($_SERVER as $key => $value) {
        if (is_string($key) && is_string($value)) {
            $environment[$key] = $value;
        }
    }

    $path = ($environment['PATH'] ?? '').':'.$root.'/vendor/bin';
    $environment['PATH'] = $path;
    $environment['TELEFRAME_FRICTION'] = '1';

    return $environment;
}

/**
 * The tail of a check's stdout and stderr, trimmed to a quotable size.
 *
 * A failure nobody can read is a failure that gets ignored, so the tail is kept
 * and the head of it is dropped: gates that fail loudly print their verdict last.
 */
function frictionReadTail(string $outFile, string $errFile): string
{
    $text = '';

    foreach ([$outFile, $errFile] as $file) {
        if (! is_file($file)) {
            continue;
        }

        $chunk = (string) file_get_contents($file);

        if ($chunk !== '') {
            $text .= ($text === '' ? '' : "\n").$chunk;
        }
    }

    $text = trim($text);

    if (strlen($text) <= FRICTION_TAIL_CHARS) {
        return $text;
    }

    return '…'.substr($text, -FRICTION_TAIL_CHARS);
}

// ===========================================================================
// REPORTING
// ===========================================================================

/**
 * The compact table, then the ranked failures, then the verdict line.
 *
 * @param  list<array<string, mixed>>  $results
 */
function frictionPrintTable(array $results, string $tier, bool $withSkipped = true): void
{
    printf('  %-28s %-6s %-6s %8s%s', 'CHECK', 'TIER', 'EXIT', 'SECONDS', PHP_EOL);
    printf('  %s%s', str_repeat('-', 52), PHP_EOL);

    foreach ($results as $result) {
        printf(
            '  %-28s %-6s %-6s %8.2f%s',
            $result['name'],
            $result['tier'],
            $result['outcome'] === 'PASS' ? '0' : (string) $result['exit'],
            $result['seconds'],
            PHP_EOL,
        );
    }

    $skipped = $withSkipped ? frictionSkipped($tier) : [];

    if ($skipped !== []) {
        printf('  %s%s', str_repeat('-', 52), PHP_EOL);
        printf('  %d %s-tier check(s) not run: %s%s', count($skipped), $tier, implode(', ', $skipped), PHP_EOL);
    }
}

/**
 * Names of the `full` checks a `fast` run left behind.
 *
 * @return list<string>
 */
function frictionSkipped(string $tier): array
{
    if ($tier !== 'fast') {
        return [];
    }

    $skipped = [];

    foreach (frictionCatalogue() as $entry) {
        if ($entry['tier'] === 'full') {
            $skipped[] = $entry['name'];
        }
    }

    return $skipped;
}

/** Severity order, worst first. Lower rank is worse. */
function frictionSeverity(string $outcome): int
{
    return match ($outcome) {
        'BROKEN' => 0,
        'UNCOVERED' => 1,
        'ORPHANED' => 2,
        'TIMEOUT' => 3,
        'NO-VERDICT' => 4,
        'FAIL' => 5,
        default => 9,
    };
}

/**
 * The ranked list of everything that is not a pass, worst first, with the tail
 * of each failure's own output underneath it.
 *
 * @param  list<array<string, mixed>>  $results
 * @return int the number of failures
 */
function frictionPrintFailures(array $results): int
{
    $bad = array_values(array_filter(
        $results,
        static fn (array $result): bool => $result['outcome'] !== 'PASS',
    ));

    if ($bad === []) {
        return 0;
    }

    usort($bad, static function (array $a, array $b): int {
        $bySeverity = frictionSeverity((string) $a['outcome']) <=> frictionSeverity((string) $b['outcome']);

        return $bySeverity !== 0 ? $bySeverity : strcmp((string) $a['name'], (string) $b['name']);
    });

    printf('%s  FAILURES, ranked worst first (%d)%s', PHP_EOL, count($bad), PHP_EOL);

    $rank = 0;

    foreach ($bad as $failure) {
        $rank++;
        printf(
            '%s  %d. %-24s %-11s %s',
            PHP_EOL,
            $rank,
            $failure['name'],
            $failure['outcome'],
            $failure['detail'] !== '' ? $failure['detail'].PHP_EOL : frictionWhy((string) $failure['name'], $failure).PHP_EOL,
        );

        $output = (string) $failure['output'];

        if ($output !== '') {
            foreach (explode("\n", $output) as $line) {
                printf('        %s%s', $line, PHP_EOL);
            }
        }
    }

    return count($bad);
}

/**
 * The friction class a failure line quotes: the row's own `why` when it has one
 * (a self-test check is not in the catalogue and must still say why it matters),
 * otherwise the catalogue entry for that name.
 *
 * @param  array<string, mixed>  $row
 */
function frictionWhy(string $name, array $row = []): string
{
    $why = (string) ($row['why'] ?? '');

    if ($why !== '') {
        return $why;
    }

    foreach (frictionCatalogue() as $entry) {
        if ($entry['name'] === $name) {
            return (string) $entry['why'];
        }
    }

    return '(no claim)';
}

// ===========================================================================
// --list
// ===========================================================================

function frictionPrintList(array $discovered, array $catalogue, string $root): void
{
    $uncovered = frictionUncovered($discovered, $catalogue);
    $orphaned = frictionOrphanedClaims($discovered, $catalogue);
    $dead = frictionDeadGates($root, $discovered, $catalogue);
    $deadFailing = 0;
    $deadExempt = [];
    foreach ($dead as $gate) {
        if ($gate['exempt']) {
            $deadExempt[] = $gate;

            continue;
        }
        $deadFailing++;
    }

    $sources = frictionCommandSources($root);
    $sourceNames = [];
    foreach ($sources as $source) {
        $parts = explode(':', (string) $source['name']);
        $file = array_shift($parts);
        $sourceNames[$file] = true;
    }
    ksort($sourceNames);

    printf('FRICTION GATE — inventory%s', PHP_EOL);
    printf('  sources read : %d command surface(s) from %s%s', count($sources), implode(', ', array_keys($sourceNames)), PHP_EOL);
    printf('  gates named  : %d distinct identit%s%s', count($discovered), count($discovered) === 1 ? 'y' : 'ies', PHP_EOL);
    printf('  claims       : %d catalogue entr%s%s', count($catalogue), count($catalogue) === 1 ? 'y' : 'ies', PHP_EOL);
    printf('  uncovered    : %d%s', count($uncovered), $uncovered === [] ? '' : '  <-- FAILS THE RUN');
    printf('  orphaned     : %d%s', count($orphaned), $orphaned === [] ? '' : '  (run here, by no CI job — reported, not a failure)');
    printf('  declared-dead: %d%s%s', $deadFailing, $deadExempt === [] ? '' : sprintf(' (%d exempt, each with a stated reason)', count($deadExempt)), $deadFailing === 0 ? '' : '  <-- FAILS THE RUN');
    printf('%s', PHP_EOL);

    $byTier = ['fast' => [], 'full' => [], 'ci' => []];

    foreach ($catalogue as $entry) {
        $byTier[$entry['tier']][] = $entry;
    }

    foreach (['fast', 'full', 'ci'] as $tier) {
        printf('── %s tier (%d)%s%s', strtoupper($tier), count($byTier[$tier]), PHP_EOL, PHP_EOL);

        foreach ($byTier[$tier] as $entry) {
            printf('  %-28s %ss  why: %s%s', $entry['name'], $entry['timeout'], $entry['why'], PHP_EOL);
            printf('  %-28s %s%s', '', 'command: '.($entry['command'] === '' ? '(not run here)' : $entry['command']), PHP_EOL);
            printf('  %-28s %s%s', '', 'claims : '.implode(', ', (array) $entry['covers']), PHP_EOL);
        }

        printf('%s', PHP_EOL);
    }

    if ($uncovered !== []) {
        printf('UNCOVERED — named by this repository, claimed by nobody:%s', PHP_EOL);

        foreach ($uncovered as $gate) {
            printf('  %s%s', $gate['id'], PHP_EOL);
            printf('      seen in : %s%s', implode(', ', $gate['samples']), PHP_EOL);
            printf('      sources : %s%s', implode(', ', array_slice($gate['sources'], 0, 4)), PHP_EOL);
        }

        printf('  Add a catalogue entry for each: which tier, which command here, why it matters.%s', PHP_EOL);
    }

    if ($orphaned !== []) {
        printf('ORPHANED — this runner runs them; no CI job or composer script does:%s', PHP_EOL);

        foreach ($orphaned as $claim) {
            printf('  %-28s [%s] claims %s%s', $claim['name'], $claim['tier'], $claim['id'], PHP_EOL);
        }

        printf('  Not a failure: the gate runs here. But it runs NOWHERE else, so a red one is%s', PHP_EOL);
        printf('  found by hand or not at all.%s', PHP_EOL);
    }

    if ($dead !== []) {
        printf('DECLARED-BUT-DEAD — named by composer.json, claimed, run by NOTHING:%s', PHP_EOL);

        foreach ($dead as $gate) {
            if ($gate['exempt']) {
                printf('  %s  [EXEMPT — declared local_only]%s', $gate['id'], PHP_EOL);
                printf('      %s%s', (string) $gate['why'], PHP_EOL);

                continue;
            }
            printf('  %s%s', $gate['id'], PHP_EOL);
            printf('      named only by: %s%s', implode(', ', $gate['sources']), PHP_EOL);
            printf('      seen in      : %s%s', implode(', ', $gate['samples']), PHP_EOL);
        }

        if ($deadFailing > 0) {
            printf('  A composer script is a COMMAND SURFACE, not a JOB. Wire the script into a%s', PHP_EOL);
            printf('  workflow, fold it into composer verify, or declare local_only WITH a reason%s', PHP_EOL);
            printf('  on its catalogue entry. This run FAILED because %d of these run nowhere.%s', $deadFailing, PHP_EOL);
        }
    }
}

// ===========================================================================
// --selftest — the runner's own reporting, proved against broken inputs
// ===========================================================================

/**
 * Prove the runner can report a failure, a timeout and a broken definition.
 *
 * Two halves, both real:
 *
 *   A. SYNTHETIC CHECKS — four gates written to a temp dir: one that passes, one
 *      that fails, one that hangs forever against a 1s deadline, and one whose
 *      command cannot be spawned. These exercise the runner's own reporting path,
 *      including a real SIGTERM/SIGKILL against a real hung process.
 *
 *   B. A REAL GATE, REALLY BROKEN — bin/check-public-surface.php over a planted
 *      tree holding a path from the private strip set. Not a mock: the shipped
 *      gate, the shipped strip set, a tree that really does contain
 *      `.brain/SESSION_STATE.md`, and the exit code the gate really returns.
 *
 * A runner nobody watched go red is not evidence, so this asserts the verdicts
 * and exits non-zero when the runner mis-reports any of them.
 */
function frictionSelftest(string $root): int
{
    $workspace = sys_get_temp_dir().'/teleframe-friction-selftest-'.getmypid();

    @mkdir($workspace, 0o777, true);

    $expectations = [];

    // ---- Half A: the runner's own reporting.
    $synthetic = frictionSyntheticChecks($workspace);

    $results = [];
    foreach ($synthetic as $check) {
        $results[] = frictionRunCheck($check, $workspace);
    }

    printf('── A. synthetic checks (real processes, real deadlines)%s%s', PHP_EOL, PHP_EOL);
    frictionPrintTable($results, 'fast', false);

    $byName = [];
    foreach ($results as $result) {
        $byName[$result['name']] = $result;
    }

    $expectations['synthetic pass reported PASS'] = ($byName['synthetic-pass']['outcome'] ?? '') === 'PASS';
    $expectations['synthetic fail reported FAIL, exit 1'] = ($byName['synthetic-fail']['outcome'] ?? '') === 'FAIL'
        && ($byName['synthetic-fail']['exit'] ?? null) === 1;
    $expectations['synthetic hang reported TIMEOUT, exit 124, not PASS'] = ($byName['synthetic-hang']['outcome'] ?? '') === 'TIMEOUT'
        && ($byName['synthetic-hang']['exit'] ?? null) === FRICTION_TIMEOUT_EXIT;
    $expectations['synthetic absent binary reported BROKEN'] = ($byName['synthetic-broken']['outcome'] ?? '') === 'BROKEN';

    // The documented severity ladder, asserted as a whole rather than one
    // comparison at a time: BROKEN (never ran) > TIMEOUT (started, never
    // finished) > FAIL (ran, went red). A gate that never ran is worse than one
    // that hung, because a hang at least proved the gate exists.
    $ranked = array_map(static fn (array $r): string => (string) $r['name'], frictionRank($results));
    $expectations['ranking is BROKEN > TIMEOUT > FAIL'] = $ranked === [
        'synthetic-broken',
        'synthetic-hang',
        'synthetic-fail',
    ];

    $counted = frictionPrintFailures($results);
    $expectations['failures counted = 3 (fail, timeout, broken)'] = $counted === 3;

    // ---- Half B: a real gate, really broken.
    printf('%s── B. a REAL gate on a deliberately broken input%s%s', PHP_EOL, PHP_EOL, PHP_EOL);
    printf('  planted tree: %s%s', $workspace.'/public', PHP_EOL);
    printf('  planted file: %s   (a path on the D-035 private strip set)%s', '.brain/SESSION_STATE.md', PHP_EOL);
    printf('  gate        : php bin/check-public-surface.php --tree=<planted>%s%s', PHP_EOL, PHP_EOL);

    $planted = $workspace.'/public';
    @mkdir($planted.'/.brain', 0o777, true);
    file_put_contents($planted.'/.brain/SESSION_STATE.md', "# planted by friction-gate --selftest\n");

    $realCheck = [
        'name' => 'check-public-surface',
        'tier' => 'fast',
        'why' => 'a path in the exported public tree is outside the allowlist or on the private strip set',
        'command' => escapeshellarg(PHP_BINARY).' bin/check-public-surface.php --tree='.escapeshellarg($planted),
        'timeout' => 60,
        'covers' => ['bin/check-public-surface.php --tree'],
        'incomplete' => [],
    ];

    $realResult = frictionRunCheck($realCheck, $root);
    frictionPrintTable([$realResult], 'fast', false);
    frictionPrintFailures([$realResult]);

    $expectations['real gate reported FAIL on the planted strip-set path'] = $realResult['outcome'] === 'FAIL';
    $expectations['real gate exited 1 (violations), not 0 and not 2'] = $realResult['exit'] === 1;
    $expectations['real gate output names the planted path'] = str_contains(
        (string) $realResult['output'],
        '.brain/SESSION_STATE.md',
    );

    // ---- Verdicts.
    printf('%s── self-test expectations%s%s', PHP_EOL, PHP_EOL, PHP_EOL);

    $failed = 0;

    foreach ($expectations as $claim => $held) {
        printf('  [%s] %s%s', $held ? 'PASS' : 'FAIL', $claim, PHP_EOL);
        $failed += $held ? 0 : 1;
    }

    printf('%s', PHP_EOL);
    printf(
        '  self-test: %d expectation(s), %d failed — the runner can go red.%s',
        count($expectations),
        $failed,
        PHP_EOL,
    );

    // The temp tree is this runner's own; it goes when the runner does.
    frictionRemoveTree($workspace);

    return $failed === 0 ? 0 : 1;
}

/**
 * Four synthetic gates, each a real executable in a temp dir.
 *
 * @return list<array<string, mixed>>
 */
function frictionSyntheticChecks(string $workspace): array
{
    $pass = $workspace.'/pass.sh';
    $fail = $workspace.'/fail.sh';
    $hang = $workspace.'/hang.sh';

    file_put_contents($pass, "#!/usr/bin/env bash\necho \"synthetic gate: clean\"\nexit 0\n");
    file_put_contents($fail, "#!/usr/bin/env bash\necho \"synthetic gate: 3 violations\" >&2\nexit 1\n");
    file_put_contents($hang, "#!/usr/bin/env bash\nsleep 120\n");

    foreach ([$pass, $fail, $hang] as $script) {
        @chmod($script, 0o755);
    }

    return [
        [
            'name' => 'synthetic-pass',
            'tier' => 'fast',
            'why' => 'a clean gate stays clean',
            'command' => 'bash '.escapeshellarg($pass),
            'timeout' => 10,
            'covers' => [],
            'incomplete' => [],
        ],
        [
            'name' => 'synthetic-fail',
            'tier' => 'fast',
            'why' => 'a red gate is reported red, with its exit code and its own output',
            'command' => 'bash '.escapeshellarg($fail),
            'timeout' => 10,
            'covers' => [],
            'incomplete' => [],
        ],
        [
            'name' => 'synthetic-hang',
            'tier' => 'fast',
            'why' => 'a gate that never returns is a TIMEOUT, never a pass',
            'command' => 'bash '.escapeshellarg($hang),
            'timeout' => 1,
            'covers' => [],
            'incomplete' => [],
        ],
        [
            'name' => 'synthetic-broken',
            'tier' => 'fast',
            'why' => 'a check that cannot be spawned is BROKEN, and ranks above every other verdict',
            'command' => escapeshellarg($workspace.'/this-binary-does-not-exist'),
            'timeout' => 10,
            'covers' => [],
            'incomplete' => [],
        ],
    ];
}

/**
 * Results ordered worst-first, the same order `frictionPrintFailures()` prints.
 *
 * @param  list<array<string, mixed>>  $results
 * @return list<array<string, mixed>>
 */
function frictionRank(array $results): array
{
    $bad = array_values(array_filter(
        $results,
        static fn (array $result): bool => $result['outcome'] !== 'PASS',
    ));

    usort($bad, static function (array $a, array $b): int {
        $bySeverity = frictionSeverity((string) $a['outcome']) <=> frictionSeverity((string) $b['outcome']);

        return $bySeverity !== 0 ? $bySeverity : strcmp((string) $a['name'], (string) $b['name']);
    });

    return $bad;
}

/**
 * Remove a tree this runner created, without ever leaving the temp directory.
 */
function frictionRemoveTree(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    foreach (scandir($directory) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $directory.'/'.$entry;

        is_dir($path) ? frictionRemoveTree($path) : @unlink($path);
    }

    @rmdir($directory);
}

// ===========================================================================
// MAIN
// ===========================================================================

$usage = <<<'TXT'
friction-gate.php — run every friction gate this repository already has

USAGE
  php bin/friction-gate.php                # the fast tier (seconds-scale checks only)
  php bin/friction-gate.php --tier=full    # plus the affordable slow checks
  php bin/friction-gate.php --list         # the discovered inventory, nothing run
  php bin/friction-gate.php --selftest     # prove the runner can report red

EXIT CODES
  0  every check that ran passed, and every gate the repo names is claimed
  1  a check failed, timed out, could not be run, or is unclaimed / stale-claimed
  2  usage error

NOTES
  The inventory is DISCOVERED from .github/workflows/*, .gitlab-ci.yml and
  composer.json (via bin/ci-definitions.php); no list of gates is written into
  this file. A gate named by this repository with no claim here FAILS the run,
  which is what stops a check from quietly ceasing to run.
TXT;

$tier = 'fast';
$mode = 'run';

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--list') {
        $mode = 'list';

        continue;
    }

    if ($argument === '--selftest') {
        $mode = 'selftest';

        continue;
    }

    if ($argument === '--help' || $argument === '-h') {
        echo $usage, PHP_EOL;

        exit(0);
    }

    if (str_starts_with($argument, '--tier=')) {
        $tier = substr($argument, 7);

        if (! in_array($tier, ['fast', 'full'], true)) {
            fwrite(STDERR, "friction-gate: unknown tier '{$tier}' — fast or full.\n");
            exit(2);
        }

        continue;
    }

    fwrite(STDERR, "friction-gate: unknown argument '{$argument}'.\n\n".$usage.PHP_EOL);
    exit(2);
}

if ($mode === 'selftest') {
    exit(frictionSelftest($root));
}

$discovered = frictionDiscoveredGates($root);
$catalogue = frictionCatalogue();

if ($discovered === []) {
    // The loud path. An empty inventory here means the reader broke, not that
    // the repository has no gates — and "no gates, all green" is the exact
    // output this file refuses to produce.
    fwrite(STDERR, <<<'TXT'
friction-gate: DISCOVERED ZERO GATES — this is a broken reader, not a clean repository.

  Read: .github/workflows/*.{yml,yaml}, .gitlab-ci.yml, composer.json `scripts`
  via bin/ci-definitions.php. Zero readable command surfaces means either the
  autoloader could not resolve Symfony's Yaml, or every definition failed to
  parse. Reporting "0 checks, all passed" here would be a green gate that
  proved nothing, which is the failure this file exists to end.

TXT);
    exit(1);
}

if ($mode === 'list') {
    frictionPrintList($discovered, $catalogue, $root);

    // Two things fail this mode: a gate nobody claims, and a gate this
    // repository claims that nothing runs. The second is the one that let a RED
    // gate sit on the clean committed tree for four days behind an `uncovered: 0`
    // (goal 46.84), so it is checked here and not only printed.
    $deadGates = frictionDeadGates($root, $discovered, $catalogue);
    $deadBlocking = array_values(array_filter($deadGates, static fn (array $g): bool => ! $g['exempt']));

    exit(frictionUncovered($discovered, $catalogue) === [] && $deadBlocking === [] ? 0 : 1);
}

// ---- Run.
$started = microtime(true);

printf('FRICTION GATE — %s tier — %d gate(s) named, %d claim(s)%s', $tier, count($discovered), count($catalogue), PHP_EOL);

$results = [];

foreach ($catalogue as $check) {
    if ($check['tier'] !== $tier) {
        continue;
    }

    $results[] = frictionRunCheck($check, $root);
}

frictionPrintTable($results, $tier);

$uncovered = frictionUncovered($discovered, $catalogue);
$orphaned = frictionOrphanedClaims($discovered, $catalogue);

foreach ($uncovered as $gate) {
    $results[] = [
        'name' => $gate['id'],
        'tier' => 'n/a',
        'outcome' => 'UNCOVERED',
        'exit' => 1,
        'seconds' => 0.0,
        'detail' => 'named by '.$gate['sources'][0].' and claimed by no catalogue entry',
        'output' => 'seen as: '.implode(' | ', $gate['samples']),
    ];
}

$failures = frictionPrintFailures($results);

$ciCount = 0;
foreach ($catalogue as $entry) {
    $ciCount += $entry['tier'] === 'ci' ? 1 : 0;
}

if ($orphaned !== []) {
    printf(
        '%s  ORPHANED — run here, by no CI job or composer script (reported, not a failure):%s',
        PHP_EOL,
        PHP_EOL,
    );

    foreach ($orphaned as $claim) {
        printf('    %-28s [%s tier]%s', $claim['name'], $claim['tier'], PHP_EOL);
    }
}

printf('%s', PHP_EOL);
printf(
    '  %d ran, %d failed, %d ci-tier check(s) in the inventory and not run here.%s',
    count($results) - count($uncovered),
    $failures,
    $ciCount,
    PHP_EOL,
);
printf('  wall clock: %.1fs (tier: %s)%s', microtime(true) - $started, $tier, PHP_EOL);

exit($failures === 0 ? 0 : 1);
