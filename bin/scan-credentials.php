#!/usr/bin/env php
<?php

declare(strict_types=1);

// Credential guard — "session strings are credentials" (AGENTS.md hard rule).
//
// Why this exists: the 2026-09-14 verbatim archive carried a REAL Telegram
// session string twice, inside the `session` column of a pasted SQLSTATE
// INSERT error. It reached git (and the remote) before anyone looked. The
// 2026-09-24 user directive is explicit — "no real telegram session should not
// get into client" — and a rule nobody can run is not a rule.
//
// WHAT IT SCANS (2026-09-26 rewrite; the four bypasses an audit found):
//   1. THE WORKING TREE, TRACKED **AND** UNTRACKED. The file set is
//      `git ls-files --cached --others --exclude-standard`, so an untracked,
//      un-staged file is scanned: the pre-commit window — the exact moment a
//      warning is worth most — used to be invisible. Outside a checkout it
//      falls back to a recursive walk with the same exclusions.
//   2. BASE64-SHAPED RUNS >= --min chars (default 200), CONTIGUOUS *OR*
//      FRAGMENTED. A fragmented run is one whose chunks are separated only by
//      "transparent" characters (whitespace, quotes, brackets, `=`, …); an
//      8 x 150-char split no longer passes. A qualifying fragment must still
//      contain a contiguous chunk of >= max(32, min/2) chars, so a file of
//      many short tokens cannot accumulate into a false positive.
//      `-` AND `_` ARE ALPHABET CHARACTERS (2026-10-02). They were not, and
//      that made a whole class of credential structurally invisible: Pyrogram's
//      and Telethon's `StringSession` both emit `base64.urlsafe_b64encode(...)`,
//      so `-`/`_` are not decoration on a session string, they ARE the session
//      string. With them absent they were hard breaks identical to a newline,
//      and a committed 468-char url-safe blob of 351 random bytes scanned
//      CLEAN while the byte-identical payload in standard base64 scanned FAIL —
//      the same guard, the same length, opposite verdicts, on exactly the
//      credential shape this script exists to catch.
//      Both encodings are RANDOM bytes, so `-` and `_` occur in url-safe base64
//      at rate 2/64 per character, the substitutions for `+` and `/`. Over the
//      468 characters of a Telegram session string that is ~14.6 occurrences:
//      the chance a real one contains none is (62/64)^468 ~ 4.5e-7, so the
//      widened alphabet costs no real coverage.
//      Widening ALPHABET is not by itself sufficient and was not safe alone —
//      see IDENTIFIER and $encoded below for the shape test it forced.
//   3. BINARY FILES ARE SCANNED, up to --binary-prefix bytes (default 1 MiB).
//      They are no longer skipped wholesale, and a binary tail past the
//      prefix is counted and reported rather than quietly ignored.
//   4. GIT HISTORY, UNBOUNDED by default (--history=N to bound it, --history=0
//      to switch it off), reachable from EVERY ref (--history-refs=HEAD to
//      narrow to the current branch). This is the SEC-1 shape: a secret
//      committed once on some branch and only later redacted in the working
//      tree. Both surviving blobs of the 2026-09-14 exposure sit on
//      cycle-9/cycle-10 refs, NOT on main's ancestry — a HEAD-only reach, or a
//      fixed recent window, would miss exactly the case the guard exists for.
//      Unbounded costs 0.6 s more than the last 200 commits per ref on this
//      repo, so completeness is the default. With --history=0 the clean line
//      says history is NOT scanned, out loud.
//   5. THE OBJECT SET ITSELF IS CHECKED FOR COMPLETENESS (2026-09-27).
//      Unbounded reach is a coverage claim, and a claim is only true if the
//      repository actually contains the history it reaches for. Pipeline
//      #206's credential-guard job was GREEN on a `--depth 20` clone while a
//      receipted session sat 84 commits back in the real history, and this
//      guard printed "every commit reachable from all" over that truncated
//      object set — the guard manufacturing the silent omission it exists to
//      prevent. So: a shallow object set is DETECTED (git's own
//      --is-shallow-repository, cross-checked against the .git/shallow
//      marker), the run states how many commits were actually scanned, and
//      the run exits 3, INCOMPLETE. Completeness can also come back UNKNOWN,
//      when git cannot be asked and no marker can be read — UNKNOWN is
//      treated exactly like truncated, because it is. There is no flag to
//      switch this off: it is the guard's ability to tell a pass from a
//      partial look.
//   6. AND A PARTIAL (PROMISOR) OBJECT SET IS CHECKED THE SAME WAY (2026-09-27).
//      A shallow clone is not the only way to be missing objects. A
//      `git clone --filter=blob:none` (or tree:0, or any promisor clone) has
//      EVERY COMMIT and only fetches blobs on demand, so
//      `rev-parse --is-shallow-repository` answers "false" and the check
//      above passes it. It is a harder case than shallow, because the
//      enumeration still SUCCEEDS: `git rev-list --objects` returns a full
//      list, the missing entries are silently omitted by the lazy fetcher,
//      and the guard would read what it could and conclude there was nothing
//      there. A missing object reported as an absence of a finding is the
//      exact failure the guard exists to prevent. So the object set is
//      MEASURED, not inferred: `rev-list --objects --missing=print` is run
//      with GIT_NO_LAZY_FETCH=1 and every `?`-prefixed object it reports is
//      an object this clone does not have. Any non-zero count is TRUNCATED,
//      and the coverage lines carry the number that was measured — not a
//      reassurance. `extensions.partialClone` and `remote.*.promisor` are
//      read too, because a promisor clone can be made complete again and the
//      operator needs to know which of the two they are looking at.
//
// WHAT IT STILL DOES NOT COVER — every one of these is printed in the summary
// line, so a "clean" result is never read as more than it is:
//   * .gitignore'd paths (vendor/, tgweb/, .opencode/, .env, composer.lock)
//     unless --include-ignored;
//   * bytes past --binary-prefix in a binary file;
//   * bytes past --max-bytes in any file;
//   * anything past --max-total-bytes — a capped scan exits 3, INCOMPLETE,
//     never 0;
//   * history older than --history commits;
//   * commits before a SHALLOW clone's boundary — they were never fetched, so
//     the count is a lower bound, printed with the boundary sha, exit 3;
//   * objects a PARTIAL/promisor clone does not hold — the count is printed
//     with the number measured, and it is a lower bound on the unseen, exit 3;
//   * dangling objects reachable from no ref.
//
//   HISTORY BASELINE. A history finding whose blob SHA is listed in
//   .credential-guard-baseline (repo root) is reported as ACCEPTED and does not
//   fail the gate. That file exists because this repo's OWN history still holds
//   the 2026-09-14 exposure: purging it is SEC-3 and is user-gated, so a
//   default-on history scan would otherwise be red forever and get disabled.
//   A baseline is a receipt, not a pardon — every accepted hit is printed on
//   every run, and deleting the line makes the gate red again.
//
// WHAT IT NEVER DOES: print a matched value. A finding reports path, line,
// length, shape and a short hash of the *finding identity* — never the matched
// characters — so a redacted CI log cannot leak the secret it just caught. The
// same rule covers the coverage lines: they carry counts, shas and paths, and
// no scanned bytes.
//
// EXIT CODES. 0 = clean · 1 = findings · 2 = usage error · 3 = scan INCOMPLETE.
// "Incomplete" is not a soft pass: a byte cap, a shallow (truncated) object
// set, a PARTIAL/promisor object set, or an object set whose completeness
// could not be determined all exit 3, never 0, because none of them can rule
// out a credential outside what was reached. A FINDING still exits 1 even on
// an incomplete run — a caller is never handed "incomplete" in place of "there
// is a problem", and a truncated clone with a real hit is a real hit, not a
// partial one.
//
// Usage: php bin/scan-credentials.php [options]
//   --min=N              minimum credential run length (default 200, >= 32)
//   --root=DIR           tree to scan (default: repo root)
//   --history=N|all      commits of history to scan; 0 = off (default: all)
//   --history-refs=REV   refs the history scan reaches (default "all")
//   --history-baseline=F accepted-history file (default: <root>/.credential-guard-baseline;
//                        "none" to accept nothing, so any history hit fails)
//   --no-fragment        disable the fragmented-run pass (faster, weaker)
//   --binary-prefix=N    bytes of a binary file to scan (default 1048576)
//   --max-bytes=N        per-file byte cap (default 8388608)
//   --max-total-bytes=N  whole-scan byte cap (default 536870912)
//   --include-ignored    also walk .gitignore'd paths (slow, very noisy)
//   --quiet              suppress the clean/summary lines only
//   exit 0 = clean · 1 = findings · 2 = usage error · 3 = scan incomplete

