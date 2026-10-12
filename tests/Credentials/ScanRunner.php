<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Credentials;

/**
 * Runs bin/scan-credentials.php the way a gate runs it: as a child process,
 * against a throwaway git checkout, and reports the exit code plus both
 * streams. Nothing here ever puts a matched value into an assertion message.
 *
 * The fake credential is ASSEMBLED AT RUNTIME on purpose. A literal 200+ char
 * base64 run written into this file would itself be a finding in the repo's own
 * tests/ directory — the guard scans the tree it lives in.
 */
final class ScanRunner
{
    public function __construct(
        private readonly string $script,
        private readonly string $sandbox,
    ) {}

    public function script(): string
    {
        return $this->script;
    }

    public function sandbox(): string
    {
        return $this->sandbox;
    }

    /**
     * A fresh throwaway git checkout, isolated from the developer's own git
     * config (no global hooks, no signing, no user identity leaking in).
     */
    public static function repo(string $script): self
    {
        $dir = sys_get_temp_dir().'/credscan-'.bin2hex(random_bytes(6));
        if (! mkdir($dir, 0o777, true) && ! is_dir($dir)) {
            throw new \RuntimeException("could not create sandbox {$dir}");
        }
        $runner = new self($script, $dir);
        $runner->git(['init', '-q', '-b', 'main']);
        $runner->write('.gitignore', "vendor/\n.env\n*.bin\n");

        return $runner;
    }

    /**
     * @param  list<string>  $args
     * @param  list<string>  $git
     */
    public function git(array $git): string
    {
        $env = [
            'PATH' => getenv('PATH') ?: '/usr/bin:/bin',
            'HOME' => $this->sandbox,
            'GIT_CONFIG_GLOBAL' => '/dev/null',
            'GIT_CONFIG_SYSTEM' => '/dev/null',
            'GIT_AUTHOR_NAME' => 'scan-test',
            'GIT_AUTHOR_EMAIL' => 'scan-test@example.invalid',
            'GIT_COMMITTER_NAME' => 'scan-test',
            'GIT_COMMITTER_EMAIL' => 'scan-test@example.invalid',
        ];
        $proc = proc_open(
            ['git', '-C', $this->sandbox, ...$git],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->sandbox,
            $env,
        );
        if (! is_resource($proc)) {
            throw new \RuntimeException('git could not be started');
        }
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0) {
            throw new \RuntimeException('git '.implode(' ', $git)." failed ({$code}): {$err}");
        }

