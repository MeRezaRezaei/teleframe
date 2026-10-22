<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Credentials;

use PHPUnit\Framework\TestCase;

/**
 * bin/scan-credentials.php — the four bypasses an audit reproduced, pinned.
 *
 * Before 2026-09-26 this file did not exist and NOTHING pinned the scanner:
 * `grep -r scan-credentials tests/ src/` returned nothing, which is how four
 * bypasses survived. Each test below fails against the pre-fix script and
 * passes against the current one; the regression notes say which bypass.
 *
 * Run just this suite:  vendor/bin/phpunit tests/Credentials
 */
final class CredentialGuardTest extends TestCase
{
    private ?ScanRunner $repo = null;

    protected function tearDown(): void
    {
        $this->repo?->destroy();
        $this->repo = null;
    }

    private function sandbox(): ScanRunner
    {
        $this->repo ??= ScanRunner::repo(dirname(__DIR__, 2).'/bin/scan-credentials.php');

        return $this->repo;
    }

    /** A clean, committed, empty-of-secrets starting point. */
    private function committed(): ScanRunner
    {
        $repo = $this->sandbox();
        $repo->write('README.md', "# scratch\n\nnothing sensitive here\n");
        $repo->commit('init');

        return $repo;
    }

    // -----------------------------------------------------------------------
    // BYPASS 1 — untracked files were invisible: the guard read
    // `git ls-files`, i.e. TRACKED files only, so it reported "clean" on
    // exactly the state a developer is in BEFORE committing the secret.
    // -----------------------------------------------------------------------

    public function test_an_untracked_file_holding_a_session_run_fails(): void
    {
        $repo = $this->committed();
        $secret = ScanRunner::fakeRun();
        $repo->write('notes.txt', "draft\n{$secret}\n");

        $r = $repo->scan();

        self::assertSame(1, $r['code'], 'an untracked, unstaged secret must fail the gate');
        self::assertStringContainsString('notes.txt', $r['all']);
        self::assertStringNotContainsString($secret, $r['all'], 'the guard must never print what it found');
    }

    public function test_the_clean_line_advertises_that_untracked_files_are_scanned(): void
    {
        $r = $this->committed()->scan();

        self::assertSame(0, $r['code']);
        self::assertStringContainsString('tracked + untracked', $r['out']);
    }

    public function test_an_untracked_file_without_a_credential_still_passes(): void
    {
        $repo = $this->committed();
        $repo->write('notes.txt', "just a draft\n");

        self::assertSame(0, $repo->scan()['code']);
    }

    public function test_a_gitignored_file_is_not_scanned_and_that_gap_is_printed(): void
    {
        $repo = $this->committed();
        $secret = ScanRunner::fakeRun();
        $repo->write('local/scratch.txt', $secret); // covered by .gitignore? no — add it
        $repo->write('.gitignore', "vendor/\n.env\n*.bin\nlocal/\n");

        $r = $repo->scan();

        self::assertSame(0, $r['code'], 'an ignored path is out of scope by design');
        self::assertStringContainsString('not walked', $r['out'], 'but the omission is stated, not silent');
        self::assertStringNotContainsString($secret, $r['all']);
    }

    // -----------------------------------------------------------------------
    // The .env rule must survive the untracked sweep: a developer's own local
    // .env is the HEALTHY state and must not start failing the gate, while a
    // COMMITTED .env still must.
    // -----------------------------------------------------------------------

    public function test_a_committed_env_file_fails(): void
    {
        $repo = $this->sandbox();
        $repo->write('.gitignore', "vendor/\n*.bin\n"); // stop ignoring .env for this case
        $repo->write('.env', "TELEGRAM_USER_SESSION=placeholder\n");
        $repo->commit('oops, committed an env file');

        $r = $repo->scan();

        self::assertSame(1, $r['code']);
        self::assertStringContainsString('committed env file', $r['all']);
    }

    public function test_a_local_untracked_env_file_never_fails_the_gate(): void
    {
        // The untracked sweep must not turn every developer's machine red: a
        // .env on disk that was never committed is the HEALTHY state. The .env
        // rule stays a rule about COMMITTING, so it reads the tracked set only.
        $repo = $this->sandbox();
        $repo->write('README.md', "# scratch\n");
        $repo->commit('init');
        $repo->write('.env', "TELEGRAM_USER_SESSION=placeholder\n");
        $repo->write('.env.local', "APP_ENV=local\n");
        $repo->write('.env.production', "APP_ENV=prod\n");

        $r = $repo->scan();

        self::assertSame(0, $r['code'], 'an uncommitted .env must never fail the gate');
        self::assertStringNotContainsString('committed env file', $r['all']);
    }