// `-` and `_` are the url-safe substitutions for base64's `+` and `/`, so they
// belong to the alphabet for the same reason those two do: Pyrogram's and
// Telethon's `StringSession` both emit `base64.urlsafe_b64encode(...)`, which
// is what a Telegram session string ACTUALLY is. Excluding them made `-`/`_`
// hard breaks identical to a newline, and a committed url-safe session string
// therefore scanned clean while the identical payload in standard base64
// failed — see the header, section 2, for the measured proof.
const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/-_';

/**
 * "Transparent" characters: a fragment boundary that does not break a run for
 * the fragmentation pass — whitespace, quoting, bracketing, separators and
 * base64 padding. Everything else (letters outside the alphabet, control
 * bytes) is a hard break.
 */
const TRANSPARENT = " \t\n\r\v\f\"'`\\,;:()[]{}<>|="
    ."\x00\x01\x02\x03\x04\x05\x06\x07\x0b\x0e\x0f"
    ."\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f\x7f";

/**
 * The shape of an IDENTIFIER, not of encoded data: lowercase letters, digits,
 * `_` and `-`, and nothing else.
 *
 * This exists because widening ALPHABET with `-`/`_` had a measured, real false
 * positive, and the mechanism is worth stating exactly. Before the widening,
 * every `_` inside a snake_case name was a chunk boundary, so a file's
 * identifier chunks were short, numerous, and pushed over FRAG_MAX_CHUNKS.
 * With `_` now a run character they merge into ONE chunk — and
 * `test_prune_scans_all_250_messages_and_prunes_orphans_beyond_the_old_100_cap`
 * (75 chars, 22 distinct, digits from `250` and `100`) became a 75-char SOLID
 * chunk. Two sibling 64-char lowercase-hex constants (distinct: 3) were solid
 * under the old alphabet too. 64 + 64 + 75 = 203: three chars over --min=200,
 * which made tests/Backup/PrunePaginationTest.php red. The ceiling that used to
 * reject it had been defeated by the alphabet change itself, because the
 * alphabet change is what collapsed the group from 27 chunks to 18.
 *
 * A statistical discriminator is NOT available here and was rejected on
 * purpose: distinct-character count and Shannon entropy both separate these
 * shapes cleanly, but they also reject `FakeTelegramSession1` repeated — the
 * fixture every fragmented-run regression pin in this suite is built from, and
 * a shape no real credential has. Rewriting those pins to satisfy an entropy
 * gate would replace a measured rule with an unmeasured one.
 *
 * The structural rule costs nothing real. A base64 chunk of >= 50 chars built
 * from random bytes contains a character outside lowercase+digits+`-`+`_` with
 * probability 1 - (37/64)^50 ~ 1 - 3e-11: an uppercase letter, a `+` or a `/`
 * appear in any real chunk. So this rejects identifiers and keeps credentials.
 */
const IDENTIFIER = 'abcdefghijklmnopqrstuvwxyz0123456789_-';

/**
 * A fragmented run may be at most this many chunks. A split credential is a
 * handful of pieces; a generated PHP file's use-import block glues 66 of them
 * (class names carrying an embedded crc32, e.g.
 * TlMessagesSearchResultsCalendarSearchReB1534ec0e56fChats) and that must not
 * read as one. Measured on this repo: the real 2026-09-14 exposure is 12
 * chunks, an 8 x 150 split is 8, the generated-model false positive is 66.
 *
 * This ceiling was calibrated against the OLD alphabet, and widening that
 * alphabet (2026-10-02, `-` and `_`) changed the piece-count scale: the same
 * identifier list that read as 27 chunks now reads as 18, so it passed under
 * the ceiling instead of being rejected by it. That is why the ceiling alone
 * no longer carries the false-positive load, and why IDENTIFIER exists.
 */
const FRAG_MAX_CHUNKS = 24;

/** Directory names never walked, at any depth. */
const SKIP_DIRS = ['.git', 'vendor', 'node_modules', '.opencode', '.ruff_cache', '.phpunit.result.cache'];

$root = dirname(__DIR__);
$min = 200;
$history = -1; // unbounded: see the header; --history=N bounds it, =0 disables
$quiet = false;
$fragment = true;
$binaryPrefix = 1048576;
$maxBytes = 8388608;
// 512 MiB, sized on 2026-10-22 against the measured whole-history blob volume of
// this repository's full private history (284.6 MiB — the credential-guard CI job
// hit the previous 256 MiB cap during the history scan there and exited 3
// INCOMPLETE over a 1180-commit history). The cap is a runaway guard, not a
// scan budget: raising it restores a COMPLETE scan, and it is still far below
// any heap or disk limit this script can reach. Tests that need a smaller cap
// pass --max-total-bytes explicitly (tests/Credentials/CredentialGuardTest.php).
$maxTotalBytes = 536870912;
$includeIgnored = false;
$historyRefs = 'all';
$baselineFile = null;

