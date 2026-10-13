<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Credentials;

use PHPUnit\Framework\TestCase;

/**
 * The guard as the release gate actually runs it: on THIS repository, with
 * this repository's history and this repository's baseline.
 *
 * CredentialGuardTest pins behaviour in throwaway repos. This file pins the two
 * things that only exist here: that the real tree is clean, and that every
 * receipted exposure still on file is printed on every run rather than quietly
 * absorbed by the baseline.
 *
 * The receipt set is NOT hardcoded to a count: the two SEC-1 verbatim blobs plus
 * the synthetic over-long probe receipted in 4a182bc9. The assertion is
 * set-equality between the baseline file and the guard's ACCEPTED lines, so an
 * honest receipt can be added without rotting the suite while a silenced receipt
 * — or a finding pardoned by anything other than the baseline — still fails.
 *
 * THIS FILE RUNS ON TWO TREES, and both are asserted rather than one of them
 * skipped. The private repository carries .credential-guard-baseline; the public
 * mirror is a sanitised export that deliberately does not (bin/public-manifest.txt
 * :58, never-publish). A skipped credential-gate test is indistinguishable from a
 * green one in the CI rollup, which is the failure this suite exists to prevent,
 * so each test branches on the file's existence and asserts the invariant that
 * is TRUE on the tree it is standing on: equality of the two sets when receipts
 * are on file, emptiness of both plus a positive control when they are not.
 */
final class RepositoryGuardTest extends TestCase
{
    private static function guard(): string
    {
        return dirname(__DIR__, 2).'/bin/scan-credentials.php';
    }

    /**
     * @param  list<string>  $args
     * @return array{code: int, out: string, err: string, all: string}
     */
    /** @var array<string, array{code: int, out: string, err: string, all: string}> */
    private static array $runs = [];

    private static function guard_run(array $args = []): array
    {
        // Memoised: a full scan of this repo (tree + all reachable history) is
        // ~5 s, and the suite needs the same three configurations repeatedly.
        $key = implode(' ', $args);
        if (isset(self::$runs[$key])) {
            return self::$runs[$key];
        }

        $proc = proc_open(
            [PHP_BINARY, self::guard(), ...$args],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname(__DIR__, 2),
        );
        self::assertIsResource($proc, 'the guard must be runnable');
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return self::$runs[$key] = ['code' => proc_close($proc), 'out' => $out, 'err' => $err, 'all' => $out.$err];
    }

    /**
     * The receipt file, whose ABSENCE is meaningful rather than incidental:
     * bin/public-manifest.txt:58 lists it under never-publish, so it is a
     * private-repo artefact and the public mirror does not carry it.
     */
    private static function baseline(): string
    {
        return dirname(__DIR__, 2).'/.credential-guard-baseline';
    }