    public function test_untracking_a_committed_env_file_clears_the_finding(): void
    {
        $repo = $this->sandbox();
        $repo->write('.gitignore', "vendor/\n*.bin\n");
        $repo->write('.env', "X=1\n");
        $repo->commit('oops');
        $repo->untrack('.env');

        self::assertSame(0, $repo->scan()['code'], 'untracking is the documented fix');
    }

    public function test_env_example_and_dist_templates_stay_allowed(): void
    {
        $repo = $this->sandbox();
        $repo->write('.env.example', "TELEGRAM_API_ID=\n");
        $repo->write('.env.dist', "TELEGRAM_API_ID=\n");
        $repo->commit('templates');

        self::assertSame(0, $repo->scan()['code']);
    }

    // -----------------------------------------------------------------------
    // BYPASS 2 — binary files were skipped WHOLESALE (`str_contains(..., "\0")`
    // → continue), so a secret in a .png/.zip/.phar was never looked at.
    // -----------------------------------------------------------------------

    public function test_a_binary_file_holding_a_session_run_fails_and_says_it_was_a_prefix_scan(): void
    {
        $repo = $this->committed();
        $secret = ScanRunner::fakeRun();
        // A real NUL in the first 8 KiB is what used to cause a wholesale skip.
        $repo->write('payload.phar', "\x00\x01\x02binary-ish header\x00".$secret);

        $r = $repo->scan();

        self::assertSame(1, $r['code'], 'a binary file must be scanned, not skipped');
        self::assertStringContainsString('payload.phar', $r['all']);
        self::assertStringContainsString('binary prefix scan', $r['all']);
        self::assertStringNotContainsString($secret, $r['all']);
    }

    public function test_the_clean_line_reports_that_binary_files_were_scanned(): void
    {
        $repo = $this->committed();
        $repo->write('plain.bin', "\x00\x01no credential in here\x00");
        $repo->git(['add', '-f', 'plain.bin']);
        $repo->commit('a binary file with nothing to hide');

        $r = $repo->scan();

        self::assertSame(0, $r['code']);
        self::assertStringContainsString('binary scanned', $r['out']);
    }

    public function test_a_binary_tail_past_the_prefix_is_counted_not_hidden(): void
    {
        $repo = $this->committed();
        $secret = ScanRunner::fakeRun();
        $repo->write('big.dat', "\x00".str_repeat('A', 4096).$secret); // .bin is gitignored here

        $r = $repo->scan(['--binary-prefix=1024']);

        self::assertSame(0, $r['code'], 'past the prefix is out of scope');
        self::assertStringContainsString('truncated', $r['out'], 'the truncation is reported');
        self::assertStringNotContainsString($secret, $r['all']);

        $wider = $repo->scan(['--binary-prefix=1048576']);
        self::assertSame(1, $wider['code'], 'a bigger prefix finds it');
    }

    // -----------------------------------------------------------------------
    // BYPASS 3 — FRAGMENTATION passed: the rule was a single unbroken run of
    // >= 200 chars, so splitting a session into 8 x 150-char chunks evaded it.
    // -----------------------------------------------------------------------

    public function test_a_session_split_into_eight_chunks_fails(): void
    {
        $repo = $this->committed();
        $secret = ScanRunner::fakeRun();
        $chunks = str_split($secret, 150);
        self::assertCount(8, $chunks, 'the 8 x 150 shape from the audit');

        // A real concatenation: eight quoted chunks joined by newlines. (A
        // backslash separator is a DOCUMENTED LIMIT, not a detection: '\\' is a
        // hard break, because making it transparent glues every PHP FQCN into
        // one long run — see test_a_long_generated_class_name_block_* below.)
        $repo->write('split.txt', "session = \n  \"".implode("\"\n  \"", $chunks)."\"\n");

        $r = $repo->scan();

        self::assertSame(1, $r['code'], 'a split run must not pass');
        self::assertStringContainsString('fragmented run', $r['all']);
        self::assertStringNotContainsString($secret, $r['all']);
        self::assertStringNotContainsString($chunks[0], $r['all'], 'not even one chunk may be printed');
    }

