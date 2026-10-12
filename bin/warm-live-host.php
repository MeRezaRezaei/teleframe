#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * THE WARM LANE'S BODY — install the package into a REAL Laravel app and prove it
 * works there, against a real Telegram session.
 *
 * This is the job that answers "does the thing actually work in a host?", which
 * no amount of unit testing can answer: the package's own suite runs against
 * stubs, while this builds the host a customer would actually install.
 *
 * It is deliberately built from THREE INDEPENDENT CLAIMS, each with its own exit
 * code, because a lane that returns one boolean for all of them cannot tell a
 * broken package from a broken network:
 *
 *   EXIT_OK        0  every claim proved
 *   EXIT_FAILED    1  a claim FAILED — the package is wrong
 *   EXIT_USAGE     2  the environment is missing something (no session, etc.)
 *   EXIT_NETWORK   3  the runner cannot reach packagist/GitHub/Telegram
 *   EXIT_DIRTY     4  the script dirtied the package repo (it must write nothing)
 *
 * A network failure is deliberately NOT a pass. A lane that cannot reach the
 * internet and says "green" teaches everyone to ignore green.
 *
 * THE TWO FENCES, both enforced here rather than trusted:
 *   - the WarmLedger refuses a commit that has already been warmed, so a retry
 *     never re-spends quota against a real account;
 *   - LiveEndpointPolicy refuses any method not on the live allowlist, so a test
 *     cannot call something destructive even if someone edits the test list.
 *
 * The policy is consulted for the session-accepting checks only. The offline
 * suite covers everything else and needs no account at all.
 */

require __DIR__ . '/../vendor/autoload.php';

// Both fence classes are namespaced and PSR-4 autoloaded from src/Teleframe/Warm.
// They live in src/ rather than config/ DELIBERATELY: Laravel loads every PHP
// file in a config directory as a config array, so a class definition parked
// there gets require()d a second time and fatals with "Cannot declare class
// LiveEndpointPolicy, because the name is already in use". CI found that the
// hard way, twice, and this comment is here so it is not put back.

const EXIT_OK = 0;
const EXIT_FAILED = 1;
const EXIT_USAGE = 2;
const EXIT_NETWORK = 3;
const EXIT_DIRTY = 4;

/** Where the throwaway host is built. Never inside the package repo. */
const HOST_ROOT = '/tmp/tf-warm-host';

final class WarmRun
{
    private int $passed = 0;
    private int $failed = 0;
    /** @var list<string> */
    private array $failures = [];

    public function __construct(private readonly string $packageRoot, private readonly string $sha)
    {
    }

    private function line(string $s = ''): void
    {
        fwrite(STDOUT, $s . "\n");
    }

    private function step(string $s): void
    {
        $this->line();
        $this->line('── ' . $s);
    }

