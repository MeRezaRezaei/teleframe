<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * PostgresDumper (remainder design §2/§3/§9) — the pg_dump half of the
 * dump surface: streams one PostgreSQL database snapshot into a
 * vault-scope DumpWorkspace spool (seekable tempfile, NEVER a live pipe),
 * exit-checked and loud — a non-zero exit throws BackupException carrying
 * the last 4 KiB stderr tail, never a silent empty result.
 *
 * Scope ruling: plain SQL (--format=plain) is the ONLY slice format —
 * nothing compressed, nothing custom-coded — and it carries NO --no-owner
 * surprise: ownership statements stay in the dump so a restore keeps the
 * source role grants (the wave-2 restorer feeds this straight to psql).
 *
 * Process posture mirrors MysqlDumper exactly: the binary runs through an
 * injectable process runner — callable(array $argv, array $env, string
 * $cwd): array{exit:int, stderr:string} — so tests stay fully offline. A
 * null runner (the default, and the only production path) is the built-in
 * raw proc_open implementation: the child's stdout is piped into the spool
 * file handle with a chunked fan-out (stream_copy_to_stream-style, capped
 * stderr tail), keeping peak PHP memory at one pipe buffer regardless of
 * dump size (S8 discipline).
 *
 * Credentials: password env-at-run ONLY — PGPASSWORD comes from the host
 * environment (getenv) and is merged into the child env array; it is never
 * part of argv (ps-safe), never stored in DatabaseTarget, and a missing
 * host env value falls back to '' — the DB server decides.
 *
 * probe() is the exec-safe binary gate: PATH walk + `pg_dump --version`
 * through the same runner; a missing binary, non-zero exit, or absent
 * output all yield null.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2
 * (PostgresDumper row), §3 (DatabaseDumper contract + proc-runner posture),
 * §9 (ctor) + verbatim docs/verbatim/2026-09-24-*.md:38-51 ("simply dump
 * the laravel database and make a backup from it in schedules").
 */
final class PostgresDumper implements DatabaseDumper
{
    /** The dump binary this dumper drives. */
    private const BINARY = 'pg_dump';

    /** The env-at-run password variable for this driver. */
    private const PASSWORD_ENV = 'PGPASSWORD';

    /** Stderr tail cap (design §3: last 4 KiB). */
    private const STDERR_TAIL_BYTES = 4096;

    /** Fan-out chunk size for the stdout → spool copy. */
    private const STREAM_CHUNK = 65536;

    public function __construct(
        private readonly ?\Closure $runProcess = null,
    ) {}

    /**
     * Dump the target database into a fresh workspace spool file.
     *
     * @throws BackupException when the dump binary is absent, the dump
     *                         fails (non-zero exit), or the spool cannot
     *                         be written
     */
    public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
    {
        $started = time();
        $spool = $workspace->spool();
        $cwd = dirname($spool['path']);

        // argv = binary + the target's non-secret argument list (which
        // already carries the plain-SQL scope flag --format=plain for
        // pgsql — DatabaseTarget::argc() owns the driver flag shape). The
        // password NEVER appears here (env-at-run only).
        $argv = array_merge([self::BINARY], $target->argc());

        // env = target env + PGPASSWORD from the HOST environment. Missing
        // host env falls back to '' — the DB server decides.
        $env = $target->env() + [self::PASSWORD_ENV => (string) getenv(self::PASSWORD_ENV)];

        $result = $this->runProcess === null
            ? $this->runRawProcOpen($argv, $env, $cwd, $spool['handle'])
            : ($this->runProcess)($argv, $env, $cwd);

        $exit = (int) $result['exit'];
        $stderrTail = $this->cappedTail((string) $result['stderr']);

        if ($exit !== 0) {
            throw new BackupException(
                self::BINARY.' failed (exit '.$exit.'): '.$stderrTail,
            );
        }

        // EXIT 0 IS NOT SUCCESS. pg_dump reports success by exit code, and a
        // zero-byte dump exits 0: an empty schema with no matching objects, or
        // a `tables` whitelist that matches nothing, produces a clean exit and
        // an empty spool. The DumpResult then carried bytes = 0 all the way
        // into DatabaseBackupService::backup(), which stored it, wrote an 'ok'
        // ledger row and — the part that matters — TOUCHED THE WATCHDOG
        // HEARTBEAT. A failed backup therefore silenced the one no-show check
        // whose whole job is to notice that backups stopped happening: the
        // failure refreshed the heartbeat that was supposed to expire.
        //
        // Throwing HERE, before the DumpResult is returned, is what makes the
        // heartbeat unreachable rather than merely un-touched. The service
        // calls dump() as the FIRST statement of its try block, so a throw
        // here jumps straight to the catch, which releases the spool and
        // rethrows; the touch('backup') further down that same try is never
        // evaluated. The guarantee is structural — it does not depend on the
        // service remembering to check the size.
        $bytes = $this->spoolBytes($spool['path']);

        if ($bytes === 0) {
            throw new BackupException(sprintf(
                '%s reported success (exit 0) but wrote a ZERO-BYTE dump for database "%s" — nothing reached the spool. '
                .'An empty dump is the signature of an empty schema or a `tables` whitelist that matches no object, not of a '
                .'working backup. Nothing was stored, no run was recorded and no watchdog heartbeat was taken, so the no-show '
                .'check stays armed. Fix the dump scope (or the schema) and re-run.',
                self::BINARY,
                $target->database,
            ));
        }

        return new DumpResult(
            $spool['path'],
            $bytes,
            $target->driver,
            $target->database,
            $started,
            time(),
            $exit,
            $stderrTail,
        );
    }

    /**
     * The binary version line, or null when pg_dump is absent or its
     * --version probe failed. Exec-safe: PATH-walked is_executable gate
     * first; the version text comes from the same injectable runner (an
     * injected fake supplies the line through the contract's stderr
     * channel; the built-in runner captures stdout+stderr, since real
     * binaries print --version to stdout).
     */
    public function probe(): ?string
    {
        if ($this->runProcess !== null) {
            $result = ($this->runProcess)([self::BINARY, '--version'], [], $this->cwd());
            $exit = (int) $result['exit'];
            $text = (string) $result['stderr'];
        } else {
            $binary = $this->resolveBinary();
            if ($binary === null) {
                return null;
            }
            $result = $this->runProbeRaw($binary);
            $exit = (int) $result['exit'];
            $text = (string) $result['stderr'];
        }

        if ($exit !== 0 || trim($text) === '') {
            return null;
        }

        $first = trim(explode("\n", trim($text))[0]);

        return $first === '' ? null : $first;
    }

    /**
     * The built-in runner: raw proc_open with the child's stdout piped
     * directly into the spool file handle (chunked fan-out) and stderr
     * drained concurrently into a capped tail — no deadlock between the
     * two pipes, no unbounded PHP buffering.
     *
     * @param  resource  $spoolHandle  the workspace spool's open file handle
     * @return array{exit:int, stderr:string}
     */
    private function runRawProcOpen(array $argv, array $env, string $cwd, $spoolHandle): array
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin: closed immediately, dumps read none
            1 => ['pipe', 'w'], // stdout: fanned out into the spool
            2 => ['pipe', 'w'], // stderr: capped tail
        ];

        $proc = proc_open($argv, $descriptors, $pipes, $cwd, $env);
        if (! is_resource($proc)) {
            throw new BackupException(
                self::BINARY.' could not be started (proc_open failed — is it installed and on PATH?).',
            );
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdoutDone = false;
        $stderrDone = false;
        $stderrTail = '';

        while (! $stdoutDone || ! $stderrDone) {
            if (! $stdoutDone) {
                $chunk = fread($pipes[1], self::STREAM_CHUNK);
                if ($chunk === false || feof($pipes[1])) {
                    $stdoutDone = true;
                } elseif ($chunk !== '') {
                    if (fwrite($spoolHandle, $chunk) === false) {
                        $this->abortProc($proc, $pipes);
                        throw new BackupException('could not write the dump stream to the spool file.');
                    }
                }
            }

            if (! $stderrDone) {
                $chunk = fread($pipes[2], 8192);
                if ($chunk === false || feof($pipes[2])) {
                    $stderrDone = true;
                } elseif ($chunk !== '') {
                    $stderrTail .= $chunk;
                    if (strlen($stderrTail) > self::STDERR_TAIL_BYTES) {
                        $stderrTail = substr($stderrTail, -self::STDERR_TAIL_BYTES);
                    }
                }
            }

            // Neither stream finished: yield a beat so the child makes
            // progress before the next drain (non-blocking reads).
            if (! $stdoutDone || ! $stderrDone) {
                usleep(1000);
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($proc);
        fflush($spoolHandle);

        return ['exit' => $exit, 'stderr' => $stderrTail];
    }

    /** Drain the closed child's remaining stdio on a spool-write failure. */
    private function abortProc($proc, array $pipes): void
    {
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        if (is_resource($proc)) {
            proc_terminate($proc);
            proc_close($proc);
        }
    }

    /**
     * Probe runner for the default (raw) path: captures stdout AND stderr
     * (pg_dump --version prints to stdout) and reports the version text
     * through the contract's stderr channel, stdout preferred.
     *
     * @return array{exit:int, stderr:string}
     */
    private function runProbeRaw(string $binary): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open([$binary, '--version'], $descriptors, $pipes, $this->cwd());
        if (! is_resource($proc)) {
            return ['exit' => -1, 'stderr' => ''];
        }

        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($proc);

        return ['exit' => $exit, 'stderr' => $stdout !== '' ? $stdout : $stderr];
    }

    /**
     * Exec-safe binary lookup: a PATH walk ONLY, and every PATH entry must be
     * an ABSOLUTE directory. A relative name (or a relative PATH entry such as
     * `.` or `bin`) is what makes this exec-safe: is_executable('pg_dump')
     * resolves against the CURRENT WORKING DIRECTORY, so anyone who can drop
     * a file in the process CWD could get their own binary preferred over the
     * system one. Null = no usable pg_dump on this host.
     */
    private function resolveBinary(): ?string
    {
        $path = (string) getenv('PATH');
        foreach (explode(PATH_SEPARATOR, $path) as $dir) {
            if ($dir === '' || ! $this->isAbsolute($dir)) {
                continue;
            }
            $candidate = rtrim($dir, '/\\').DIRECTORY_SEPARATOR.self::BINARY;
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** Whether a PATH entry is absolute (Windows drive/UNC or a leading /). */
    private function isAbsolute(string $dir): bool
    {
        if (str_starts_with($dir, '/') || str_starts_with($dir, '\\\\')) {
            return true;
        }

        return strlen($dir) > 1 && $dir[1] === ':';
    }

    /** The spool file's observed byte size after the dump (fallback 0). */
    private function spoolBytes(string $path): int
    {
        $bytes = filesize($path);

        return $bytes === false ? 0 : $bytes;
    }

    /** The last 4 KiB of stderr (design §3 cap), byte-safe. */
    private function cappedTail(string $stderr): string
    {
        if (strlen($stderr) <= self::STDERR_TAIL_BYTES) {
            return $stderr;
        }

        return substr($stderr, -self::STDERR_TAIL_BYTES);
    }

    /** Process cwd: the caller's working directory (fallback '.'). */
    private function cwd(): string
    {
        $cwd = getcwd();

        return $cwd === false ? '.' : $cwd;
    }
}