    public function test_a_session_split_on_quotes_and_commas_fails(): void
    {
        $repo = $this->committed();
        $secret = ScanRunner::fakeRun();
        $parts = [];
        foreach (str_split($secret, 120) as $chunk) {
            $parts[] = $chunk;
        }
        $repo->write('json-ish.txt', "{\n  \"session\": \"".implode('", "', $parts)."\"\n}\n");

        $r = $repo->scan();

        self::assertSame(1, $r['code']);
        self::assertStringContainsString('fragmented run', $r['all']);
    }

    public function test_disabling_the_fragment_pass_is_honoured_and_advertised(): void
    {
        $repo = $this->committed();
        $secret = ScanRunner::fakeRun();
        $repo->write('split.txt', implode("\n", str_split($secret, 150)));

        $off = $repo->scan(['--no-fragment']);
        self::assertSame(0, $off['code'], 'the pass really is off');
        self::assertStringContainsString('fragment OFF', $off['out'], 'and the weaker coverage is stated');

        self::assertSame(1, $repo->scan()['code'], 'on by default');
    }

    public function test_ordinary_prose_and_identifier_lists_are_not_fragmented_runs(): void
    {
        // The fragmentation pass must not fire on English or on a camelCase
        // method list; a false positive here would make the gate unrunnable.
        $repo = $this->committed();
        $prose = 'mirroring the map pattern with getChannel/createChannel/uploadPart/sendDocument/sendMessage '
            .'and searchMessages/historyPage/deleteMessages/getFile plus registry post/find and metadata post '
            ."for the store bucket, never buffering more than one part size at a time.\n";
        $repo->write('plan.md', $prose);
        $repo->write('ids.json', json_encode(array_map(
            static fn (int $i): string => ScanRunner::fakeRun(8).$i,
            range(1, 40),
        ), JSON_PRETTY_PRINT)."\n");

        $r = $repo->scan();

        self::assertSame(0, $r['code'], 'prose must not be reported as a split credential');
    }

    public function test_a_pem_armed_public_key_is_not_read_as_a_split_credential(): void
    {
        // 64-column base64 is PEM/DER line wrapping, not a session string cut
        // into pieces. src/Core/MTProto/resources/telegram_public_key.pub is
        // committed on purpose and the guard must stay green because of it.
        $repo = $this->committed();
        $body = trim(str_repeat(ScanRunner::fakeRun(64)."\n", 6), "\n");
        $repo->write('public_key.pub', "-----BEGIN RSA PUBLIC KEY-----\n{$body}\n-----END RSA PUBLIC KEY-----\n");
        $repo->write('key.pub', "-----BEGIN RSA PUBLIC KEY-----\n{$body}\n-----END RSA PUBLIC KEY-----\n");

        $r = $repo->scan();

        self::assertSame(0, $r['code'], 'a committed public key must not fail the gate');
    }

    public function test_a_long_generated_class_name_block_is_not_a_fragmented_run(): void
    {
        // Generated PHP glues 60+ class-name chunks through the same separators
        // a split uses; with an embedded crc32 they even contain digits.
        $repo = $this->committed();
        $uses = '';
        for ($i = 0; $i < 70; $i++) {
            $uses .= "use MeRezaRezaei\\Teleframe\\Schema\\Generated\\Models\\TlMessagesSearchResultsSearchReB1534ec0e56fChats{$i};\n";
        }
        $repo->write('generated.php', "<?php\n\n{$uses}");

        self::assertSame(0, $repo->scan()['code']);
    }

    public function test_a_split_secret_beyond_the_chunk_ceiling_is_reported_as_a_limit(): void
    {
        // 40 pieces of 27 chars: under the per-chunk floor, so out of scope. The
        // limit is documented rather than hidden; this test exists so a future
        // reader knows it was a decision.
        $repo = $this->committed();
        $repo->write('many.txt', implode("\n", str_split(ScanRunner::fakeRun(), 27)));

        $r = $repo->scan();

        self::assertSame(0, $r['code']);
        self::assertStringContainsString('fragment on', $r['out']);
    }

    // -----------------------------------------------------------------------
    // BYPASS 4 — HISTORY was never scanned: the guard read the working tree
    // only, so the SEC-1 case (a secret committed, then redacted in the tree)
    // was structurally invisible to it.
    // -----------------------------------------------------------------------

