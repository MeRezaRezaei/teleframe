<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Standalone;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The host-install gate's OWN invariants, checked without a network and
 * without building a Laravel host.
 *
 * bin/prove-host-install.php is the real proof: it builds a throwaway host and
 * fails when a provider is not auto-discovered, a publish tag lands nothing,
 * doctor hangs, a facade fatals unconfigured, or a command is missing. It needs
 * the network, so it cannot run on every commit. These are the checks that make
 * the gate itself trustworthy — chiefly that the gate's hardcoded expectations
 * still MATCH composer.json.
 *
 * The failure this exists to prevent: someone adds a seventh provider (or drops
 * one from the composer.json aliases) and the gate keeps asserting the old
 * six, passing forever over a package whose install has changed. A gate whose
 * expectations can drift is worse than no gate, because it is trusted.
 */
final class HostInstallGateTest extends TestCase
{
    private const SCRIPT = __DIR__.'/../../bin/prove-host-install.php';

    /** @return array<string, mixed> */
    private function composer(): array
    {
        $decoded = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true);
        self::assertIsArray($decoded, 'composer.json must be readable JSON');

        return $decoded;
    }

    /** The PHP const array in the script, read as text (never executed). */
    private function scriptConstArray(string $name): array
    {
        $source = (string) file_get_contents(self::SCRIPT);
        self::assertNotSame('', $source, 'bin/prove-host-install.php must exist');
        $matched = preg_match(
            '/const '.preg_quote($name, '/').' = \[(.*?)\n\];/s',
            $source,
            $matches,
        );
        self::assertSame(1, $matched, 'bin/prove-host-install.php must declare a '.$name.' const array');
        preg_match_all("/'([^']+)'/", $matches[1], $found);

        return $found[1];
    }

    public function test_the_gate_script_exists_and_is_valid_php(): void
    {
        self::assertFileExists(self::SCRIPT);
        $output = [];
        $code = 0;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg(self::SCRIPT).' 2>&1', $output, $code);
        self::assertSame(0, $code, "bin/prove-host-install.php must lint:\n".implode("\n", $output));
    }

    /**
     * THE check. A provider removed from composer.json must fail here, without
     * a network, in a second — the fast half of "the gate goes red".
     */
    public function test_every_gate_provider_is_still_declared_in_composer_json(): void
    {
        $declared = $this->composer()['extra']['laravel']['providers'] ?? [];
        self::assertIsArray($declared);

        $gateProviders = $this->scriptConstArray('EXPECTED_PROVIDERS');
        self::assertNotSame([], $gateProviders, 'the gate must assert a provider set');

        $missing = array_values(array_diff($gateProviders, $declared));
        self::assertSame(
            [],
            $missing,
            'provider(s) dropped from composer.json extra.laravel.providers but still '
            ."asserted by the host-install gate: \n  ".implode("\n  ", $missing)
        );
    }

    /** And the reverse drift: a provider added to composer.json is gate drift. */
    public function test_no_provider_is_declared_that_the_gate_has_never_seen(): void
    {
        $declared = $this->composer()['extra']['laravel']['providers'] ?? [];
        $gateProviders = $this->scriptConstArray('EXPECTED_PROVIDERS');
        $ungated = array_values(array_diff($declared, $gateProviders));

        self::assertSame(
            [],
            $ungated,
            'provider(s) in composer.json that bin/prove-host-install.php does not '
            ."cover — a new provider's auto-discovery is UNPROVEN until the gate "
            ."asserts it: \n  ".implode("\n  ", $ungated)
        );
    }

    public function test_every_declared_provider_class_exists_in_src(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ($this->composer()['extra']['laravel']['providers'] ?? [] as $class) {
            $relative = 'src/'.str_replace('\\', '/', (string) preg_replace(
                '/^MeRezaRezaei\\\\Teleframe\\\\/',
                '',
                $class,
            )).'.php';
            self::assertFileExists($root.'/'.$relative, $class.' is declared but its file is missing');
        }
    }

    /** The six facades the gate probes must be declared as host aliases. */
    public function test_every_gated_facade_is_declared_as_an_alias(): void
    {
        $aliases = $this->composer()['extra']['laravel']['aliases'] ?? [];
        foreach (['TF', 'Teleframe', 'TStore', 'Notify', 'StoreBackup', 'TelegramApp', 'Passport'] as $alias) {
            self::assertArrayHasKey(
                $alias,
                $aliases,
                'the '.$alias.' facade must be auto-discovered as a host alias',
            );
        }
    }

    /** Every command the gate requires must exist in a real command class. */
    public function test_every_gated_command_exists_in_src(): void
    {
        $root = dirname(__DIR__, 2);
        $sources = '';
        // Not every command lives under src/Laravel/Console: teleframe:notify
        // is declared by MeRezaRezaei\Teleframe\Notify\NotifyCommand, in the
        // core-ish tree beside the queue drivers it drives. Globbing only the
        // Laravel directory reported that real, registered command as missing,
        // which is the gate lying rather than the command being absent.
        foreach (glob($root.'/src/Laravel/Console/*.php') ?: [] as $file) {
            $sources .= (string) file_get_contents($file);
        }
        foreach (glob($root.'/src/Teleframe/Notify/NotifyCommand.php') ?: [] as $file) {
            $sources .= (string) file_get_contents($file);
        }
        self::assertStringContainsString('$signature', $sources, 'no command classes found — the glob is stale');

        // Most signatures span several lines, so the name ends at the newline,
        // not at a closing quote.
        $signatures = [];
        preg_match_all("/\\\$signature = '([a-z:-]+)/", $sources, $found);
        $signatures = $found[1];
        self::assertNotSame([], $signatures, 'no command signatures parsed — the pattern is stale');

        $missing = array_values(array_diff($this->scriptConstArray('EXPECTED_COMMANDS'), $signatures));
        self::assertSame(
            [],
            $missing,
            "command(s) the host-install gate requires but no Console class declares: \n  "
            .implode("\n  ", $missing),
        );
    }

    /**
     * Every publish tag the gate asserts must be registered by a provider. A
     * dropped publishes() call is the regression the manual install run found
     * and nothing else pinned.
     */
    public function test_every_gated_publish_tag_is_registered_by_a_provider(): void
    {
        $root = dirname(__DIR__, 2);
        // RECURSIVE: TeleframeServiceProvider — the one that owns the
        // teleframe-config tag — lives in src/Laravel/Providers/, one level
        // down. A flat glob misses it and this test silently proves nothing.
        $sources = '';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.'/src/Laravel', FilesystemIterator::SKIP_DOTS),
        );
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile() && str_ends_with($file->getFilename(), 'ServiceProvider.php')) {
                $sources .= (string) file_get_contents($file->getPathname());
            }
        }
        self::assertStringContainsString('publishes', $sources, 'no provider sources found — the walk is stale');
        self::assertStringContainsString('TeleframeServiceProvider', $sources, 'the main provider was not read');

        // The gate's EXPECTED_PUBLISHES is a tag => destination map; read the
        // tag keys out of the script text.
        $script = (string) file_get_contents(self::SCRIPT);
        $matched = preg_match('/const EXPECTED_PUBLISHES = \[(.*?)\n\];/s', $script, $matches);
        self::assertSame(1, $matched, 'bin/prove-host-install.php must declare EXPECTED_PUBLISHES');
        preg_match_all("/'([a-z0-9-]+)' =>/", $matches[1], $tags);
        self::assertNotSame([], $tags[1], 'EXPECTED_PUBLISHES must be a tag => path map');

        $missing = [];
        foreach ($tags[1] as $tag) {
            if (! str_contains($sources, "'".$tag."'")) {
                $missing[] = $tag;
            }
        }
        self::assertSame(
            [],
            $missing,
            "publish tag(s) the gate asserts but no provider registers: \n  ".implode("\n  ", $missing),
        );
    }

    /**
     * THE CI-REACHABILITY GATE. bin/prove-host-install.php must be invoked by a
     * job that can actually run, on some CI surface that exists — otherwise it
     * IS a script nobody runs, which is the whole failure this protects.
     *
     * WHY THIS WAS REWRITTEN RATHER THAN DELETED. The old assertion was: the
     * string 'bin/prove-host-install.php' appears in .gitlab-ci.yml. That
     * PREMISE IS FALSE, and the reason is a port, not a regression in this file:
     *
     *   git log --oneline -S prove-host-install -- .gitlab-ci.yml
     *     ddbdef18  ci: move CI/CD to GitHub Actions; GitLab becomes the private/public split
     *     1d424b92  feat(gate): "installs and boots in a fresh host" is a gate, not a one-off run
     *
     * ddbdef18 is where 1d424b92's job was removed. `.github/workflows/
     * fidelity-audit.yml` is NOT the cause: it has never mentioned the gate at
     * all. Every tracked .yml that mentions it is .github/workflows/
     * regen-gates.yml, and there only inside a comment block that states the
     * job is DELIBERATELY NOT PORTED (regen-gates.yml:659-727).
     *
     * A disproven premise is a reason to REPLACE the assertion, never to
     * delete it, and never to weaken it. The property under protection — "a CI
     * surface that can actually run invokes this gate" — is still real. This
     * replacement is STRICTER than the original on four axes:
     *   1. DISCOVERS every CI surface instead of hardcoding one filename, so
     *      moving a job between .gitlab-ci.yml and .github/workflows/*.yml does
     *      not silently disarms the gate (the original's exact failure mode).
     *   2. REQUIRES the invocation to sit inside a real job body. The original
     *      counted a mention inside a COMMENT block as proof the gate ran —
     *      which is precisely why it could be satisfied by regen-gates.yml's
     *      "not ported" notice.
     *   3. CHECKS REACHABILITY: a job under `when: never`, a workflow with no
     *      `on:` trigger, a job gated on a `vars.`/`secrets.`/`env.` value
     *      that may never be set, or a job whose `if:` is a CONSTANT-FALSE
     *      expression (`if: false`) is REJECTED.
     *   4. FAILS LOUDLY with every surface and job it scanned, so the message
     *      names the hole instead of printing a boolean.
     *
     * KNOWN LIMIT, recorded rather than hidden: reachability is decided by
     * reading job blocks as text, so a gate invoked indirectly (a reusable
     * workflow via `uses:`, or a shell alias) is not seen. That is accepted
     * deliberately — an indirection a static check cannot follow is not a gate a
     * reader can verify either, and this test is the one that must stay honest.
     */
    public function test_ci_runs_the_host_install_gate(): void
    {
        $scan = $this->scanForGateInvocations($this->ciSurfaces());

        self::assertNotSame(
            [],
            $scan['reachable'],
            $this->reachabilityFailureMessage($scan),
        );
    }

    /**
     * THE SCAN — shared verbatim by the real assertion above and by the
     * table-driven test below, so the test exercises the shipped decision and
     * not a paraphrase of it. A copy of these rules inside a test would be a
     * second implementation that can drift from the first, which is the exact
     * disease this whole file exists to catch.
     *
     * The trigger check runs BEFORE the jobs are read, not after: a workflow
     * nothing can trigger runs none of its jobs, so it must not be able to
     * contribute a single "reachable" invocation. Filtering afterwards — after
     * the invocation had already been counted — would leave the claim in the
     * failure message untrue.
     *
     * @param  array<string, string>  $surfaces  relative path => raw contents
     * @return array{reachable: list<string>, triggerless: list<string>}
     */
    private function scanForGateInvocations(array $surfaces): array
    {
        $invocation = 'bin/prove-host-install.php';
        $reachable = [];
        $triggerless = [];

        foreach ($surfaces as $name => $surface) {
            $lines = $this->stripComments($surface);
            $isGitLab = str_ends_with($name, '.gitlab-ci.yml') || str_starts_with($name, '.gitlab');

            if (! $isGitLab && ! $this->hasUsableTrigger($lines)) {
                $triggerless[] = $name;

                continue;
            }

            $jobs = $isGitLab ? $this->jobBlocks($lines, $this->isGitLabShape($lines)) : [];
            if ($jobs === []) {
                // Actions shape: job keys are indented two spaces under `jobs:`.
                $jobs = $this->jobBlocks($lines, false);
            }

            foreach ($jobs as $job => $body) {
                if (! str_contains($body, $invocation)) {
                    continue;
                }
                $reachable[] = $name.' :: '.$job;
            }
        }

        return ['reachable' => $reachable, 'triggerless' => $triggerless];
    }

    /**
     * @param  array{reachable: list<string>, triggerless: list<string>}  $scan
     */
    private function reachabilityFailureMessage(array $scan): string
    {
        $message = 'the host-install gate (bin/prove-host-install.php) is invoked by NO reachable job on '
            ."ANY CI surface — it is a script nobody runs.\n"
            .'  surfaces scanned : '.implode(', ', array_keys($this->ciSurfaces()))."\n"
            .'  a claim in a COMMENT is not an invocation; a job under `when: never`, a workflow with '
            .'no `on:` trigger, an `if:` depending on an unset vars./secrets./env. value, and an `if:` '
            ."that is constant false (`if: false`) do not count as runnable.\n"
            .'  wire a job that calls `php bin/prove-host-install.php` into a CI surface that can '
            .'reach it.';

        // Only reachable when the assertion is already failing, so this ADDS the
        // name of the hole without turning an unrelated triggerless workflow
        // into a failure of its own.
        if ($scan['triggerless'] !== []) {
            $message .= "\n  skipped as TRIGGERLESS (no usable `on:` trigger, so no job in them can "
                .'ever run): '.implode(', ', $scan['triggerless']);
        }

        return $message;
    }

    /**
     * THE REACHABILITY RULES, PINNED — the ninth test, and the one that makes
     * the other two claims in this class's docblock true instead of aspirational.
     *
     * Before it existed, the workflow-level check ("a workflow with no `on:`
     * trigger … do not count as runnable") was asserted in the docblock and in
     * the failure message and implemented NOWHERE: deleting the `on:` block from
     * .github/workflows/host-install.yml in a scratch copy left the gate
     * ACCEPTED (exit 0). The constant-false `if:` rules were implemented and
     * proved only by scratch controls, so nothing in the suite would have failed
     * had a later edit deleted them.
     *
     * ISOLATION — this test never reads and NEVER modifies a real workflow
     * file. Each case is written to a throwaway directory that contains nothing
     * but one synthetic workflow, and the SAME scan the real assertion uses
     * (scanForGateInvocations over ciSurfaces($root)) reads it. A test that
     * mutated .github/workflows/*.yml to make itself pass would be a test that
     * edits the thing it is supposed to protect.
     *
     * It asserts BOTH directions. A rule checked only in its rejecting direction
     * is half a gate: if "is this reachable" ever returns false for everything,
     * every rejection above still looks green.
     */
    public function test_an_unreachable_job_shape_can_never_satisfy_the_reachability_gate(): void
    {
        $cases = [
            // [name, workflow `on:` block, extra job-level keys, expected reachable]
            ['triggerless: no `on:` key at all', '', '', false],
            ['triggerless: `on:` with nothing beneath it', "on:\n", '', false],
            ['triggerless: `on: {}`', "on: {}\n", '', false],
            ['triggerless: `on: []`', "on: []\n", '', false],
            ['triggerless: `on: ~`', "on: ~\n", '', false],
            ['triggerless: `on: \'\'`', "on: ''\n", '', false],

            ['reachable: `on: push`', "on: push\n", '', true],
            ['reachable: `on: [push, pull_request]`', "on: [push, pull_request]\n", '', true],
            ['reachable: `on: {push: {branches: [main]}}`', "on: {push: {branches: [main]}}\n", '', true],
            ['reachable: block form with null-valued event keys', "on:\n  push:\n  pull_request:\n", '', true],
            ['reachable: quoted `"on":` key', "\"on\": push\n", '', true],

            ['unreachable: constant-false `if: false`', "on: push\n", "    if: false\n", false],
            ['unreachable: constant-false `if: ${{ false }}`', "on: push\n", "    if: \${{ false }}\n", false],
            ['unreachable: `when: never`', "on: push\n", "    when: never\n", false],
            ['unreachable: `if:` on an unset `vars.`', "on: push\n", "    if: vars.HOST_INSTALL == 'yes'\n", false],
            ['unreachable: `if:` on an unset `secrets.`', "on: push\n", "    if: secrets.TOKEN != ''\n", false],
            ['unreachable: `if:` on an unset `env.`', "on: push\n", "    if: env.GATE == '1'\n", false],

            ['reachable: real event condition', "on: push\n", "    if: github.event_name == 'push'\n", true],
            ['reachable: `always()`', "on: push\n", "    if: always()\n", true],
            ['reachable: `if: true`', "on: push\n", "    if: true\n", true],
        ];

        $failures = [];
        foreach ($cases as [$name, $trigger, $jobKeys, $expected]) {
            $actual = $this->gateIsReachableInScratch($this->scratchWorkflow($trigger, $jobKeys));
            if ($actual !== $expected) {
                $failures[] = sprintf(
                    '  %-52s expected reachable=%s, got %s',
                    $name,
                    var_export($expected, true),
                    var_export($actual, true),
                );
            }
        }

        self::assertSame(
            [],
            $failures,
            'reachability rule(s) that no longer hold — each case is a synthetic workflow scanned by the '
            ."shipped logic:\n".implode("\n", $failures),
        );

        // The same rule, asserted against the REAL tree this time: a workflow
        // committed here without a trigger is a job nobody will ever see run.
        $triggerless = [];
        foreach ($this->ciSurfaces() as $name => $surface) {
            if (str_starts_with($name, '.gitlab')) {
                continue; // GitLab pipelines need no `on:`
            }
            if (! $this->hasUsableTrigger($this->stripComments($surface))) {
                $triggerless[] = $name;
            }
        }
        self::assertSame(
            [],
            $triggerless,
            "workflow(s) committed without a usable `on:` trigger — nothing can ever run them: \n  "
            .implode("\n  ", $triggerless),
        );
    }

    /** One synthetic workflow: a trigger block, extra job-level keys, one step that runs the gate. */
    private function scratchWorkflow(string $trigger, string $jobKeys): string
    {
        return "name: scratch\n".$trigger
            ."permissions:\n  contents: read\n\njobs:\n  gate:\n".$jobKeys
            ."    runs-on: ubuntu-latest\n    steps:\n      - run: |\n"
            ."          php bin/prove-host-install.php\n";
    }

    /**
     * Does the SHIPPED reachability scan find the gate runnable in this
     * workflow? Written to a throwaway directory holding this one file, so no
     * real workflow is read, let alone modified.
     */
    private function gateIsReachableInScratch(string $workflow): bool
    {
        $root = sys_get_temp_dir().'/teleframe-host-install-gate-'.bin2hex(random_bytes(8));
        $directory = $root.'/.github/workflows';
        mkdir($directory, 0o777, true);
        $file = $directory.'/scratch.yml';
        file_put_contents($file, $workflow);

        try {
            return $this->scanForGateInvocations($this->ciSurfaces($root))['reachable'] !== [];
        } finally {
            @unlink($file);
            @rmdir($directory);
            @rmdir($root.'/.github');
            @rmdir($root);
        }
    }

    /**
     * Every CI surface in the repository, discovered — never a hardcoded list,
     * because the hardcoded list is what let the GitLab -> Actions port disarm
     * this gate without anybody noticing.
     *
     * $root defaults to the repository so the real assertion keeps reading the
     * real tree; the table-driven test below passes a throwaway directory, which
     * is what keeps that test from ever touching (or needing to modify) a real
     * workflow file.
     *
     * @return array<string, string> relative path => raw file contents
     */
    private function ciSurfaces(?string $root = null): array
    {
        $root ??= dirname(__DIR__, 2);
        $surfaces = [];
        foreach (['.gitlab-ci.yml', '.github/workflows/*.yml', '.github/workflows/*.yaml'] as $pattern) {
            foreach (glob($root.'/'.$pattern) ?: [] as $file) {
                $surfaces[str_replace($root.'/', '', $file)] = (string) file_get_contents($file);
            }
        }
        self::assertNotSame([], $surfaces, 'no CI surface found — the discovery globs are stale');

        return $surfaces;
    }

    /**
     * Can this workflow EVER be started? The workflow-level half of
     * reachability: a workflow with no usable `on:` trigger never dispatches a
     * single job, so every job inside it — however reachable it looks — is
     * dead. This is the check the docblock above has always CLAIMED and that was
     * proven, by a scratch control, not implemented anywhere.
     *
     * ONLY ZERO-INDENT keys are considered, and that restriction is what makes
     * this safe to do with a regex. A workflow's top-level keys sit at column 0
     * and nothing else does: a step's `if:` is indented, and block-scalar
     * content is ALWAYS indented further than the key that opened it, so a
     * script line can never present a false `on:` at column 0.
     *
     * GitLab is NOT asked this question — it has no `on:` and its pipelines run
     * without one — so the caller skips it there.
     *
     * WHAT COUNTS AS A USABLE TRIGGER — accepted, in every spelling Actions
     * accepts: an inline scalar (`on: push`), a flow sequence (`on: [push,
     * pull_request]`), a flow mapping (`on: {push: {branches: [main]}}`), and
     * the block forms, where ANY child line names a trigger — including the
     * `push:` / `pull_request:` shape whose values are null, because the KEY is
     * the event there, not the value.
     *
     * REJECTED, deliberately: no `on:` key at all; `on:` with nothing beneath
     * it; `on: {}` / `on: []`; and the explicit nulls `on: ~`, `on: null`,
     * `on: ''`, `on: ""`. All five trigger nothing, and the safe reading of a
     * trigger that fires on no event is that the workflow does not run. The
     * quoted key spellings `"on":` and `'on':` are ACCEPTED, since a quoted
     * `on` is the same key to Actions.
     *
     * NOT COVERED, and recorded rather than hidden: a trigger file that only
     * resolves through `${{ }}` templating, and any reachability decided by a
     * path/branch filter so narrow it never matches in practice. The second is
     * a judgement call, not a defect — a filter that is currently narrow is a
     * legitimate (if temporary) choice, and a static reader cannot tell a
     * deliberate filter from a forgotten one.
     *
     * @param  list<string>  $lines  comment-free lines
     */
    private function hasUsableTrigger(array $lines): bool
    {
        $count = count($lines);
        for ($index = 0; $index < $count; $index++) {
            if (preg_match('/^(["\']?)on\1\s*:\s*(.*)$/', rtrim($lines[$index], "\r"), $found) !== 1) {
                continue;
            }

            $inline = trim($found[2]);
            if ($inline !== '') {
                return $this->isNonEmptyTriggerValue($inline);
            }

            // Block form: the events are the CHILD keys beneath it.
            for ($child = $index + 1; $child < $count; $child++) {
                $candidate = rtrim($lines[$child], "\r");
                if (trim($candidate) === '') {
                    continue;
                }
                if (preg_match('/^\s/', $candidate) !== 1) {
                    break; // dedented: the block ended, and there were no events
                }

                return true;
            }

            return false; // `on:` with nothing under it fires on nothing
        }

        return false; // no `on:` key at all
    }

    /** Is an inline `on:` value a trigger, rather than `[]`, `{}`, `~` or `""`? */
    private function isNonEmptyTriggerValue(string $value): bool
    {
        $length = strlen($value);
        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];
            if (($first === '[' && $last === ']') || ($first === '{' && $last === '}')) {
                return trim(substr($value, 1, -1)) !== '';
            }
            if (($first === "'" && $last === "'") || ($first === '"' && $last === '"')) {
                $value = trim(substr($value, 1, -1));
            }
        }

        return ! in_array(strtolower($value), ['', '~', 'null'], true);
    }

    /**
     * Drop comment lines and any inline ` #` tail, so a sentence ABOUT the gate
     * is never mistaken for the gate RUNNING. Inline ` #` is stripped because
     * the invocation string contains no `#`, so this can only ever remove a
     * false positive — it can never manufacture one.
     *
     * @return list<string>
     */
    private function stripComments(string $yaml): array
    {
        $kept = [];
        foreach (explode("\n", $yaml) as $line) {
            if (str_starts_with(ltrim($line), '#')) {
                continue;
            }
            $kept[] = preg_replace('/\s+#.*$/', '', $line) ?? $line;
        }

        return $kept;
    }

    /** GitLab jobs are TOP-LEVEL keys; these top-level keys are not jobs. */
    private function isGitLabShape(array $lines): bool
    {
        foreach (['stages:', 'workflow:', 'variables:', 'default:', 'cache:'] as $key) {
            foreach ($lines as $line) {
                if (str_starts_with($line, $key)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Map job name => job body text, for both CI dialects.
     *
     * REACHABILITY IS DECIDED HERE, not by grepping for the invocation: a job
     * only appears in the result if its body invokes the gate AND the job can
     * actually run.
     *
     * @param  list<string>  $lines  comment-free lines
     * @return array<string, string>
     */
    private function jobBlocks(array $lines, bool $gitLab): array
    {
        $notJobs = ['stages', 'workflow', 'variables', 'default', 'cache', 'include', 'image', 'services'];
        $jobs = [];
        $current = null;
        $body = [];
        $inJobs = false;

        foreach ($lines as $line) {
            if (! $gitLab && preg_match('/^jobs:\s*$/', $line)) {
                $inJobs = true;

                continue;
            }
            // Any unindented key ends the `jobs:` section (Actions) or starts
            // the next top-level GitLab key.
            if (preg_match('/^([A-Za-z0-9_.:-]+):\s*$/', $line, $found)) {
                if ($gitLab) {
                    if ($current !== null) {
                        $jobs[$current] = implode("\n", $body);
                    }
                    $current = in_array($found[1], $notJobs, true) ? null : $found[1];
                    $body = [];

                    continue;
                }
                if ($inJobs && $current !== null) {
                    $jobs[$current] = implode("\n", $body);
                    $current = null;
                    $body = [];
                }
                $inJobs = false;

                continue;
            }
            if ($gitLab) {
                if ($current !== null) {
                    $body[] = $line;
                }

                continue;
            }
            if ($inJobs && preg_match('/^  ([A-Za-z0-9_-]+):\s*$/', $line, $found)) {
                if ($current !== null) {
                    $jobs[$current] = implode("\n", $body);
                }
                $current = $found[1];
                $body = [];

                continue;
            }
            if ($inJobs && $current !== null) {
                $body[] = $line;
            }
        }

        if ($current !== null) {
            $jobs[$current] = implode("\n", $body);
        }

        return array_filter(
            $jobs,
            fn (string $body, string $job): bool => $this->jobIsReachable($body),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Is this job actually able to run? The three ways a job that LOOKS wired
     * is really "a script nobody runs", all of which the original assertion
     * could not see:
     *   - `when: never` (GitLab) — declared, never executed.
     *   - an `if:` depending on `vars.`, `secrets.` or `env.`, none of which is
     *     guaranteed to exist on a run. An `if:` on `github.` event context IS
     *     accepted, because that context is always populated on a triggered
     *     run; `always()`/`success()` are accepted for the same reason.
     *   - an `if:` that is a CONSTANT-FALSE expression. This is the shape a
     *     later editor reaches for when a job misbehaves — type `if: false`
     *     and move on — and it leaves the job body, and therefore this gate,
     *     looking wired forever. It is a green check mark on a gate that
     *     checks nothing, which is the one outcome a reachability gate exists
     *     to prevent. See conditionIsConstantFalse() for exactly which
     *     spellings are read, and for the ones deliberately left to a human.
     */
    private function jobIsReachable(string $body): bool
    {
        if (preg_match('/^\s*when:\s*never\s*$/m', $body)) {
            return false;
        }
        if ($this->conditionIsConstantFalse($body)) {
            return false;
        }
        if (preg_match_all('/^\s*if:\s*(.+)$/m', $body, $found) < 1) {
            return true;
        }
        foreach ($found[1] as $condition) {
            if (preg_match('/\b(vars|secrets|env)\./', $condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is any JOB-LEVEL `if:` in this block a constant-false expression?
     *
     * DELIBERATE SCOPE — this is a LITERAL recogniser, not a GitHub-Actions
     * expression evaluator. It judges only shapes whose value is false no
     * matter what the run's context holds, so it can never reject a job that
     * would have run:
     *   - the literals `false` and `0`, in any case, quoted or bare, with or
     *     without a `${{ … }}` wrapper: `if: false`, `if: 'false'`,
     *     `if: "FALSE"`, `if: ${{ false }}`, `if: ${{false}}`;
     *   - the negation of a literal true — `if: !true`, `if: !(true)`,
     *     `if: not true` — which is the same shape wearing a disguise;
     *   - a literal `false && …` prefix, false by short-circuit whatever
     *     follows it.
     * Anything else is UNKNOWN here and therefore ACCEPTED, deliberately:
     * `if: true`, `${{ true }}`, any `github.*`/`needs.*`/`matrix` reference,
     * any comparison, and `always()`/`success()` are all legitimate. Guessing
     * at an expression this method cannot fully parse would reject jobs that
     * DO run, and a reachability gate that cries wolf is a gate people route
     * around. `false || …`, `1 == 2`, and a string literal such as `!'false'`
     * are also accepted — they are always-false in Actions, and they are left
     * alone because none of them is the shape an editor types to switch a job
     * off. `when:` gains nothing here either: its only unreachable value,
     * `never`, is already rejected above.
     *
     * JOB-LEVEL KEYS ONLY, and that is load-bearing. A job's own keys sit at
     * one indent — 0 for a GitLab job, 4 under `jobs:` for an Actions job —
     * and a step's `if:` is always deeper (`      - if: …`). Without the
     * indent test, any job that skips one of its steps with `if: false` (an
     * ordinary, healthy pattern) would be called unreachable and this gate
     * would fail a job that runs the gate — the mirror image of the hole it
     * exists to close.
     */
    private function conditionIsConstantFalse(string $body): bool
    {
        $lines = explode("\n", $body);
        $base = null;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $indent = strlen($line) - strlen(ltrim($line, ' '));
            $base = $base === null ? $indent : min($base, $indent);
        }
        if ($base === null) {
            return false;
        }

        foreach ($lines as $line) {
            if (preg_match('/^( *)if:\s*(.+)$/', $line, $found) !== 1) {
                continue;
            }
            if (strlen($found[1]) !== $base) {
                continue;
            }
            if ($this->isConstantFalseExpression($found[2])) {
                return true;
            }
        }

        return false;
    }

    /** Unwrap `${{ … }}`, one matching quote pair and a leading `!`/`not`. */
    private function isConstantFalseExpression(string $condition): bool
    {
        $expression = trim($condition);
        $negated = false;
        // Bounded at three passes: enough for every spelling named above
        // (`!${{ 'true' }}` uses all three), and a hard stop keeps a
        // pathologically nested value from turning this into an evaluator.
        for ($pass = 0; $pass < 3; $pass++) {
            $before = $expression;
            if (preg_match('/^\$\{\{(.*)\}\}$/s', $expression, $found) === 1) {
                $expression = trim($found[1]);
            }
            $length = strlen($expression);
            if ($length >= 2) {
                $first = $expression[0];
                $last = $expression[$length - 1];
                if (($first === "'" && $last === "'") || ($first === '"' && $last === '"')) {
                    $expression = trim(substr($expression, 1, -1));
                }
            }
            // Wrapping parens are another disguise (`!(true)`), so peel them
            // too — but only a BALANCED pair, so `f(a)` keeps its argument
            // and an unrecognised call is never truncated into a literal.
            if (str_starts_with($expression, '(') && $this->isWrappedInParens($expression)) {
                $expression = trim(substr($expression, 1, -1));
            }
            if (preg_match('/^(?:!\s*|not\s+)(.+)$/i', $expression, $found) === 1) {
                $negated = ! $negated;
                $expression = trim($found[1]);
            }
            if ($expression === $before) {
                break;
            }
        }

        // `false && …` is false whatever the rest evaluates to — but not when
        // the expression was inverted, because `!false && x` is `true && x`.
        if (! $negated && preg_match('/^false\s*&&/i', $expression) === 1) {
            return true;
        }

        $literal = strtolower($expression);

        return $negated
            ? in_array($literal, ['true', '1'], true)
            : in_array($literal, ['false', '0'], true);
    }

    /** Does this string's final `)` close the `(` it opens with? */
    private function isWrappedInParens(string $expression): bool
    {
        $depth = 0;
        $length = strlen($expression);
        for ($index = 0; $index < $length; $index++) {
            if ($expression[$index] === '(') {
                $depth++;
            } elseif ($expression[$index] === ')') {
                $depth--;
                if ($depth === 0) {
                    return $index === $length - 1;
                }
                if ($depth < 0) {
                    return false;
                }
            }
        }

        return false;
    }
}