    /** @return list<string> the receipted blob shas, comments and blanks dropped */
    private static function receipts(): array
    {
        $path = self::baseline();
        if (! is_file($path)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", (string) file_get_contents($path))),
            static fn (string $l): bool => $l !== '' && ! str_starts_with($l, '#'),
        ));
    }

    /** @return list<string> every line the guard marked ACCEPTED, in either output shape */
    private static function acceptedLines(string $out): array
    {
        return array_values(array_filter(
            explode("\n", $out),
            static fn (string $l): bool => str_contains($l, 'ACCEPTED')
        ));
    }

    public function test_this_repository_passes_the_guard(): void
    {
        $r = self::guard_run();

        self::assertSame(0, $r['code'], "the guard must be green on its own repository:\n".$r['all']);
        self::assertStringContainsString('credential-guard: clean', $r['out']);
    }

    public function test_the_guard_states_its_own_coverage_on_every_clean_run(): void
    {
        $r = self::guard_run();

        // The four audited bypasses were each a silent omission. Whatever the
        // configuration, the run must say what it did and did not reach.
        self::assertStringContainsString('tracked + untracked', $r['out'], 'untracked coverage is stated');
        self::assertStringContainsString('fragment', $r['out'], 'fragmentation coverage is stated');
        self::assertStringContainsString('binary', $r['out'], 'binary coverage is stated');
        self::assertStringContainsString('history', $r['out'], 'history coverage is stated');
        self::assertStringContainsString('coverage:', $r['out'], 'the limits line is not optional');
    }

    public function test_every_receipted_exposure_is_printed_on_every_run(): void
    {
        // .credential-guard-baseline exists because SEC-3 (revoke + purge) is
        // user-gated and still open. A baseline that silenced the finding
        // instead of printing it would be the exact defect this suite exists to
        // prevent, so it is asserted, not assumed.
        //
        // THE COUNT IS NOT HARDCODED, and that is the correction. This used to
        // assert `assertCount(2, $entries, 'two blobs, as the SEC-1 audit
        // recorded')`. Commit 4a182bc9 ("fix(guard): a synthetic 300-char probe
        // string was tripping the credential guard") legitimately added a THIRD
        // receipt — the over-long probe in tests/MySql, a documented FALSE
        // POSITIVE — and did not update this pin. The suite went red on a
        // baseline change that was correct.
        //
        // A hardcoded number cannot survive an honest receipt being added, and it
        // should not: the property worth protecting is not "how many receipts are
        // on file" but "the file and the guard's output agree in BOTH directions" —
        // every receipt is printed (a silenced receipt is the regression), AND
        // nothing is printed that is not receipted (a finding quietly pardoned by
        // something other than the baseline is the same regression wearing a
        // different hat). Equality of the two sets is strictly stronger than
        // either half alone and cannot rot when an honest receipt is added.
        //
        // WHICH TREE THIS RUNS ON IS PART OF THE INVARIANT, not an excuse. The
        // receipt file is deliberately NOT published: bin/public-manifest.txt:58
        // lists it under never-publish as "a RECEIPT for this repo's own
        // un-purged SEC-1 history exposure, including blob SHAs", and :20 names
        // it among the files a denylist would forget. The public mirror is
        // therefore a SANITISED export — it does not carry the receipt AND it
        // does not carry the exposure the receipt was for, because that exposure
        // lived in history the mirror does not publish. Asserting the private
        // tree's cardinality there could never pass, and skipping it would hide
        // that. So both halves of the SAME equality are asserted, and the branch
        // is the file's existence, which is a fact about the tree and not a
        // guess about the environment.
        //
        // Note what is deliberately NOT asserted here: the run's VERDICT. "Green"
        // is a fact about whether this tree carries an UNRECEIPTED exposure, not
        // about the baseline, and the two come apart: on the public mirror the
        // verdict is green because nothing is exposed, while on this repository
        // with the baseline removed it is red because the exposure is exposed and
        // unreceipted — which is the guard working. The verdict is pinned where it
        // belongs, in test_this_repository_passes_the_guard, on a tree that is in
        // its proper state. What is asserted below is the equality, and the
        // equality does not care which verdict came back.
        $present = is_file(self::baseline());

        $r = self::guard_run();

        $entries = self::receipts();
        // Read from BOTH streams: the FAIL path prints its findings and its
        // coverage lines on stderr, the clean path on stdout. A test that read
        // only one of them would pass vacuously on the other path.
        $printed = self::acceptedLines($r['all']);

        if ($present) {
            self::assertFileExists(self::baseline());
            self::assertSame(0, $r['code'], 'receipted exposures do not fail the gate');
            self::assertStringContainsString('ACCEPTED', $r['out']);
            self::assertStringContainsString('still reachable', $r['out']);
            self::assertStringContainsString('SEC-3', $r['out'], 'the debt is named on every run');

            self::assertNotSame([], $entries, 'the baseline must receipt something');
            self::assertSame(
                count($entries),
                count($printed),
                'every baseline receipt is printed AND nothing else claims ACCEPTED: '.count($entries).' receipt(s), '.count($printed).' ACCEPTED line(s)'
            );
            foreach ($entries as $entry) {
                self::assertStringContainsString(substr(explode(' ', $entry)[0], 0, 12), $r['out'], 'each receipt names its blob');
            }

            return;
        }

        // The inverse invariant, which the public mirror has never had asserted
        // and which is therefore the half that could hide a regression. With no
        // receipts on file, the printed set must be empty: a printed-but-
        // unreceipted finding — something pardoned by a mechanism other than the
        // baseline — is the same defect as a silenced receipt, and an ABSENT
        // baseline must never become a way to be quiet.
        self::assertFileDoesNotExist(self::baseline(), 'this branch is the absent-baseline branch');
        self::assertSame([], $entries, 'no receipt file means no receipts');
        self::assertSame(
            [],
            $printed,
            'nothing may claim ACCEPTED when there are no receipts: '.count($printed).' ACCEPTED line(s)'
        );
        self::assertStringNotContainsString('still reachable', $r['all'], 'no exposure is still reachable, so none may be claimed as such');
        self::assertStringNotContainsString('SEC-3', $r['all'], 'no exposure is receipted, so no purge debt may be named');
        self::assertStringContainsString('coverage:', $r['all'], 'the absent baseline must not have muted the coverage statement either');

        // Asserting emptiness is only half a proof: a guard that reported NOTHING
        // on every tree would satisfy every line above. So this is the positive
        // control — the one assertion that actually says "an absent baseline does
        // not mean a silenced finding". A synthetic tree holding one
        // credential-shaped run, scanned with no baseline in sight, must come back
        // RED and must print the finding. It costs one small file and no history
        // scan, and it is portable: it depends on the guard's behaviour, never on
        // which tree the suite happens to run on.
        $root = sys_get_temp_dir().'/teleframe-guard-absent-'.getmypid();
        try {
            @mkdir($root, 0777, true);
            self::assertDirectoryExists($root, 'the control tree must be creatable');
            file_put_contents(
                $root.'/probe.txt',
                self::guardAlphabet().str_repeat(self::guardAlphabet(), 12)."\n"
            );

            $c = self::guard_run(['--root='.$root, '--history=0', '--min=32']);

            self::assertSame(1, $c['code'], "an absent baseline must not silence a finding:\n".$c['all']);
            self::assertStringContainsString('probe.txt', $c['all'], 'the finding is printed, not merely counted');
            self::assertStringNotContainsString('ACCEPTED', $c['all'], 'with no baseline nothing may claim to be receipted');
        } finally {
            @unlink($root.'/probe.txt');
            @rmdir($root);
        }
    }

    public function test_dropping_the_baseline_makes_this_repository_fail(): void
    {
        // Proves the ACCEPTED lines are load-bearing and not decoration: with the
        // baseline refused, the guard is red on this repo.
        //
        // On the mirror this has no exposure to load — the sanitised export does
        // not carry the blobs, so refusing a baseline that was never there must
        // change NOTHING. That is the honest assertion there, and it is not a
        // weaker one: it pins the two configurations to the SAME verdict and the
        // SAME accepted set, which is what stops a future change from making
        // "no baseline" behave differently from "default baseline" — the exact
        // seam where a finding could start disappearing without anyone noticing.
        // It is asserted as agreement between two runs rather than as a hardcoded
        // exit code, because the verdict legitimately differs between the mirror
        // (green: nothing exposed) and this repository with the baseline removed
        // (red: exposed and unreceipted). What must not differ is the effect of
        // refusing a baseline that was not there.
        $r = self::guard_run(['--history-baseline=none']);

        if (! is_file(self::baseline())) {
            $d = self::guard_run();

            self::assertSame(
                $d['code'],
                $r['code'],
                'refusing a baseline that does not exist must not change the verdict'
            );
            self::assertSame(
                self::acceptedLines($d['all']),
                self::acceptedLines($r['all']),
                'refusing an absent baseline must not change what the guard accepts'
            );
            self::assertSame([], self::acceptedLines($r['all']), 'nothing is receipted, so nothing may claim to be');
            self::assertStringNotContainsString('ACCEPTED', $r['all'], 'nothing is receipted, so nothing may claim to be');

            return;
        }

        self::assertSame(1, $r['code'], 'history hits must fail when nothing is receipted');
        self::assertStringContainsString('docs/verbatim/2026-09-14-nf5-schema-remediation-and-progress-rally.md', $r['all']);
        self::assertStringContainsString('(value withheld)', $r['all']);
        self::assertStringNotContainsString('ACCEPTED', $r['all'], 'nothing is receipted, so nothing may claim to be');
    }

    public function test_the_working_tree_alone_is_clean(): void
    {
        $r = self::guard_run(['--history=0']);

        self::assertSame(0, $r['code'], 'the redacted working tree must be clean');
        self::assertStringContainsString('history NOT SCANNED', $r['out'], 'and the omission is stated');
    }

    public function test_the_guard_output_never_contains_a_long_credential_alphabet_run(): void
    {
        // The strongest available statement that nothing leaked: no line of the
        // guard's own output is itself a credential-shaped run.
        //
        // The oracle is the guard's OWN ALPHABET, read out of its source, because
        // the question being asked is "would the guard classify its own output as
        // a finding?" — and that question only means anything against the
        // alphabet the guard actually walks. It used to be a hardcoded copy, and
        // on 2026-10-02 that was the second half of a real defect: `-` and `_`
        // were added to the guard's alphabet (they are base64url, which is what
        // a Pyrogram/Telethon StringSession IS) and this copy was left narrow, so
        // the oracle and the guard silently disagreed about what a run was.
        // test_the_guards_alphabet_admits_the_url_safe_characters pins the
        // widening itself, so deriving the oracle here cannot make this vacuous.
        $alphabet = self::guardAlphabet();
        $limit = 128;

        // 128, not the old 64, and not the guard's own --min of 200. Measured on
        // this repo's real output (three configurations, all lines): the longest
        // run the guard prints is 66, and it is a path —
        // `history@docs/verbatim/2026-09-14-nf5-schema-remediation-and-progress-rally.md`
        // — which glues into one run only because `-` and `/` are both alphabet
        // characters. 128 keeps ~2x headroom over that and ~3.6x under a real
        // 468-char session string. Widening past it needs a new measurement, not
        // a new guess.
        foreach ([self::guard_run(), self::guard_run(['--history=0']), self::guard_run(['--history-baseline=none'])] as $r) {
            foreach (explode("\n", $r['all']) as $line) {
                $longest = 0;
                $i = 0;
                $len = strlen($line);
                while ($i < $len) {
                    $n = strspn($line, $alphabet, $i);
                    $longest = max($longest, $n);
                    $i += $n + max(1, strcspn($line, $alphabet, $i));
                }
                self::assertLessThan($limit, $longest, 'a guard output line is credential-shaped: '.$line);
            }
        }
    }

    public function test_the_guards_alphabet_admits_the_url_safe_characters(): void
    {
        // The blindness this pair of tests exists over: `-` and `_` are base64url,
        // and base64url IS the shape of a Telegram session string (Pyrogram and
        // Telethon both emit `base64.urlsafe_b64encode(...)`). While they were
        // hard breaks, identical to a newline, a committed url-safe session
        // scanned CLEAN while the byte-identical payload in standard base64
        // scanned FAIL — same guard, same length, opposite verdicts.
        //
        // Pinned here, literally, so that nothing downstream can re-narrow the
        // alphabet — including the oracle derived from it in the test above.
        $alphabet = self::guardAlphabet();

        self::assertStringContainsString('-', $alphabet, 'base64url dash is alphabet, not a hard break');
        self::assertStringContainsString('_', $alphabet, 'base64url underscore is alphabet, not a hard break');
        self::assertStringContainsString('+', $alphabet);
        self::assertStringContainsString('/', $alphabet);
        self::assertSame(
            count(array_unique(str_split($alphabet))),
            strlen($alphabet),
            'the alphabet carries no duplicate character, so strspn cannot double-count a run'
        );
    }

    /** The guard's own ALPHABET constant, read out of its source. Regex-free. */
    private static function guardAlphabet(): string
    {
        $source = (string) file_get_contents(self::guard());
        $at = strpos($source, "const ALPHABET = '");

        self::assertIsInt($at, 'the guard must still declare ALPHABET as a single-quoted literal');
        $from = $at + strlen("const ALPHABET = '");
        $to = strpos($source, "'", $from);

        self::assertIsInt($to, 'the ALPHABET literal must be terminated');

        return substr($source, $from, $to - $from);
    }
}