    public function test_a_secret_committed_then_redacted_in_the_tree_is_found_in_history(): void
    {
        $repo = $this->sandbox();
        $secret = ScanRunner::fakeRun();
        $repo->write('dump.sql', "INSERT INTO sessions (session) VALUES ('{$secret}');\n");
        $repo->commit('the mistake');
        $repo->write('dump.sql', "INSERT INTO sessions (session) VALUES ('[REDACTED]');\n");
        $repo->commit('redact it');

        $treeOnly = $repo->scan(['--history=0']);
        self::assertSame(0, $treeOnly['code'], 'the working tree really is clean now');

        $r = $repo->scan();

        self::assertSame(1, $r['code'], 'the secret is still reachable in history');
        self::assertStringContainsString('history@dump.sql', $r['all']);
        self::assertStringNotContainsString($secret, $r['all']);
    }

    public function test_a_secret_on_a_side_branch_is_found_even_when_main_is_clean(): void
    {
        // Both surviving blobs of this repo's own 2026-09-14 exposure live on
        // cycle-9/cycle-10 refs, NOT on main's ancestry, so a HEAD-only reach
        // would miss exactly the case the guard exists for.
        $repo = $this->sandbox();
        $secret = ScanRunner::fakeRun();
        $repo->write('notes.txt', "keep\n");
        $repo->commit('init');
        $repo->git(['checkout', '-q', '-b', 'cycle-9-leak']);
        $repo->write('leak.txt', $secret);
        $repo->commit('leak on a side branch');
        $repo->git(['checkout', '-q', 'main']);
        $repo->write('notes.txt', "keep\nclean\n");
        $repo->commit('main is clean');

        $head = $repo->scan(['--history-refs=HEAD']);
        self::assertSame(0, $head['code'], 'HEAD ancestry alone does not reach the side branch');

        $all = $repo->scan();
        self::assertSame(1, $all['code'], 'reaching every ref does');
        self::assertStringContainsString('history@leak.txt', $all['all']);
    }

    public function test_turning_history_off_is_disclosed_on_the_clean_line(): void
    {
        $r = $this->committed()->scan(['--history=0']);

        self::assertSame(0, $r['code']);
        self::assertStringContainsString('history NOT SCANNED', $r['out']);
        self::assertStringContainsString('NOT covered', $r['out'], 'the omission names what is lost');
        self::assertStringContainsString('history depth none', $r['out']);
    }

    public function test_the_clean_line_states_the_history_depth_actually_reached(): void
    {
        $r = $this->committed()->scan(['--history=all']);

        self::assertSame(0, $r['code']);
        self::assertStringContainsString('every commit reachable', $r['out']);
    }

    // -----------------------------------------------------------------------
    // The baseline: a receipt, not a pardon.
    // -----------------------------------------------------------------------

    public function test_a_baselined_history_blob_is_accepted_but_still_printed(): void
    {
        $repo = $this->sandbox();
        $secret = ScanRunner::fakeRun();
        $repo->write('dump.sql', $secret);
        $repo->commit('the mistake');
        $repo->write('dump.sql', "[REDACTED]\n");
        $repo->commit('redact it');
        $blob = trim($repo->git(['rev-parse', 'HEAD~1:dump.sql']));
        $repo->write('.credential-guard-baseline', "# known\n{$blob} dump.sql\n");

        $r = $repo->scan();

        self::assertSame(0, $r['code'], 'a receipted exposure does not fail the gate');
        self::assertStringContainsString('ACCEPTED', $r['out']);
        self::assertStringContainsString('still reachable', $r['out'], 'it is never quietly clean');
        self::assertStringContainsString(substr($blob, 0, 12), $r['out'], 'the receipt names the blob to purge');
        self::assertStringNotContainsString($secret, $r['all']);

        // Remove the receipt and the same repository goes red again.
        $repo->write('.credential-guard-baseline', "# nothing receipted\n");
        self::assertSame(1, $repo->scan()['code'], 'deleting the receipt re-arms the gate');
    }

    public function test_history_baseline_none_accepts_nothing(): void
    {
        $repo = $this->sandbox();
        $secret = ScanRunner::fakeRun();
        $repo->write('dump.sql', $secret);
        $repo->commit('the mistake');
        $repo->write('dump.sql', "[REDACTED]\n");
        $repo->commit('redact it');
        $blob = trim($repo->git(['rev-parse', 'HEAD~1:dump.sql']));
        $repo->write('.credential-guard-baseline', "{$blob} dump.sql\n");

        self::assertSame(1, $repo->scan(['--history-baseline=none'])['code']);
    }