$usage = static function (string $message): void {
    fwrite(STDERR, "scan-credentials: {$message}\n");
    fwrite(STDERR, "usage: php bin/scan-credentials.php [--min=N] [--root=DIR] [--history=N|all] [--no-fragment]\n");
    fwrite(STDERR, "         [--history-refs=all|HEAD] [--history-baseline=F|none] [--binary-prefix=N]\n");
    fwrite(STDERR, "         [--max-bytes=N]\n");
    fwrite(STDERR, "         [--max-total-bytes=N] [--include-ignored] [--quiet]\n");
    exit(2);
};

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--min=')) {
        $min = (int) substr($arg, 6);
        if ($min < 32) {
            $usage('--min must be >= 32');
        }

        continue;
    }
    if (str_starts_with($arg, '--root=')) {
        $root = rtrim(substr($arg, 7), '/');
        if ($root === '') {
            $usage('--root must not be empty');
        }

        continue;
    }
    if (str_starts_with($arg, '--history-baseline=')) {
        $baselineFile = substr($arg, 19);
        if ($baselineFile === 'none') {
            $baselineFile = '';

            continue;
        }
        if ($baselineFile === '') {
            $usage('--history-baseline must be a path or "none"');
        }

        continue;
    }
    if (str_starts_with($arg, '--history-refs=')) {
        $historyRefs = substr($arg, 15);
        if ($historyRefs === '') {
            $usage('--history-refs must not be empty ("all" or a rev, e.g. HEAD)');
        }

        continue;
    }
    if (str_starts_with($arg, '--history=')) {
        $raw = substr($arg, 10);
        if ($raw === 'all') {
            $history = -1;

            continue;
        }
        $history = (int) $raw;
        if ($history < 0) {
            $usage('--history must be >= 0 or "all"');
        }

        continue;
    }
    if (str_starts_with($arg, '--binary-prefix=')) {
        $binaryPrefix = (int) substr($arg, 17);
        if ($binaryPrefix < 1) {
            $usage('--binary-prefix must be >= 1');
        }

        continue;
    }
    if (str_starts_with($arg, '--max-bytes=')) {
        $maxBytes = (int) substr($arg, 13);
        if ($maxBytes < 1) {
            $usage('--max-bytes must be >= 1');
        }

        continue;
    }
    if (str_starts_with($arg, '--max-total-bytes=')) {
        $maxTotalBytes = (int) substr($arg, 19);
        if ($maxTotalBytes < 1) {
            $usage('--max-total-bytes must be >= 1');
        }

        continue;
    }
    if ($arg === '--include-ignored') {
        $includeIgnored = true;

        continue;
    }
    if ($arg === '--no-fragment') {
        $fragment = false;

        continue;
    }
    if ($arg === '--quiet') {
        $quiet = true;

        continue;
    }
    $usage("unknown option: {$arg}");
}

if (! is_dir($root)) {
    $usage("root not a directory: {$root}");
}

/**
 * Run a git subcommand, returning stdout ('' on any failure).
 *
 * $env is merged over the inherited environment, and exists for one reason:
 * GIT_NO_LAZY_FETCH=1. On a promisor/partial clone git will otherwise FETCH a
 * missing object from the network the moment anything reads it — so a scan
 * would silently repair the very object set it is trying to measure, and the
 * guard's coverage would depend on whether the network happened to be up. The
 * measurement in section 1a therefore runs with lazy fetching switched off,
 * which makes "this object is absent" a fact about the OBJECT SET instead of a
 * fact about connectivity.
 *
 * @param  array<string, string>  $env
 */
$git = static function (string $root, array $parts, array $env = []): string {
    $child = $env === [] ? null : array_merge(getenv(), $env);
    $proc = proc_open(['git', '-C', $root, ...$parts], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $child);
    if (! is_resource($proc)) {
        return '';
    }
    $raw = stream_get_contents($pipes[1]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    return $raw;
};

/** Exactly $n bytes from a stream, or fewer at EOF. */
$read = static function ($handle, int $n): string {
    $buf = '';
    while (strlen($buf) < $n) {
        $chunk = fread($handle, $n - strlen($buf));
        if ($chunk === false || $chunk === '') {
            break;
        }
        $buf .= $chunk;
    }

    return $buf;
};

// ---------------------------------------------------------------------------
// 1. Enumeration — TRACKED + UNTRACKED-NOT-IGNORED.
// ---------------------------------------------------------------------------

// A linked worktree's root carries `.git` as a POINTER FILE ("gitdir: <path>",
// one line), not as a directory. `is_dir` alone treated that shape as “no
// checkout”, which silently dropped the entire history axis for every scan
// whose --root was a linked worktree: measured 2026-10-22, the web-proxy
// worktree of this repository scanned green (exit 0) with “history NOT
// SCANNED (no git checkout at --root)” while the same repository's main
// worktree scan found the history findings — a two-column gate that reports
// one column empty exactly when the second is the more important one, with a
// zero exit sealing it. A pointer file counts as a checkout only when it is
// the well-formed pointer; anything else stays out-of-scope and keeps saying
// so.
$gitEntry = $root.'/.git';
$isCheckout = is_dir($gitEntry);
$gitDirPointer = '';
if (! $isCheckout && is_file($gitEntry)) {
    $gitDirPointer = (string) file_get_contents($gitEntry);
    if (str_starts_with($gitDirPointer, 'gitdir: ')) {
        $isCheckout = true;
    }
}
$trackedFiles = [];
$files = [];
$mode = 'recursive walk (no git checkout)';

if ($isCheckout) {
    $mode = 'git working tree (tracked + untracked)';
    $split = static function (string $raw): array {
        $out = [];
        foreach (explode("\0", $raw) as $path) {
            if ($path !== '') {
                $out[] = $path;
            }
        }

        return $out;
    };
    $trackedFiles = $split($git($root, ['ls-files', '-z', '--cached']));
    $others = $includeIgnored
        ? $split($git($root, ['ls-files', '-z', '--others']))
        : $split($git($root, ['ls-files', '-z', '--others', '--exclude-standard']));
    $files = array_values(array_unique([...$trackedFiles, ...$others]));
    sort($files);
    sort($trackedFiles);
} else {
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static fn ($cur): bool => ! ($cur->isDir() && in_array($cur->getFilename(), SKIP_DIRS, true))
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );
    /** @var SplFileInfo $file */
    foreach ($it as $file) {
        if (! $file->isFile() || ! $file->isReadable()) {
            continue;
        }
        $files[] = substr($file->getPathname(), strlen($root) + 1);
    }
    sort($files);
    $trackedFiles = $files;
}

$trackedLookup = array_flip($trackedFiles);

// ---------------------------------------------------------------------------
// 1a. TRUNCATION — is this object set the WHOLE history, or a slice of it?
//
// Unbounded history reach is a COVERAGE CLAIM, and a claim is only true when
// the repository holds the history it is reached over. A `--depth N` clone is
// a documented, entirely reasonable CI configuration and it is not a
// repository: the commits before the shallow boundary were never fetched.
// Pipeline #206 ran exactly that, and this guard reported "every commit
// reachable from all" over it. Three states, and only three:
//
//   complete — the .git/shallow marker is absent, which is what a full clone
//              looks like. Unbounded reach is then a true claim.
//   shallow  — the marker exists and is non-empty (git's own
//              --is-shallow-repository is consulted first and the marker
//              cross-checks it). History reach is a LOWER BOUND: the run
//              names the count it actually scanned and the boundary it stops
//              at, and exits 3, INCOMPLETE.
//   unknown  — git could not be asked AND no marker could be read. Silence
//              would be a claim. So the run says UNKNOWN and exits 3, the
//              same as truncated, because that is what it is.
//
// The fourth state — "assume complete, it probably is" — is the incident, and
// is deliberately not implemented. Detection is not suppressible by a flag: a
// gate that can be told to stop asking is not a gate.
$truncation = ['state' => 'n/a', 'boundary' => 0, 'oldest' => ''];