        return $out;
    }

    public function write(string $rel, string $contents): string
    {
        $abs = $this->sandbox.'/'.$rel;
        if (! is_dir(dirname($abs))) {
            mkdir(dirname($abs), 0o777, true);
        }
        file_put_contents($abs, $contents);

        return $abs;
    }

    /** Set a config value in this checkout. */
    public function config(string $key, string $value): void
    {
        $this->git(['config', $key, $value]);
    }

    public function read(string $rel): string
    {
        return (string) file_get_contents($this->sandbox.'/'.$rel);
    }

    /** Unstage a path WITHOUT re-adding it: `commit()` does `git add -A`. */
    public function untrack(string $rel): void
    {
        $this->git(['rm', '-q', '--cached', '--', $rel]);
        $this->git(['commit', '-q', '--no-gpg-sign', '--allow-empty', '-m', 'untrack '.$rel]);
    }

    public function commit(string $message): void
    {
        $this->git(['add', '-A']);
        $this->git(['commit', '-q', '--no-gpg-sign', '--allow-empty', '-m', $message]);
    }

    /**
     * A `--depth N` CLONE of this repository — the shape CI actually runs when
     * GIT_DEPTH is set, and the shape pipeline #206's green credential-guard
     * job had. `file://` is mandatory: git ignores --depth for a plain local
     * path, and a silently-ignored --depth would make this helper build a
     * FULL clone while claiming to be a shallow one — the exact failure mode
     * these tests exist to catch, in the test that proves the catch.
     */
    public function shallowClone(int $depth): self
    {
        $dir = dirname($this->sandbox).'/'.basename($this->sandbox).'-depth'.$depth.'-'.bin2hex(random_bytes(4));
        $this->git(['clone', '--quiet', '--depth', (string) $depth, '--no-tags', 'file://'.$this->sandbox, $dir]);

        $marker = $dir.'/.git/shallow';
        if (! is_file($marker) || trim((string) file_get_contents($marker)) === '') {
            throw new \RuntimeException("the depth-{$depth} clone is not shallow: no non-empty {$marker} — the clone did not honour --depth");
        }

        return new self($this->script, $dir);
    }

    /**
     * A `--filter=blob:none` CLONE of this repository — a PARTIAL (promisor)
     * clone: every commit and tree is fetched, blobs are not, and `git
     * rev-parse --is-shallow-repository` answers "false" over it. `file://` is
     * mandatory for the same reason as in shallowClone(): git ignores --filter
     * for a plain local path, so a silently-ignored flag would build a FULL
     * clone while claiming to be a partial one.
     *
     * The server must allow filtering first (`uploadpack.allowFilter`, git
     * 2.19+); without it git prints "filtering not recognized by server,
     * ignoring" and produces a complete clone that still carries a promisor
     * config. So this sets the flag on the SOURCE and then VERIFIES the result
     * by measuring absent objects with the same command the guard uses. A
     * helper that could return a non-partial clone would make every test below
     * it a test of nothing.
     *
     * @param  bool  $checkout  check the working tree out. A checkout lazily
     *                          fetches the blobs it needs, which REPAIRS the
     *                          object set — so the default is a clone that is
     *                          still genuinely missing its history blobs.
     */
    public function partialClone(string $filter = 'blob:none', bool $checkout = false): self
    {
        return $this->buildPartialClone($filter, $checkout, []);
    }

    /**
     * A partial clone in which exactly $restore is checked out and the rest of
     * the history stays absent.
     *
     * A full `git checkout` on a small repository lazily fetches EVERY blob
     * (there is nothing else worth having), which silently repairs the object
     * set and leaves a clone that is not partial at all — partialClone() then
     * throws, correctly. Restoring a single path is how you get the shape that
     * actually matters for the exit-code ordering: a working tree that HOLDS a
     * credential, next to a history the scan could not read.
     *
     * @param  list<string>  $restore
     */
    public function partialCloneWith(string $filter, array $restore): self
    {
        return $this->buildPartialClone($filter, false, $restore);
    }

    /**
     * @param  list<string>  $restore
     */
    private function buildPartialClone(string $filter, bool $checkout, array $restore): self
    {
        $this->git(['config', 'uploadpack.allowFilter', 'true']);
        $dir = dirname($this->sandbox).'/'.basename($this->sandbox).'-'.str_replace(':', '', $filter).'-'.bin2hex(random_bytes(4));
        $args = ['clone', '--quiet', '--filter', $filter, '--no-tags'];
        if (! $checkout) {
            $args[] = '--no-checkout';
        }
        $this->git([...$args, 'file://'.$this->sandbox, $dir]);

        $clone = new self($this->script, $dir);
        foreach ($restore as $path) {
            $clone->git(['checkout', 'HEAD', '--', $path]);
        }

        // Two independent ways the clone could have failed to be partial, both
        // silent, both fatal to a test built on top of this. The clone is
        // destroyed on the way out: a throw here means the fixture is broken,
        // and a broken fixture must not leave a promisor clone behind on disk
        // for the next run (or the next agent) to trip over.
        try {
            if (trim($clone->git(['config', '--get', 'remote.origin.promisor'])) !== 'true') {
                throw new \RuntimeException("the --filter={$filter} clone is not a promisor clone: remote.origin.promisor is unset — the server refused the filter (set uploadpack.allowFilter on the source)");
            }
            $absent = $clone->missingObjects();
            if ($absent < 1) {
                throw new \RuntimeException("the --filter={$filter} clone has {$absent} absent object(s): it is not missing anything, so it is not a partial clone — the filter was ignored, or the checkout fetched everything back");
            }
        } catch (\RuntimeException $e) {
            $clone->destroy();

            throw $e;
        }

        return $clone;
    }

    /**
     * How many objects `rev-list --all --objects` reaches that this clone does
     * not hold. The same measurement the guard makes, with lazy fetching
     * switched off — without that, merely ASKING the question repairs the
     * object set and the answer is always zero.
     */
    public function missingObjects(): int
    {
        return $this->countObjects(true);
    }

    /** How many objects `rev-list --all --objects` reaches in total. */
    public function enumeratedObjects(): int
    {
        return $this->countObjects(false);
    }

    private function countObjects(bool $onlyMissing): int
    {
        $env = [
            'PATH' => getenv('PATH') ?: '/usr/bin:/bin',
            'HOME' => $this->sandbox,
            'GIT_CONFIG_GLOBAL' => '/dev/null',
            'GIT_CONFIG_SYSTEM' => '/dev/null',
            'GIT_NO_LAZY_FETCH' => '1',
        ];
        $proc = proc_open(
            ['git', '-C', $this->sandbox, 'rev-list', '--all', '--objects', '--missing=print'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->sandbox,
            $env,
        );
        if (! is_resource($proc)) {
            throw new \RuntimeException('git could not be started');
        }
        $out = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        $count = 0;
        foreach (explode("\n", $out) as $line) {
            if ($line === '') {
                continue;
            }
            if (! $onlyMissing || str_starts_with($line, '?')) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Make the promisor remote unreachable, by pointing it at a path that does
     * not exist. This is the shape that matters: with a REACHABLE promisor,
     * `git cat-file` silently fetches the missing blob and the guard finds the
     * credential anyway, so the dangerous case — an absent object reported as
     * an absence of a finding — only exists when the fetch cannot happen.
     */
    public function breakPromisorRemote(): void
    {
        $this->git(['remote', 'set-url', 'origin', $this->sandbox.'/there-is-no-such-repository']);
    }

    /**
     * @param  list<string>  $args
     * @param  array<string, string>|null  $env  a COMPLETE environment, or null
     *                                           to inherit the current one
     * @return array{code: int, out: string, err: string, all: string}
     */
    public function scan(array $args = [], ?array $env = null): array
    {
        $cmd = array_merge([PHP_BINARY, $this->script, '--root='.$this->sandbox], $args);
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->sandbox, $env);
        if (! is_resource($proc)) {
            throw new \RuntimeException('the scanner could not be started');
        }
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        return ['code' => $code, 'out' => $out, 'err' => $err, 'all' => $out.$err];
    }

    public function destroy(): void
    {
        if (! is_dir($this->sandbox)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->sandbox, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var \SplFileInfo $f */
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($this->sandbox);
    }

    /**
     * An obviously fake, credential-shaped run long enough to trip the guard.
     * Never a real credential, never written as a literal — assembled at
     * runtime so no 200+ char alphabet run ever lands in this file, which the
     * guard itself scans.
     *
     * Mixed upper/lower/digit on purpose: the guard requires a solid chunk to
     * contain a digit so that long class names do not read as encoded data, so
     * a fake made only of letters would (correctly) not be detected.
     */
    public static function fakeRun(int $chars = 1080): string
    {
        $unit = 'FakeTelegramSession1'; // 19 chars, alphabet-safe, self-documenting
        $out = str_repeat($unit, (int) ceil($chars / strlen($unit)));

        return substr($out, 0, $chars);
    }
}