    // -----------------------------------------------------------------------
    // Safety: the guard must never print a matched value, in any mode.
    // -----------------------------------------------------------------------

    public function test_no_mode_ever_prints_the_matched_value(): void
    {
        $secret = ScanRunner::fakeRun();
        $fragmented = implode("\n", str_split($secret, 150));

        $cases = [
            'contiguous' => static function (ScanRunner $r) use ($secret): void {
                $r->write('a.txt', "key={$secret}\n");
                $r->git(['add', '-A']);
                $r->commit('c');
            },
            'untracked' => static function (ScanRunner $r) use ($secret): void {
                $r->write('b.txt', $secret);
            },
            'fragmented' => static function (ScanRunner $r) use ($fragmented): void {
                $r->write('c.txt', $fragmented);
            },
            'binary' => static function (ScanRunner $r) use ($secret): void {
                $r->write('d.dat', "\x00\x00".$secret); // .bin is gitignored in the sandbox
            },
            'history' => static function (ScanRunner $r) use ($secret): void {
                $r->write('e.txt', $secret);
                $r->commit('leak');
                $r->write('e.txt', "clean\n");
                $r->commit('redact');
            },
        ];

        foreach ($cases as $label => $seed) {
            $repo = ScanRunner::repo(dirname(__DIR__, 2).'/bin/scan-credentials.php');
            $repo->write('README.md', "# scratch\n");
            $repo->commit('init');
            $seed($repo);
            $r = $repo->scan();
            $repo->destroy();

            self::assertStringNotContainsString($secret, $r['all'], "{$label}: stdout/stderr leaked the value");
            self::assertStringNotContainsString(substr($secret, 0, 64), $r['all'], "{$label}: a prefix leaked");
            self::assertStringContainsString('(value withheld)', $r['all'], "{$label}: findings must be marked withheld");
        }
    }

    // -----------------------------------------------------------------------
    // Exit codes and the "partial scan is not a pass" rule.
    // -----------------------------------------------------------------------

    public function test_exit_codes_are_clean_findings_and_usage_error(): void
    {
        $repo = $this->committed();

        self::assertSame(0, $repo->scan()['code'], 'clean');

        $repo->write('leak.txt', ScanRunner::fakeRun());
        self::assertSame(1, $repo->scan()['code'], 'findings');

        self::assertSame(2, $repo->scan(['--min=4'])['code'], 'usage error');
        self::assertSame(2, $repo->scan(['--nope'])['code'], 'unknown option');
        self::assertSame(2, $repo->scan(['--root=/definitely/not/here'])['code'], 'bad root');
    }

    public function test_a_capped_scan_reports_incomplete_and_exits_three_never_zero(): void
    {
        $repo = $this->committed();
        $repo->write('big.txt', str_repeat("filler line\n", 4000));

        $r = $repo->scan(['--max-total-bytes=2048']);

        self::assertSame(3, $r['code'], 'a partial scan is not a pass');
        self::assertStringContainsString('INCOMPLETE', $r['err']);
        self::assertStringContainsString('NOT scanned', $r['err']);
    }

    public function test_quiet_suppresses_the_clean_lines_but_not_findings(): void
    {
        $repo = $this->committed();
        self::assertSame('', $repo->scan(['--quiet'])['out']);

        $repo->write('leak.txt', ScanRunner::fakeRun());
        $loud = $repo->scan(['--quiet']);
        self::assertSame(1, $loud['code']);
        self::assertStringContainsString('leak.txt', $loud['all'], 'quiet must never hide a finding');
    }

    // -----------------------------------------------------------------------
    // Regression surface of the original six documented cases.
    // -----------------------------------------------------------------------