// ---------------------------------------------------------------------------
// 1a-ii. PARTIAL (PROMISOR) OBJECTS — the truncation a shallow check cannot see.
//
// A `--filter=blob:none` clone is NOT shallow. `rev-parse
// --is-shallow-repository` answers "false" over it, so the check above passes
// it, and it is a WORSE case than shallow rather than a milder one. On a
// shallow clone `git rev-list --objects` fails loudly (exit 128) when an
// object is absent. On a partial clone the enumeration SUCCEEDS: the missing
// entries are quietly left out by the lazy fetcher, and a read of one of them
// is not an error either — with a reachable promisor remote git silently
// FETCHES it, and with an unreachable one it prints "missing" and the guard's
// loop moves on. Both outcomes are reported as the same thing: nothing found.
// That is the dangerous shape named in the brief: a missing object reported as
// an ABSENCE OF A FINDING.
//
// So the object set is MEASURED rather than inferred from config. The one
// command that answers the question without changing the answer is
// `rev-list --objects --missing=print` under GIT_NO_LAZY_FETCH=1: it lists
// every reachable object and prefixes the absent ones with '?'. The count of
// '?' lines is therefore a real, checkable measurement of how much of the
// history this clone does not hold.
//
//   absent  = 0 → the objects the scan reached are the objects that exist.
//              A promisor CONFIG alone is still disclosed (a partial clone
//              whose blobs happen to be fetched looks identical to a full one
//              today, and the next `git gc` or a network failure can take the
//              objects away again), but it is not a truncation finding.
//   absent > 0 → TRUNCATED, the same verdict and the same exit 3 a shallow
//              clone gets, with the measured number in the coverage line.
//
// A promisor remote whose objects are all present is NOT over-triggered: the
// verdict keys on the measurement, not on the config. The config is read too,
// because the operator needs to know WHY a clone is partial in order to fix
// it (`git fetch --refetch` or dropping the filter), and because it is the
// cross-check when the measurement itself cannot run on an old git.
$partialObjects = ['absent' => null, 'total' => null, 'promisor' => [], 'filter' => ''];

if ($isCheckout && $history !== 0) {
    // Which remotes are promisors, and with what filter. Read with --list
    // rather than a --get-regexp so a name with odd characters cannot break
    // the query, and so an old git that lacks the flag still yields the rest.
    foreach (explode("\n", $git($root, ['config', '--local', '--list'])) as $line) {
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $key = strtolower(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        if ($key === 'extensions.partialclone' && $value !== '') {
            $partialObjects['promisor'][$value] = true;
        } elseif (str_ends_with($key, '.promisor') && $value !== 'false') {
            // 'remote.origin.promisor' names the remote 'origin', not
            // 'remote.origin' — the section prefix is part of the key, not of
            // the name, and printing the whole key back at the operator would
            // send them looking for a remote called "remote.origin".
            $remote = substr($key, 0, -strlen('.promisor'));
            $partialObjects['promisor'][str_starts_with($remote, 'remote.') ? substr($remote, 7) : $remote] = true;
        } elseif (str_ends_with($key, '.partialclonefilter') && $value !== '') {
            $partialObjects['filter'] = $value;
        }
    }

    // The measurement. GIT_NO_LAZY_FETCH=1 is the whole point: without it git
    // repairs the object set while being measured and reports zero absent.
    // --missing=print landed in git 2.19 (2018); an older git rejects the flag
    // with a usage error and an empty stdout, which is indistinguishable from
    // an empty repository — so an empty result on a repository that HAS
    // commits is UNKNOWN, never "nothing is missing".
    $revScope = $historyRefs === 'all' ? ['--all'] : [$historyRefs];
    $enumerated = $git($root, ['rev-list', '--objects', '--missing=print', ...$revScope], ['GIT_NO_LAZY_FETCH' => '1']);
    $commitCount = trim($git($root, ['rev-list', '--count', ...$revScope]));
    $hasCommits = $commitCount !== '' && (int) $commitCount > 0;

    if ($enumerated === '' && $hasCommits) {
        $partialObjects['absent'] = null; // the measurement could not run
    } else {
        $absent = 0;
        $total = 0;
        foreach (explode("\n", $enumerated) as $line) {
            if ($line === '') {
                continue;
            }
            $total++;
            if (str_starts_with($line, '?')) {
                $absent++;
            }
        }
        $partialObjects['absent'] = $absent;
        $partialObjects['total'] = $total;
    }
}

if ($isCheckout && $history !== 0) {
    // git's own answer, when the installed git has it (2.15+, 2017).
    $verdict = trim($git($root, ['rev-parse', '--is-shallow-repository']));

    // The marker, resolved the way git resolves it: in a LINKED WORKTREE .git
    // is a file, not a directory, so <root>/.git/shallow is the wrong path
    // there and only --git-path knows the real one.
    $candidates = [];
    $markerResolved = false;
    $gitPath = trim($git($root, ['rev-parse', '--git-path', 'shallow']));
    if ($gitPath !== '') {
        $candidates[] = str_starts_with($gitPath, '/') ? $gitPath : $root.'/'.$gitPath;
        $markerResolved = true;
    }
    if (is_dir($root.'/.git')) {
        $candidates[] = $root.'/.git/shallow';
        $markerResolved = true;
    }
    $marker = null;
    foreach (array_unique($candidates) as $candidate) {
        if (is_file($candidate)) {
            $marker = $candidate;

            break;
        }
    }

    // A shallow marker is non-empty by construction (git writes one sha per
    // cut point). A marker that exists but names nothing is a degenerate state
    // git does not produce, so it is treated as evidence of NOTHING in either
    // direction — an anomaly is not a certificate of completeness.
    $shallow = $verdict === 'true' || ($marker !== null && filesize($marker) > 0);

    // Is there a git here at all? --git-dir predates --is-shallow-repository
    // by a decade, so it answers on a git too old to be asked the real
    // question — and when it answers, the marker's ABSENCE is authoritative
    // (a functional git that reports no cut points has no cut points).
    // Without a git, nothing is authoritative and the state is UNKNOWN.
    $gitWorks = trim($git($root, ['rev-parse', '--git-dir'])) !== '';

    $boundaryShas = [];
    if ($shallow && $marker !== null) {
        foreach (explode("\n", (string) @file_get_contents($marker)) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $boundaryShas[] = $line;
            }
        }
    }

    $truncation = [
        'state' => $shallow
            ? 'shallow'
            : ($verdict === 'false' || ($gitWorks && $marker === null && $markerResolved) ? 'complete' : 'unknown'),
        'boundary' => count($boundaryShas),
        'oldest' => $boundaryShas === [] ? '' : substr($boundaryShas[0], 0, 12),
    ];
}