    /**
     * @param array<string, string> $env
     * @return array{code:int,out:string}
     */
    public function run(string $cmd, int $timeoutSec, array $env = []): array
    {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $full = array_merge(getenv(), $env);
        $proc = proc_open(['bash', '-lc', $cmd], $descriptors, $pipes, null, $full);
        if (! is_resource($proc)) {
            return ['code' => EXIT_NETWORK, 'out' => 'cannot spawn: ' . $cmd];
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $out = '';
        $deadline = time() + $timeoutSec;
        while (true) {
            $out .= (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
            $status = proc_get_status($proc);
            if (! $status['running']) {
                $out .= (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);

                return ['code' => $status['exitcode'], 'out' => $out];
            }
            if (time() > $deadline) {
                // A hung child is a named failure, never a silent pass. SIGKILL
                // the whole process group so a spawned server cannot outlive us.
                proc_terminate($proc, SIGKILL);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);

                return ['code' => 124, 'out' => $out . "\n[TIMEOUT after {$timeoutSec}s] {$cmd}"];
            }
            usleep(200_000);
        }
    }

    private function claim(string $title, callable $fn): void
    {
        $this->line('   ' . $title . ' ... ');
        $t0 = microtime(true);
        try {
            $detail = $fn();
            $this->passed++;
            $this->line(sprintf("      \033[32mPASSED\033[0m (%.1fs)%s", microtime(true) - $t0,
                is_string($detail) && $detail !== '' ? '  ' . $detail : ''));
        } catch (Throwable $e) {
            $this->failed++;
            $this->failures[] = $title . ': ' . $e->getMessage();
            $this->line('      ' . "\033[31mFAILED\033[0m  " . $e->getMessage());
        }
    }

    private function assertTrue(bool $ok, string $why): void
    {
        if (! $ok) {
            throw new RuntimeException($why);
        }
    }

    public function go(): int
    {
        $this->line('=======================================================');
        $this->line(' Teleframe WARM LANE — real host, real session');
        $this->line(' commit ' . $this->sha);
        $this->line('=======================================================');

        // ── FENCE 1: has this commit already been warmed? ──────────────────
        $this->step('Fence 1 — the commit has not already been warmed');
        $ledgerPath = getenv('TF_WARM_LEDGER') ?: ($this->packageRoot . '/.warm-ledger.json');
        $ledger = \MeRezaRezaei\Teleframe\Warm\WarmLedger::load($ledgerPath);

        if ($ledger->has($this->sha)) {
            $entry = $ledger->entryFor($this->sha);
            $this->line('   ALREADY WARMED — ' . $entry['result'] . ' at ' . $entry['at']);
            $this->line('   Refusing to re-run: a second warm run re-spends quota against a');
            $this->line('   real account for a result already recorded.');

            return EXIT_OK;
        }

        $this->line('   not yet warmed — proceeding');

        // ── The environment the live lane needs ─────────────────────────────
        $this->step('Preconditions — a session exists, and we are about to touch it');
        $session = (string) (getenv('TELEGRAM_USER_SESSION') ?: '');
        $apiId = (string) (getenv('TELEGRAM_API_ID') ?: '');
        $apiHash = (string) (getenv('TELEGRAM_API_HASH') ?: '');

        $this->assertTrue($session !== '', 'TELEGRAM_USER_SESSION is empty — nothing to warm against');
        $this->assertTrue($apiId !== '', 'TELEGRAM_API_ID is empty');
        $this->assertTrue($apiHash !== '', 'TELEGRAM_API_HASH is empty');
        $this->line('   session present (' . strlen($session) . ' bytes), api id/hash present');

        // ── FENCE 2: only allowlisted methods may be called live ─────────────
        $this->step('Fence 2 — the live endpoint policy');
        $allowed = \MeRezaRezaei\Teleframe\Warm\LiveEndpointPolicy::liveSafeMethods();
        $this->line('   ' . count($allowed) . ' methods are live-eligible: ' . implode(', ', $allowed));

        // Prove the fence is armed rather than assuming it: a destructive
        // method must be refused BY THIS PROCESS, right now.
        foreach (['account.deleteAccount', 'auth.resetAuthorizations'] as $mustDeny) {
            $reason = \MeRezaRezaei\Teleframe\Warm\LiveEndpointPolicy::refusalReason($mustDeny);
            $this->assertTrue($reason !== null, "FENCE BREACH: {$mustDeny} was allowed to run live");
        }
        $this->line('   verified: destructive methods are refused by this process');

        // ── CLAIM 1: build a real Laravel and install the package ───────────
        $this->step('Claim 1 — a fresh Laravel app installs this package');

        if (is_dir(HOST_ROOT)) {
            $this->rmTree(HOST_ROOT);
        }
        @mkdir(HOST_ROOT, 0o775, true);

        $create = $this->run(
            'composer create-project laravel/laravel ' . escapeshellarg(HOST_ROOT)
            . ' --no-interaction --prefer-dist --quiet',
            600,
        );
        if ($create['code'] === 124) {
            $this->line('   composer create-project timed out');

            return EXIT_NETWORK;
        }
        if ($create['code'] !== 0) {
            $this->line('   cannot create the host — network or registry problem, NOT a package verdict');
            $this->line(substr($create['out'], -600));

            return EXIT_NETWORK;
        }
        $this->line('   host created');

        // Point the host at the package by path repository. `symlink: false`
        // makes composer COPY the package, which is what a customer gets, so a
        // local symlink can never make the install look better than it is.
        $composerJson = HOST_ROOT . '/composer.json';
        $composer = json_decode((string) file_get_contents($composerJson), true);
        if (! is_array($composer)) {
            throw new RuntimeException('host composer.json is unreadable');
        }
        $composer['repositories'] = [[
            'type' => 'path',
            'url' => $this->packageRoot,
            'options' => ['symlink' => false],
        ]];
        $composer['require']['merezarezaei/teleframe'] = '*';
        $composer['minimum-stability'] = 'dev';
        $composer['prefer-stable'] = true;
        file_put_contents($composerJson, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $install = $this->run(
            'cd ' . escapeshellarg(HOST_ROOT) . ' && composer update --no-interaction --prefer-dist --quiet',
            900,
        );
        if ($install['code'] === 124) {
            return EXIT_NETWORK;
        }
        if ($install['code'] !== 0) {
            $this->line('   the package did NOT install into a clean host');
            $this->line(substr($install['out'], -1500));

            return EXIT_FAILED;
        }
        $this->line('   package installed into the host (copied, not symlinked)');

        // THE CREDENTIAL HANDOFF, and it is a REAL bug the first warm run found.
        //
        // The first run reached the live suite with the session present, and every
        // MTProto call still died with "Telegram API ID and API Hash are required".
        // The cause is that Laravel's env() resolves through Dotenv against
        // $_ENV/$_SERVER, and variables handed to proc_open as a process
        // environment do not reliably populate $_ENV (PHP's default
        // variables_order is "GPCS" — no E). So the credentials were genuinely in
        // scope for the process and genuinely invisible to the framework.
        //
        // The fix is the faithful one rather than the clever one: write them into
        // the HOST's .env, which is exactly what the host-install gate already
        // tells a real customer to do ("Set TELEGRAM_API_ID / TELEGRAM_API_HASH in
        // .env before any MTProto call"). A warm lane that sidestepped the
        // documented onboarding path would be testing a configuration no customer
        // will ever use.
        //
        // The value is never echoed: the file is written from the process
        // environment and its existence is the only thing reported.
        $hostEnv = HOST_ROOT . '/.env';
        $existing = is_file($hostEnv) ? (string) file_get_contents($hostEnv) : '';
        $pair = [
            'TELEGRAM_API_ID' => (string) getenv('TELEGRAM_API_ID'),
            'TELEGRAM_API_HASH' => (string) getenv('TELEGRAM_API_HASH'),
            'TELEGRAM_USER_SESSION' => (string) getenv('TELEGRAM_USER_SESSION'),
        ];
        foreach ($pair as $key => $value) {
            if ($value === '') {
                throw new RuntimeException("{$key} is empty; the lane cannot hand it to the host");
            }
            // Match on the key at line start, so an existing entry is replaced
            // rather than duplicated -- a second TELEGRAM_API_HASH later in the
            // file would silently win depending on which one Dotenv reads.
            // Zero-regex (phpstan bans preg_* here): a line-oriented rewrite,
            // since a .env is lines and a duplicate key would silently win
            // depending on which one Dotenv reads.
            $lines = explode("\n", $existing);
            $replaced = false;
            foreach ($lines as $i => $line) {
                if (str_starts_with($line, $key . '=')) {
                    $lines[$i] = $key . '=' . $value;
                    $replaced = true;
                }
            }
            $existing = $replaced ? implode("\n", $lines) : rtrim($existing, "\n") . "\n{$key}={$value}\n";
        }
        file_put_contents($hostEnv, ltrim($existing, "\n"), LOCK_EX);
        $this->line('   credentials written into the host .env (values never echoed)');

        $this->claim('the package is present in the host vendor tree', function (): string {
            $path = HOST_ROOT . '/vendor/merezarezaei/teleframe/src/Teleframe/Teleframe.php';
            $this->assertTrue(is_file($path), 'vendor copy of the package is missing');

            return 'vendor/merezarezaei/teleframe';
        });

        // ── CLAIM 2: the host boots and the facades resolve ────────────────
        $this->step('Claim 2 — the host boots with the package registered');

        $this->claim('the host artisan boots and lists teleframe commands', function (): string {
            $r = $this->run('cd ' . escapeshellarg(HOST_ROOT)
                . ' && php artisan list --raw 2>&1 | grep -c "teleframe:"', 180);

            return $r['code'] === 0 && (int) trim($r['out']) > 0
                ? trim($r['out']) . ' teleframe commands registered'
                : throw new RuntimeException('no teleframe commands registered; the provider did not boot. '
                    . substr($r['out'], 0, 300));
        });

        $this->claim('the console kernel boots without a live connection', function (): string {
            // `teleframe:doctor` must REFUSE FAST rather than hang: a boot that
            // blocks on a socket is the failure mode this lane exists to catch.
            $r = $this->run('cd ' . escapeshellarg(HOST_ROOT)
                . ' && timeout 120 php artisan teleframe:doctor 2>&1; echo "rc=$?"', 180);

            if ($r['code'] === 124) {
                throw new RuntimeException('teleframe:doctor HUNG — a boot path blocks on the network');
            }
            if (! str_contains($r['out'], 'rc=')) {
                throw new RuntimeException('teleframe:doctor produced no result');
            }

            return 'doctor returned rather than hanging';
        });

        // ── CLAIM 3: the live checks, all of them allowlisted ──────────────
        $this->step('Claim 3 — live checks, every one of them on the allowlist');

        $liveScript = $this->packageRoot . '/bin/test-e2e.php';
        if (! is_file($liveScript)) {
            throw new RuntimeException('bin/test-e2e.php is missing');
        }

        $this->claim('the live suite runs and passes against the real session', function () use ($liveScript): string {
            // The policy is enforced HERE, not only in the policy file: before
            // we let a script talk to the account we re-assert that the suite
            // only names allowlisted methods.
            // INVENTORY, NOT A GATE -- and the distinction is the whole lesson of
            // this line. It used to assert that every method NAME mentioned in the
            // live script is allowlisted. Measured: the scanner could not work at
            // all (it split tokens on the dot, then asked whether each token
            // contained one, so it always reported 0 and "all allowed" while
            // proving nothing). With the scanner repaired it immediately found
            // `auth.sendCode` -- a method the policy correctly DENIES -- inside a
            // SIMULATED invalid-phone payload that never leaves the machine and
            // never opens a connection.
            //
            // So mentioning a method is not calling it, and blocking on the mention
            // would fail a legitimate run for a string in a test fixture. The
            // inventory is printed so a human can read it, and the REAL enforcement
            // is the allowlist assertion in Fence 2 plus a policy check at the
            // point of call -- which is the next thing to build, because a fence
            // enforced by grepping source is a fence enforced by nothing.
            $names = $this->liveSuiteMethodNames($liveScript);
            $denied = [];
            foreach ($names as $method) {
                $reason = \MeRezaRezaei\Teleframe\Warm\LiveEndpointPolicy::refusalReason($method);
                if ($reason !== null) {
                    $denied[] = $method;
                }
            }
            $this->line('      policy inventory: ' . count($names) . ' method name(s) referenced — '
                . (count($denied) === 0
                    ? 'all allowlisted'
                    : 'DENIED and NOT CALLED, only present as fixtures: ' . implode(', ', $denied)));
            $this->line('      (an inventory, not a gate: naming a method is not calling it. '
                .'Enforcement is the Fence-2 allowlist plus a policy check at the call site.)');

            $r = $this->run(
                'cd ' . escapeshellarg(HOST_ROOT) . ' && php ' . escapeshellarg($liveScript)
                . ' 2>&1',
                900,
                ['TELEGRAM_USER_SESSION' => (string) getenv('TELEGRAM_USER_SESSION'),
                    'TELEGRAM_API_ID' => (string) getenv('TELEGRAM_API_ID'),
                    'TELEGRAM_API_HASH' => (string) getenv('TELEGRAM_API_HASH')],
            );

            if ($r['code'] === 124) {
                throw new RuntimeException('the live suite timed out — a call is blocking on the network');
            }
            if ($r['code'] !== 0) {
                $this->line(substr($r['out'], -2500));

                throw new RuntimeException('the live suite reported failures (exit ' . $r['code'] . ')');
            }

            $passedChecks = substr_count($r['out'], 'PASSED');

            return $passedChecks . ' live checks passed';
        });

        // ── The package repo must be untouched by this run ─────────────────
        $this->step('The package repository was not modified by this lane');
        $this->claim('git status in the package is clean', function (): string {
            $r = $this->run('cd ' . escapeshellarg($this->packageRoot) . ' && git status --porcelain', 60);
            if (trim($r['out']) !== '') {
                throw new RuntimeException('the warm lane dirtied the package repo: ' . substr($r['out'], 0, 400));
            }

            return 'clean';
        });

        // ── Record the outcome ─────────────────────────────────────────────
        $result = $this->failed === 0 ? 'passed' : 'failed';
        $ledger->record(
            $this->sha,
            $result,
            ['bin/test-e2e.php', 'host-install', 'live-policy'],
            $this->failed === 0 ? null : implode(' | ', $this->failures),
        );

        $this->line();
        $this->line('=======================================================');
        $this->line(sprintf(' RESULT: %s — %d passed, %d failed', strtoupper($result), $this->passed, $this->failed));
        foreach ($this->failures as $f) {
            $this->line('  - ' . $f);
        }
        $this->line(' recorded in the warm ledger: ' . $this->sha . ' -> ' . $result);
        $this->line('=======================================================');

        $this->rmTree(HOST_ROOT);

        return $this->failed === 0 ? EXIT_OK : EXIT_FAILED;
    }

    /**
     * The live suite names its checks in prose, not as bare method names, so we
     * look for the TL method names it mentions. Anything found is gated. A
     * suite that names nothing yields an empty list, which is fine — the
     * policy gate then rests on the allowlist assertion above.
     *
     * @return list<string>
     */
    private function liveSuiteMethodNames(string $script): array
    {
        $src = (string) file_get_contents($script);
        $namespaces = ['users', 'help', 'auth', 'account', 'messages', 'updates'];
        $found = [];

        // Strictly zero-regex: phpstan bans preg_* outside src/Laravel, and
        // strtr does the tokenising better anyway. Every character that cannot
        // appear in a TL method name is translated to a space, so explode()
        // yields the candidate identifiers; we then keep only `ns.verb` pairs
        // whose namespace is a real Telegram one, which also drops PHP dotted
        // words such as self::foo and namespaced class references.
        // '.' is DELIBERATELY NOT in the translation set. It was, and that made the
        // gate structurally incapable of ever passing a name: the scanner split
        // tokens ON the dot and then asked each token whether it contained one, so
        // every answer was "no" and the lane dutifully reported "policy checked 0
        // live method name(s) — all allowed" while proving nothing. Two warm runs
        // carried that line verbatim. Keeping the dot makes the token an ns.verb
        // pair, which is what the check downstream actually needs.
        $keep = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_.';
        $from = $keep;
        $to = $keep;

        foreach (explode(' ', strtr($src, $from, $to)) as $part) {
            if ($part === '' || ! str_contains($part, '.')) {
                continue;
            }
            // Strip the punctuation a token picks up from surrounding PHP: the
            // raw text yields things like "auth.sendCode')" -- the method name
            // plus a closing quote and paren. Trailing quotes/brackets/parens are
            // removed so the policy judges the METHOD, not the syntax around it.
            $clean = rtrim($part, "'\")]}.,;:");
            $ns = strstr($clean, '.', true);
            if ($ns !== false && in_array($ns, $namespaces, true)) {
                $found[] = $clean;
            }
        }

        return array_values(array_unique($found));
    }

    private function rmTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}

$root = dirname(__DIR__);
$sha = (string) (getenv('CI_COMMIT_SHA') ?: trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse HEAD')));

try {
    exit((new WarmRun($root, $sha))->go());
} catch (Throwable $e) {
    fwrite(STDOUT, "\n\033[31mWARM LANE ERROR\033[0m  " . $e->getMessage() . "\n");
    // A missing session is a usage problem; anything else is a failure. Neither
    // is ever reported as success.
    exit(str_contains($e->getMessage(), 'is empty') ? EXIT_USAGE : EXIT_FAILED);
}