    public function test_the_six_documented_cases_still_behave(): void
    {
        // 1. clean -> 0
        self::assertSame(0, $this->committed()->scan()['code']);

        // 2. committed .env -> 1
        $env = ScanRunner::repo(dirname(__DIR__, 2).'/bin/scan-credentials.php');
        $env->write('.gitignore', "vendor/\n*.bin\n");
        $env->write('.env', "X=1\n");
        $env->commit('env');
        self::assertSame(1, $env->scan()['code']);
        $env->destroy();

        // 3. 1080-char blob -> 1, value withheld
        $blob = ScanRunner::repo(dirname(__DIR__, 2).'/bin/scan-credentials.php');
        $blob->write('x.txt', ScanRunner::fakeRun());
        $blob->commit('blob');
        $r = $blob->scan();
        self::assertSame(1, $r['code']);
        self::assertStringContainsString('1080 chars', $r['all']);
        $blob->destroy();

        // 4. .env.example -> 0
        $tpl = ScanRunner::repo(dirname(__DIR__, 2).'/bin/scan-credentials.php');
        $tpl->write('.env.example', "X=\n");
        $tpl->commit('tpl');
        self::assertSame(0, $tpl->scan()['code']);
        $tpl->destroy();

        // 5. no-git recursive fallback -> 0
        $bare = sys_get_temp_dir().'/credscan-bare-'.bin2hex(random_bytes(6));
        mkdir($bare.'/nested', 0o777, true);
        file_put_contents($bare.'/nested/ok.txt', "nothing here\n");
        $fallback = (new ScanRunner(dirname(__DIR__, 2).'/bin/scan-credentials.php', $bare))->scan();
        self::assertSame(0, $fallback['code']);
        self::assertStringContainsString('recursive walk (no git checkout)', $fallback['out']);
        unlink($bare.'/nested/ok.txt');
        rmdir($bare.'/nested');
        rmdir($bare);

        // 6. bad --min=4 -> 2
        self::assertSame(2, $this->committed()->scan(['--min=4'])['code']);
    }

    public function test_the_scanner_itself_stays_zero_regex(): void
    {
        // bin/ is not on the preg_* allow-list either; the walk is strspn/strcspn.
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/bin/scan-credentials.php');

        self::assertStringNotContainsString('preg_', $source);
    }

    // -----------------------------------------------------------------------
    // BYPASS 5 — the alphabet had no `-` or `_`, so a url-safe base64 credential
    // was structurally invisible. This is the credential the guard exists for:
    // Pyrogram's and Telethon's `StringSession` both emit
    // `base64.urlsafe_b64encode(...)`, and that IS the shape of a Telegram
    // session string.
    //
    // PROVEN, not hypothesised: a committed 468-char url-safe blob of 351
    // random bytes scanned `clean`, EXIT=0, while the byte-identical payload
    // encoded in standard base64 scanned FAIL, EXIT=1. Same guard, same length,
    // opposite verdict.
    //
    // The fixtures below are ASSEMBLED AT RUNTIME from random bytes, never
    // literals: a 468-char url-safe literal written into this file would itself
    // be a finding in the repo's own tests/ directory, which the guard scans.
    // They also cannot be faked with ScanRunner::fakeRun(), whose alphabet is
    // [A-Za-z0-9] and which therefore NEVER contains `-` or `_` — a fakeRun
    // fixture cannot exercise this path at all, which is precisely how the gap
    // survived a suite full of credential fixtures.
    // -----------------------------------------------------------------------

    /** 351 random bytes as base64url — the shape of a Telegram session string. */
    private static function urlSafeSession(int $bytes = 351): string
    {
        return strtr(base64_encode(random_bytes($bytes)), '+/', '-_');
    }

    public function test_a_url_safe_base64_session_is_a_contiguous_finding(): void
    {
        $repo = $this->committed();
        $secret = self::urlSafeSession();
        // Asserted, not assumed: a url-safe blob is only 470-ish chars and only
        // fails if `-`/`_` really are run characters.
        self::assertStringContainsString('-', $secret.'-', 'the fixture must carry base64url characters at all');
        self::assertGreaterThanOrEqual(200, strlen($secret));
        $repo->write('session.txt', "TELEGRAM_SESSION={$secret}\n");

        $r = $repo->scan();

        self::assertSame(1, $r['code'], 'a url-safe session string must fail the gate');
        self::assertStringContainsString('session.txt', $r['all']);
        self::assertStringContainsString('(value withheld)', $r['all'], 'and the value is never printed');
        self::assertStringNotContainsString($secret, $r['all'], 'not even the url-safe value may be printed');
        self::assertStringNotContainsString(substr($secret, 0, 64), $r['all']);
    }