// ---------------------------------------------------------------------------
// 1b. History baseline — blob SHAs whose history hit is a KNOWN, receipted
//     exposure rather than a new one. See the header for why this exists.
// ---------------------------------------------------------------------------

$baselinePath = $baselineFile ?? $root.'/.credential-guard-baseline';
$baselineLabel = str_starts_with($baselinePath, $root.'/') ? substr($baselinePath, strlen($root) + 1) : $baselinePath;
$baseline = [];
// $baselinePath is a string by construction here: the ?? above defaults it,
// --history-baseline=none sets it to ''. The emptiness test is the real one.
if ($baselinePath !== '' && is_file($baselinePath)) {
    foreach (explode("\n", (string) file_get_contents($baselinePath)) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $sp = strpos($line, ' ');
        $baseline[$sp === false ? $line : substr($line, 0, $sp)] = $sp === false ? '' : trim(substr($line, $sp + 1));
    }
}

// ---------------------------------------------------------------------------
// 2. The scan — one strspn/strcspn walk, no regex, matched text never retained.
// ---------------------------------------------------------------------------

/**
 * Longest contiguous credential run and longest qualifying fragmented run.
 *
 * A fragmented run is a group of credential chunks joined only by TRANSPARENT
 * characters. It qualifies when the SOLID chunks — those at least $floor long —
 * add up to $min on their own. The floor is what keeps prose out:
 * "getChannel/createChannel/uploadPart/sendDocument/…" joins across 210 chars
 * but not one chunk reaches 50, so nothing solid accumulates and it is not a
 * split credential. An 8 x 150 split contributes 1050 solid chars and is caught,
 * trailing 30-char remainder included. Requiring EVERY chunk to clear the floor
 * would instead reject the same split, because a real session does not divide
 * evenly. Known limit, stated rather than hidden: a run deliberately broken
 * into pieces all shorter than $floor (= max(16, min/4)) still passes — that is
 * the same shape as a run below a lowered --min, and the alternative is a gate
 * that fires on English.
 *
 * @return array{0: int, 1: int, 2: int, 3: int} [contiguous, its offset, fragmented, its offset]
 */
// A >= $floor chunk counts toward a fragmented run only if it contains a
// DIGIT and is not an IDENTIFIER (see the IDENTIFIER constant for the measured
// reason, and for the alternatives that were rejected on purpose).
//
// The digit test alone: without it, generated PHP class names —
// 'ChannelAdminLogEventActionChangeAvailableReactionsData', 51 chars of pure
// letters, dozens per file — accumulate past the threshold and the guard
// fails on src/Schema/Generated/. A base64 run of >= 50 characters contains no
// digit with probability (54/64)^50 ~ 1.4%, and a fragmented run needs several
// such chunks to add up, so the test costs almost no real coverage.
//
// The identifier test alone: needed only because ALPHABET now contains `-`
// and `_` (2026-10-02). Widening them merged snake_case identifiers into long
// digit-bearing chunks and turned one test file red at 203 solid chars.
// Identifier-shaped chunks are words; base64 of random bytes is not.
//
// The chunk is passed to strspn/strpbrk and dropped; it is never stored or
// printed.
$encoded = static function (string $chunk): bool {
    if (strpbrk($chunk, '0123456789') === false) {
        return false; // no digit: an identifier, not encoded data
    }

    return strspn($chunk, IDENTIFIER) !== strlen($chunk); // not purely identifier-shaped
};

$scan = static function (string $data, bool $fragment) use ($min, $encoded): array {
    $len = strlen($data);
    if ($len === 0) {
        return [0, 0, 0, 0];
    }
    $floor = max(16, intdiv($min, 4));
    $contiguous = 0;
    $contiguousAt = 0;
    $frag = 0;
    $fragAt = 0;
    $i = 0;
    while ($i < $len) {
        $run = strspn($data, ALPHABET, $i);
        $groupAt = $i;
        if ($run > $contiguous) {
            $contiguous = $run;
            $contiguousAt = $i;
        }
        $i += $run;
        if ($run === 0) {
            $i++; // hard break byte: skip it, never stall

            continue;
        }
        if (! $fragment) {
            $gap = strcspn($data, ALPHABET, $i);
            $i += $gap > 0 ? $gap : 1;

            continue;
        }
        $group = $run;
        $solid = ($run >= $floor && $encoded(substr($data, $groupAt, $run))) ? $run : 0;
        $prev = $run;
        $chunks = 1;
        while ($i < $len) {
            $seps = strspn($data, TRANSPARENT, $i);
            if ($seps === 0) {
                break;
            }
            // PEM/DER line wrapping is 64 columns. A 64-char base64 line
            // followed by a newline is an armoured public key or certificate
            // block, not somebody's session string cut into pieces — treating
            // it as a split would flag every committed public key.
            if ($prev === 64 && ($seps === 1 ? $data[$i] === "\n" : ($seps === 2 && $data[$i] === "\r" && $data[$i + 1] === "\n"))) {
                break;
            }
            $after = $i + $seps;
            $next = strspn($data, ALPHABET, $after);
            if ($next === 0) {
                break;
            }
            $prev = $next;
            $chunks++;
            $group += $seps + $next;
            if ($next > $contiguous) {
                // a chunk swallowed by the group is still a contiguous run
                $contiguous = $next;
                $contiguousAt = $after;
            }
            if ($next >= $floor && $encoded(substr($data, $after, $next))) {
                $solid += $next;
            }
            $i = $after + $next;
        }
        if ($group > $frag && $solid >= $min && $chunks <= FRAG_MAX_CHUNKS) {
            $frag = $group;
            $fragAt = $groupAt;
        }
        $gap = strcspn($data, TRANSPARENT, $i);
        $i += $gap > 0 ? $gap : 1;
    }

    return [$contiguous, $contiguousAt, $frag, $fragAt];
};

$findings = [];
$envFindings = [];

/**
 * Record at most ONE finding per blob: the longest run's extent only.
 * Nothing from the matched text is kept or printed.
 */
$absorb = static function (string $label, string $data, int $cap, bool $isBinary, string $key = '') use ($scan, $fragment, $min, &$findings): void {
    if (strlen($data) > $cap) {
        $data = substr($data, 0, $cap);
    }
    if ($data === '') {
        return;
    }
    [$contiguous, $contiguousAt, $frag, $fragAt] = $scan($data, $fragment);
    $longest = max($contiguous, $frag);
    if ($longest < $min) {
        return;
    }
    $at = $contiguous >= $frag ? $contiguousAt : $fragAt;
    // The line the run STARTS on. Only the offset is used; the matched bytes
    // are never captured into a variable that could reach the report.
    $line = substr_count(substr($data, 0, $at), "\n") + 1;
    $findings[] = [
        'file' => $label,
        'key' => $key !== '' ? $key : 'path:'.$label,
        'line' => $line,
        'length' => $longest,
        'shape' => $contiguous >= $frag ? 'contiguous' : 'fragmented',
        'binary' => $isBinary,
        'ref' => substr(hash('sha256', $label.':'.$longest.':'.$min), 0, 12),
    ];
};