    public function test_a_url_safe_base64_session_split_into_chunks_is_a_fragmented_finding(): void
    {
        // The coverage that must NOT be traded away for the fix. A guard that
        // only catches the contiguous shape traded one hole for another: with
        // `-`/`_` still hard breaks inside the fragmentation pass, each chunk of
        // a split url-safe session shatters below the per-chunk floor and the
        // 8 x 150 split evades detection.
        $repo = $this->committed();
        $secret = self::urlSafeSession();
        $chunks = str_split($secret, 150);
        self::assertGreaterThan(1, count($chunks));
        $repo->write('split.txt', "session = \n  \"".implode("\"\n  \"", $chunks)."\"\n");

        $r = $repo->scan();

        self::assertSame(1, $r['code'], 'a split url-safe session must not pass');
        self::assertStringContainsString('fragmented run', $r['all']);
        self::assertStringNotContainsString($secret, $r['all']);
    }

    public function test_a_url_safe_session_in_history_is_found_and_its_value_withheld(): void
    {
        // Same credential class, reached the way the SEC-1 shape reaches: once
        // committed, then redacted in the working tree.
        $repo = $this->sandbox();
        $secret = self::urlSafeSession();
        $repo->write('dump.sql', "INSERT INTO sessions (session) VALUES ('{$secret}');\n");
        $repo->commit('the mistake');
        $repo->write('dump.sql', "INSERT INTO sessions (session) VALUES ('[REDACTED]');\n");
        $repo->commit('redact it');

        self::assertSame(0, $repo->scan(['--history=0'])['code'], 'the working tree really is clean now');

        $r = $repo->scan();

        self::assertSame(1, $r['code'], 'a url-safe session is still reachable in history');
        self::assertStringContainsString('history@dump.sql', $r['all']);
        self::assertStringNotContainsString($secret, $r['all']);
    }

    public function test_widening_the_alphabet_does_not_make_snake_case_identifiers_a_finding(): void
    {
        // THE measured false positive of the widening, pinned as a regression.
        // This is not hypothetical: adding `-`/`_` to ALPHABET with no shape
        // test made this exact shape red on this repo. The mechanism, recorded
        // because it is what the shape test in bin/scan-credentials.php exists
        // for: before the widening every `_` was a chunk boundary, so the
        // identifier chunks were short, numerous, and pushed the group over
        // FRAG_MAX_CHUNKS. With `_` a run character they merge into ONE chunk —
        // and a snake_case test name carrying digits is then a single solid,
        // digit-bearing chunk. Two lowercase-hex sibling constants were solid
        // under the old alphabet too. 64 + 64 + 75 = 203, three over --min=200,
        // which turned tests/Backup/PrunePaginationTest.php red.
        //
        // Every line below is a real shape from that file and this suite.
        $repo = $this->committed();
        $repo->write('names.php', "<?php\n\n"
            ."    private const KEEP_A = '".str_repeat('a', 60)."0001';\n"
            ."    private const KEEP_B = '".str_repeat('b', 60)."0002';\n"
            ."    public function test_prune_scans_all_250_messages_and_prunes_orphans_beyond_the_old_100_cap(): void {}\n"
            ."    public function test_the_walk_pages_history_100_100_50_then_an_empty_page(): void {}\n"
            ."    public function test_cold_copy_reclaim_of_sealed_chunks_with_id_250_under_a_passphrase(): void {}\n"
            ."\n");

        $r = $repo->scan();

        self::assertSame(0, $r['code'], 'identifiers are words; a guard that cries wolf is switched off within a day');
        self::assertStringNotContainsString('FAIL', $r['all']);
    }

    public function test_an_identifier_shaped_chunk_is_rejected_while_a_real_chunk_is_kept(): void
    {
        // The shape test is a DISCRIMINATOR, not a blanket relaxation: it must
        // still let a genuine url-safe chunk count toward a fragmented run. A
        // snake_case name containing digits is an identifier; 150 characters of
        // base64url are not, even though both are lowercase-ish and both hold
        // digits. If the shape test rejected both, the test above would pass for
        // the wrong reason and the fragmented pass would be blind.
        $repo = $this->committed();

        // Real url-safe payload, split 3 x 150 — only ~450 solid chars, so it
        // clears --min=200 on the credentials' own account.
        $repo->write('real.txt', implode("\"\n  \"", str_split(self::urlSafeSession(), 150))."\n");

        self::assertSame(1, $repo->scan()['code'], 'a real chunk must still count as encoded data');
    }
}