$scanned = 0;
$totalBytes = 0;
$truncatedFiles = 0;
$binaryFiles = 0;
$truncatedBinary = 0;
$incomplete = null;

/**
 * Record why the run cannot rule out what it did not reach. One reason or
 * several: a byte cap AND a truncated object set are both true at once, and
 * the operator needs to see each of them, not the first one found.
 */
$markIncomplete = static function (string $why) use (&$incomplete): void {
    $incomplete = $incomplete === null ? $why : $incomplete.'; '.$why;
};

foreach ($files as $rel) {
    $abs = $root.'/'.$rel;

    // .env must never be COMMITTED (.env.example / .env.dist are templates).
    // Tracked-only: a developer's own untracked .env is the healthy state.
    if (isset($trackedLookup[$rel])) {
        $base = basename($rel);
        if ($base === '.env' || (str_starts_with($base, '.env.') && ! str_ends_with($base, '.example') && ! str_ends_with($base, '.dist'))) {
            $envFindings[] = $rel;
        }
    }

    if (! is_file($abs) || ! is_readable($abs)) {
        continue;
    }
    $size = filesize($abs);
    if ($size === 0) {
        continue;
    }
    if ($totalBytes + $size > $maxTotalBytes) {
        $markIncomplete("the whole-scan byte cap ({$maxTotalBytes} B) was reached at {$rel}; the rest of the tree was NOT scanned");

        break;
    }
    $totalBytes += $size;

    $data = file_get_contents($abs);
    if ($data === false || $data === '') {
        continue;
    }
    $scanned++;

    $isBinary = str_contains(substr($data, 0, 8192), "\0");
    if ($isBinary) {
        $binaryFiles++;
    }
    $cap = $isBinary ? $binaryPrefix : $maxBytes;
    if (strlen($data) > $cap) {
        $truncatedFiles++;
        if ($isBinary) {
            $truncatedBinary++;
        }
    }
    $absorb($rel, $data, $cap, $isBinary);
}

// ---------------------------------------------------------------------------
// 3. History — bounded recent-reach scan (the SEC-1 shape: committed once,
//    redacted in the tree later, still reachable).
// ---------------------------------------------------------------------------

$historyCount = 0;
$historyBytes = 0;

// The bound this run ASKED for, before the object set has its say. Overwritten
// below whenever the object set turns out to be less than that bound.
$historyDepth = $history < 0
    ? "all reachable from {$historyRefs}"
    : ($history === 0 ? 'none' : "{$history} commits per ref from {$historyRefs}");

if (! $isCheckout || $history === 0) {
    $historyNote = $history === 0
        ? 'NOT SCANNED (--history=0): a credential committed and later redacted in the tree is NOT covered'
        : 'NOT SCANNED (no git checkout at --root)';
    // The depth line must not contradict the note above it: nothing was
    // reached, so nothing is claimed.
    $historyDepth = 'none (history '.$historyNote.'; nothing is claimed)';
} else {
    $revArgs = $historyRefs === 'all' ? ['--all'] : [$historyRefs];
    if ($history >= 0) {
        array_unshift($revArgs, '--max-count='.$history);
    }
    $commits = array_values(array_filter(explode("\n", $git($root, ['rev-list', ...$revArgs]))));
    $scannedCommits = count($commits);

    // How many commits the OBJECT SET holds under the same refs, unbounded by
    // --history. In a complete repository this equals $scannedCommits, which
    // is what makes "479 of 479" a checkable statement rather than a
    // reassurance; in a shallow one it is the whole of what exists locally.
    $presentCommits = (int) trim($git($root, ['rev-list', '--count', ...($historyRefs === 'all' ? ['--all'] : [$historyRefs])]));
    $selected = $history < 0
        ? "every ref ({$historyRefs})"
        : "up to {$history} per ref from {$historyRefs}";

    // The PARTIAL verdict, computed once and folded into whichever branch the
    // shallow axis lands in. A clone can be BOTH (`git clone --depth 20
    // --filter=blob:none` is an ordinary thing to write in CI), and reporting
    // only one of the two truncations would be a third false claim: the one
    // that is left out is exactly the one the operator would not know to fix.
    $partialNote = '';
    $partialDepth = '';
    $partialIncomplete = '';
    $partialPrimary = false; // the not-shallow path, where this IS the verdict

    if ($partialObjects['absent'] !== null && $partialObjects['absent'] > 0) {
        $absent = $partialObjects['absent'];
        $objectsTotal = $partialObjects['total'];
        $via = $partialObjects['promisor'] === [] ? '' : ' (promisor remote '.implode(', ', array_keys($partialObjects['promisor'])).($partialObjects['filter'] === '' ? '' : ', filter '.$partialObjects['filter']).')';
        $partialPrimary = $truncation['state'] !== 'shallow';
        // On the not-shallow path this is not a footnote appended to a verdict,
        // it IS the verdict — so it is phrased as one, rather than as "PARTIAL
        // as well" next to a summary line that has already ruled on the object
        // set. A hedge is read as a maybe.
        $partialNote = $partialPrimary
            ? sprintf(
                ' — TRUNCATED: this is a PARTIAL (promisor) clone%s: %d of %d reachable object(s) are ABSENT from this clone and were NOT read, so a credential committed into any of them is NOT covered. Git does not report an absent object as an error, it reports it as nothing, which is indistinguishable from an object with nothing in it',
                $via,
                $absent,
                $objectsTotal
            )
            : sprintf(
                '; PARTIAL as well: %d of %d reachable object(s) are ABSENT from this clone%s and were NOT read, so a credential in any of them is NOT covered — git reports an absent object as no output, which is indistinguishable from an object with nothing in it',
                $absent,
                $objectsTotal,
                $via
            );
        $partialDepth = sprintf(
            'TRUNCATED (PARTIAL object set: %d of %d reachable object(s) ABSENT and NOT read%s)',
            $absent,
            $objectsTotal,
            $via
        );
        $partialIncomplete = sprintf(
            'the object set is PARTIAL — this is a promisor/partial clone: %d of %d reachable object(s) are ABSENT from this clone and were not read%s. Git does not report an absent object as an error, it reports it as nothing, so a credential committed into one of them is silently NOT covered. Re-clone without --filter (or run git fetch --refetch / git config --unset remote.origin.promisor) and re-run',
            $absent,
            $objectsTotal,
            $via
        );
    } elseif ($partialObjects['absent'] === null && $partialObjects['promisor'] !== []) {
        // The measurement could not run (a git without --missing=print) on a
        // repository that says it is a promisor. Silence here is not an
        // all-clear: the config is positive evidence that this object set is
        // allowed to be incomplete, and the one command that could have
        // measured it cannot be run.
        $partialNote = '; object-set completeness UNMEASURABLE: this is a promisor clone and this git could not run rev-list --missing=print, so how many objects are absent is UNKNOWN';
        $partialDepth = 'UNKNOWN (promisor clone; absent-object count could not be measured on this git)';
        $partialIncomplete = 'the object set\'s completeness is UNKNOWN — this is a promisor clone (remote '.implode(', ', array_keys($partialObjects['promisor'])).') and this git could not run `rev-list --missing=print` to measure how many objects are absent. Make git >= 2.19 available, or re-clone without --filter, then re-run';
    } elseif ($partialObjects['promisor'] !== []) {
        // A promisor clone whose objects are ALL present. Not truncated, and
        // NOT reported as truncated — a guard that cried wolf on a
        // fully-fetched partial clone would be switched off within a day. It
        // is still disclosed, because the completeness is a property of the
        // network right now and not of the clone: the next gc, or a network
        // failure, takes it away.
        $partialNote = sprintf(
            '; this is a promisor/partial clone (remote %s) but all %d reachable object(s) are present, so nothing is missing from it right now — that completeness is a property of the current network, not of the clone',
            implode(', ', array_keys($partialObjects['promisor'])),
            $partialObjects['total'] ?? 0
        );
    }

    if ($partialIncomplete !== '') {
        $markIncomplete($partialIncomplete);
    }

    if ($truncation['state'] === 'shallow') {
        $boundaryWord = $truncation['boundary'] === 1 ? 'boundary' : 'boundaries';
        $at = $truncation['oldest'] === '' ? '' : " (oldest reachable {$truncation['oldest']})";
        $historyNote = sprintf(
            'scanned (%d of %d commits present under %s) — TRUNCATED: this is a SHALLOW clone, the true total is UNKNOWN, and ancestry is cut at %d shallow %s%s; every commit before that boundary was never fetched, so a credential committed there is NOT covered%s',
            $scannedCommits,
            $presentCommits,
            $selected,
            $truncation['boundary'],
            $boundaryWord,
            $at,
            $partialNote
        );
        $historyDepth = sprintf(
            'TRUNCATED (SHALLOW object set: %d of %d commits present under %s, true total UNKNOWN, ancestry cut at %d %s%s)',
            $scannedCommits,
            $presentCommits,
            $selected,
            $truncation['boundary'],
            $boundaryWord,
            $at
        );
        $markIncomplete(
            "the object set is TRUNCATED — this is a SHALLOW clone: {$presentCommits} commit(s) are present, the repository's true total is UNKNOWN to this clone, and ancestry is cut at {$truncation['boundary']} shallow {$boundaryWord}{$at}. A credential committed before that boundary is NOT covered. Fetch the full history (git fetch --unshallow, or GIT_DEPTH: \"0\" in CI) and re-run"
        );
    } elseif ($truncation['state'] === 'unknown' || $partialObjects['absent'] === null) {
        // Two different reasons to refuse a claim, one verdict. Either git could
        // not be asked whether the clone is shallow (and no marker could be
        // read), or the absent-object measurement could not run at all — a git
        // without `rev-list --missing=print` (2.19, 2018). The second is
        // checked here rather than left to fall through to the complete branch
        // because an UNMEASURED object set is not a complete one, and printing
        // "COMPLETE" over it would be the original defect wearing a new hat.
        $why = $truncation['state'] === 'unknown'
            ? 'git could not report whether this clone is shallow and no .git/shallow marker could be located'
            : 'this git could not run `rev-list --missing=print`, so how many reachable objects are absent from this clone could not be measured';
        $historyNote = sprintf(
            'scanned (%d commit(s)) — object-set completeness UNKNOWN: %s, so this count is a LOWER BOUND, not a claim of full coverage%s',
            $scannedCommits,
            $why,
            $partialNote
        );
        $historyDepth = 'UNKNOWN (object-set completeness could not be determined; the count above is a lower bound)';
        $markIncomplete(
            "the object set's completeness is UNKNOWN — {$why}, so no unbounded-coverage claim is made. Make git >= 2.19 available (and >= 2.15 for --is-shallow-repository), or run in an ordinary clone rather than a linked worktree on a pre-2.5 git, then re-run"
        );
    } else {
        // The object set is not SHALLOW. It may still be PARTIAL, and when it
        // is, "COMPLETE" must not be printed anywhere in the same breath as the
        // admission — a line that says "object set COMPLETE … ; PARTIAL as
        // well: 7 of 11 objects are ABSENT" is a sentence that contradicts
        // itself, and whichever half the reader skims is the wrong half. So
        // the word is chosen by the measurement, not by the absence of a
        // shallow marker.
        $objectWord = $partialPrimary
            ? 'object set PARTIAL — not shallow'
            : 'object set COMPLETE — not shallow, no object absent';
        $historyNote = $history < 0
            ? sprintf('scanned (every commit reachable from %s; %d of %d commits present, %s%s', $historyRefs, $scannedCommits, $presentCommits, $objectWord, $partialNote)
            : sprintf('scanned (%d commits, up to %d per ref, from %s; %s%s', $scannedCommits, $history, $historyRefs, $objectWord, $partialNote);
        $historyNote .= $partialPrimary ? ')' : '';
    }

    // The PARTIAL verdict is applied to the depth line AFTER every branch
    // above, because each branch assigns $historyDepth itself and an override
    // placed before them would be silently overwritten. The depth line is what
    // a reader skims: "history depth all reachable from all" printed under a
    // run that could not read 7 of its 11 objects is exactly the false claim
    // this check exists to remove, and a shallow+partial clone that reported
    // only its shallow boundary would be naming one truncation and hiding the
    // other — the hidden one being the one the operator would not know to fix.
    if ($partialDepth !== '') {
        $historyDepth = str_starts_with($historyDepth, 'TRUNCATED (SHALLOW')
            ? $historyDepth.'; '.$partialDepth
            : $partialDepth;
    }

    if ($commits === []) {
        $historyNote .= ' — no commits reachable';
    } else {
        // (sha, path) for every object reachable from those commits.
        $shaPath = [];
        $shas = [];
        foreach (explode("\n", $git($root, ['rev-list', '--objects', ...$commits])) as $line) {
            if ($line === '') {
                continue;
            }
            $sp = strpos($line, ' ');
            $sha = $sp === false ? $line : substr($line, 0, $sp);
            if (! isset($shaPath[$sha])) {
                $shaPath[$sha] = $sp === false ? '(no path)' : substr($line, $sp + 1);
                $shas[] = $sha;
            }
        }

        // Blobs only. stdin is a temp FILE, not a pipe: writing tens of
        // thousands of SHAs into a pipe while git is blocked writing stdout
        // deadlocks the pair.
        $shaFile = tempnam(sys_get_temp_dir(), 'credscan');
        $blobs = [];
        if ($shaFile === false) {
            $historyNote .= ' — SKIPPED, could not stage the object list';
            $commits = [];
        } else {
            file_put_contents($shaFile, implode("\n", $shas)."\n");
            $check = proc_open(
                ['git', '-C', $root, 'cat-file', '--batch-check'],
                [0 => ['file', $shaFile, 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            if (is_resource($check)) {
                while (($line = fgets($pipes[1])) !== false) {
                    $parts = explode(' ', rtrim($line, "\n"));
                    if (($parts[1] ?? '') === 'blob') {
                        $blobs[] = $parts[0];
                    }
                }
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($check);
            }

            $batch = proc_open(
                ['git', '-C', $root, 'cat-file', '--batch'],
                [0 => ['file', $shaFile, 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            if (is_resource($batch)) {
                $hit = 0;
                while (($header = fgets($pipes[1])) !== false) {
                    $header = rtrim($header, "\n");
                    $sp = strpos($header, ' ');
                    if ($sp === false) {
                        continue;
                    }
                    $sha = substr($header, 0, $sp);
                    // header is "<sha> <type> <size>": size is the LAST field.
                    $size = (int) substr($header, (int) strrpos($header, ' ') + 1);
                    $body = $size > 0 ? $read($pipes[1], $size) : '';
                    $read($pipes[1], 1); // git cat-file's per-object trailing newline
                    if ($body === '') {
                        continue;
                    }
                    $historyCount++;
                    $historyBytes += $size;
                    if ($historyBytes > $maxTotalBytes) {
                        $markIncomplete("the whole-scan byte cap ({$maxTotalBytes} B) was reached during the history scan; older blobs were NOT scanned");

                        break;
                    }
                    if ($hit >= 50) {
                        continue; // report a bounded number of findings, keep the walk cheap
                    }
                    $probe = substr($body, 0, $maxBytes);
                    $isBinary = str_contains(substr($probe, 0, 8192), "\0");
                    $cap = $isBinary ? $binaryPrefix : $maxBytes;
                    $before = count($findings);
                    if (getenv('TF_SCAN_DEBUG')) {
                        fwrite(STDERR, sprintf("DBG %s size=%d bin=%d\n", $shaPath[$sha] ?? '?', $size, (int) $isBinary));
                    }
                    $absorb('history@'.$shaPath[$sha], $probe, $cap, $isBinary, $sha);
                    if (count($findings) > $before) {
                        $hit++;
                    }
                }
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($batch);
            }
            @unlink($shaFile);
        }
    }
}

// ---------------------------------------------------------------------------
// 4. Report. Never prints a matched value.
// ---------------------------------------------------------------------------

$summary = sprintf(
    '%d files scanned (%s), min run %d, fragment %s, history %s',
    $scanned,
    $mode,
    $min,
    $fragment ? 'on' : 'OFF (--no-fragment: a split run is NOT detected)',
    $historyNote
);
$coverage = sprintf(
    'coverage: .gitignore paths %s · binary %s (%d file(s), %d truncated at the prefix) · per-file cap %d B · history depth %s',
    $includeIgnored ? 'INCLUDED' : 'not walked',
    $binaryFiles > 0 ? 'scanned' : 'none present',
    $binaryFiles,
    $truncatedBinary,
    $maxBytes,
    $historyDepth
);

// An incomplete run says so on stderr, ALWAYS, before anything else: the
// reason, both coverage lines, and the rule that makes it exit 3. It does NOT
// exit here — a finding found inside a partial scan is still a finding, and
// answering 3 to a caller who needs to know about a live credential would be
// the same class of omission this whole check exists to stop. The exit code
// below resolves in the order FAIL > INCOMPLETE > clean.
if ($incomplete !== null) {
    fwrite(STDERR, "credential-guard: INCOMPLETE — {$incomplete}\n");
    fwrite(STDERR, "  {$summary}\n  {$coverage}\n");
    fwrite(STDERR, "  a PARTIAL scan is not a pass: it cannot rule out a credential outside what it reached, so this run cannot exit 0\n");
}

// Partition: a finding whose key is baselined is a KNOWN exposure — reported
// on every run, but not a new failure. Everything else fails the gate.
$accepted = [];
$failing = [];
foreach ($findings as $f) {
    if (array_key_exists($f['key'], $baseline)) {
        $accepted[] = $f;
    } else {
        $failing[] = $f;
    }
}

if ($failing === [] && $envFindings === []) {
    if ($accepted !== []) {
        // Never a bare "clean": a receipted exposure is still an exposure.
        foreach ($accepted as $f) {
            printf(
                "credential-guard: ACCEPTED (baseline %s) %s:%d: %s run, %d chars, blob %s (value withheld)\n",
                $baselineLabel,
                $f['file'],
                $f['line'],
                $f['shape'],
                $f['length'],
                substr($f['key'], 0, 12),
            );
        }
        // "PASS" is a claim about the whole surface, so it is only printed by
        // a run that reached it. An incomplete run still prints every
        // receipt — that ratchet does not switch off — but it does not get to
        // call itself a pass.
        printf(
            $incomplete === null
                ? "credential-guard: PASS with %d receipted exposure(s) still reachable — purge them (SEC-3), then delete their line from %s\n"
                : "credential-guard: %d receipted exposure(s) still reachable, printed as a receipt — purge them (SEC-3), then delete their line from %s (this run is INCOMPLETE and is NOT a pass)\n",
            count($accepted),
            $baselineLabel
        );
    }
    // "clean" is a claim about the WHOLE surface, so it is only ever printed
    // by a run that reached the whole surface. An incomplete run has already
    // said, in words and in numbers, what it did not reach.
    if (! $quiet && $incomplete === null) {
        printf("credential-guard: clean (%s)\n", $summary);
        printf("credential-guard: %s\n", $coverage);
    }
    exit($incomplete === null ? 0 : 3);
}

fwrite(STDERR, "credential-guard: FAIL — credential-shaped material found\n");
foreach ($envFindings as $rel) {
    fwrite(STDERR, sprintf("  %s: committed env file (rename to .env.example, or git rm --cached %s)\n", $rel, $rel));
}
foreach ($failing as $f) {
    fwrite(STDERR, sprintf(
        "  %s:%d: %s run, %d chars%s, ref sha256:%s (value withheld)\n",
        $f['file'],
        $f['line'],
        $f['shape'],
        $f['length'],
        $f['binary'] ? ', binary prefix scan' : '',
        $f['ref']
    ));
}
foreach ($accepted as $f) {
    fwrite(STDERR, sprintf(
        "  %s:%d: %s run, %d chars, blob %s — ACCEPTED (baseline %s), still reachable\n",
        $f['file'],
        $f['line'],
        $f['shape'],
        $f['length'],
        substr($f['key'], 0, 12),
        $baselineLabel,
    ));
}
fwrite(STDERR, sprintf("credential-guard: %s\n", $summary));
fwrite(STDERR, sprintf("credential-guard: %s\n", $coverage));
fwrite(STDERR, "credential-guard: rotate the exposed secret, redact the file, then re-run.\n");
exit(1);
