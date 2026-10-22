<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * DatabaseRestorer (remainder design §8 wave-2 line: "object bytes →
 * mysql/psql stdin, mirroring the dumper's process seam") — the restore
 * half of the dump surface: feeds one store object's SQL bytes into the
 * driver's client binary (mysql / psql) over the child's stdin,
 * exit-checked and loud — a non-zero exit throws BackupException carrying
 * the last 4 KiB stderr tail, never a silent empty result.
 *
 * Process posture mirrors MysqlDumper/PostgresDumper exactly: the client
 * binary runs through an injectable process runner so tests stay fully
 * offline — callable(array $argv, array $env, string $cwd):
 * array{exit:int, stderr:string}. A null runner (the default, and the
 * only production path) is the built-in raw proc_open implementation:
 * the source bytes are fanned into the child's stdin through ONE 512 KiB
 * buffer (S8 discipline — a dump object is never whole in PHP memory),
 * stdout is drained and discarded concurrently (restore clients print
 * nothing useful), and stderr is drained into a capped tail — no pipe
 * deadlock between the three descriptors, no unbounded buffering. An
 * injected runner is a complete process stand-in (offline fakes return
 * exit+stderr and are responsible for their own stdin behaviour); the
 * built-in runner stays byte-faithful.
 *
 * Source ruling (design §8 "object bytes"): restore() accepts the SAME
 * source surface as StoreService::put() — an open stream resource OR a
 * readable file path — and the CALLER decides how bytes come back
 * (StoreService::get() written into a php://temp stream, a spooled temp
 * file, a previously downloaded dump). A raw-bytes string is deliberately
 * REJECTED (unlike put(), which treats non-file strings as bytes): dump
 * objects can be GBs, so a restore source must never ride as a PHP
 * string in memory. A lazy callable source was considered and ruled out —
 * the caller already picks path-vs-stream at the call site, and a
 * callable adds a second indirection for no seam gain.
 *
 * Credentials: password env-at-run ONLY — MYSQL_PWD / PGPASSWORD comes
 * from the HOST environment (getenv) and is merged into the child env
 * array; it is never part of argv (ps-safe), never stored in
 * DatabaseTarget, never in rows or logs, and a missing host env value
 * falls back to '' — the DB server decides.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §8
 * (wave-2 DatabaseRestorer line), §3 (proc-runner posture, env-at-run
 * credentials, stderr tail cap) + mirror siblings src/Teleframe/Store/
 * Backup/{MysqlDumper,PostgresDumper}.php.
 */
final class DatabaseRestorer
{
    /** Stderr tail cap (mirror §3: last 4 KiB). */
    private const STDERR_TAIL_BYTES = 4096;

    /** Stdin fan-in buffer — one 512 KiB chunk, never the whole object. */
    private const STDIN_CHUNK = 524288;

    /** Stdout drain chunk size (discarded; restore clients print nothing). */
    private const DRAIN_CHUNK = 65536;

    public function __construct(
        private readonly ?\Closure $runProcess = null,
    ) {}

    /**
     * Restore one dump object into the target database through the
     * driver's client binary over stdin.
     *
     * @param  resource|string  $source  an open forward-only stream OR a
     *                                   readable file path (never raw
     *                                   bytes — dump objects can be GBs)
     *
     * @throws BackupException when the client binary is absent, the
     *                         restore fails (non-zero exit), the source
     *                         is unusable, or the driver is unsupported
     */
    public function restore(DatabaseTarget $target, $source): RestoreResult
    {
        [$binary, $passwordEnv] = $this->clientFor($target->driver);

        $handle = null;
        $ownsHandle = false;
        if (is_resource($source)) {
            $handle = $source;
        } elseif (is_string($source)) {
            if (! is_file($source)) {
                throw new BackupException(
                    'restore source must be a readable file path or an open stream — never raw bytes (dump objects can be GBs): '.$source,
                );
            }
            $handle = fopen($source, 'rb');
            if ($handle === false) {
                throw new BackupException('cannot open restore source path: '.$source);
            }
            $ownsHandle = true;
        } else {
            throw new BackupException('restore source must be a readable file path or an open stream.');
        }

        try {
            $started = time();

            // argv = client binary + the target's non-secret connection
            // args (mirror DatabaseTarget's flag style). The password
            // NEVER appears here (env-at-run only).
            $argv = array_merge([$binary], $this->clientArgc($target));

            // env = target env + the password key from the HOST
            // environment. Missing host env falls back to '' — the DB
            // server decides.
            $env = $target->env() + [$passwordEnv => (string) getenv($passwordEnv)];

            $cwd = $this->cwd();

            if ($this->runProcess === null) {
                $result = $this->runRawProcOpen($argv, $env, $cwd, $handle, $binary);
                $bytes = (int) $result['bytes'];
            } else {
                $result = ($this->runProcess)($argv, $env, $cwd);
                $bytes = $this->sourceBytes($source, $handle);
            }

            $exit = (int) $result['exit'];
            $stderrTail = $this->cappedTail((string) $result['stderr']);

            if ($exit !== 0) {
                throw new BackupException(
                    $binary.' failed (exit '.$exit.'): '.$stderrTail,
                );
            }

            return new RestoreResult(
                $target->driver,
                $target->database,
                $started,
                time(),
                $exit,
                $stderrTail,
                $bytes,
            );
        } finally {
            if ($ownsHandle) {
                fclose($handle);
            }
        }
    }

    /**
     * The built-in runner: raw proc_open with the source fanned into the
     * child's stdin through one 512 KiB buffer, stdout drained+discarded
     * and stderr drained into a capped tail — all three pipes served in
     * the same non-blocking loop so a chatty child can never deadlock
     * against a stdin writer (mirror the dumpers' concurrent-drain
     * posture, minus the spool: restore writes IN, not out).
     *
     * @param  resource  $source  the caller's (or our own) open source stream
     * @return array{exit:int, stderr:string, bytes:int} bytes = stdin bytes fanned in
     */
    private function runRawProcOpen(array $argv, array $env, string $cwd, $source, string $binary): array
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin: source fanned in, 512 KiB chunks
            1 => ['pipe', 'w'], // stdout: drained and discarded
            2 => ['pipe', 'w'], // stderr: capped tail
        ];

        $proc = proc_open($argv, $descriptors, $pipes, $cwd, $env);
        if (! is_resource($proc)) {
            throw new BackupException(
                $binary.' could not be started (proc_open failed — is it installed and on PATH?).',
            );
        }

        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdinDone = false;
        $stderrTail = '';
        $pending = '';  // partial non-blocking stdin write remainder
        $bytes = 0;     // source bytes fanned into the child
        $running = true;

        // One loop feeds stdin while draining stdout/stderr. feof() is
        // NEVER consulted on the non-blocking pipes: PHP can report EOF
        // after an empty non-blocking read while the child is still
        // writing, and closing a pipe early SIGPIPEs the child. EOF is
        // instead "no buffered data AND the child has exited".
        while (! $stdinDone || $running) {
            if (! $stdinDone) {
                if ($pending !== '') {
                    $written = fwrite($pipes[0], $pending);
                    if ($written === false) {
                        $this->abortProc($proc, $pipes);
                        throw new BackupException('could not write the restore stream to the child process stdin.');
                    }
                    $pending = substr($pending, $written);
                } elseif (feof($source)) {
                    $stdinDone = true;
                    fclose($pipes[0]);
                } else {
                    $chunk = fread($source, self::STDIN_CHUNK);
                    if ($chunk === false) {
                        $this->abortProc($proc, $pipes);
                        throw new BackupException('could not read the restore source stream.');
                    }
                    $pending = $chunk;
                    $bytes += strlen($chunk);
                }
            }

            $stdoutChunk = fread($pipes[1], self::DRAIN_CHUNK);
            if ($stdoutChunk === false) {
                $this->abortProc($proc, $pipes);
                throw new BackupException('could not drain the restore child stdout.');
            }
            // discarded — restore clients print nothing useful to stdout

            $stderrChunk = fread($pipes[2], 8192);
            if ($stderrChunk === false) {
                $this->abortProc($proc, $pipes);
                throw new BackupException('could not drain the restore child stderr.');
            }
            if ($stderrChunk !== '') {
                $stderrTail .= $stderrChunk;
                if (strlen($stderrTail) > self::STDERR_TAIL_BYTES) {
                    $stderrTail = substr($stderrTail, -self::STDERR_TAIL_BYTES);
                }
            }

            $status = proc_get_status($proc);
            $running = (bool) $status['running'];

            if ($running) {
                usleep(1000); // yield a beat so the child progresses
            }
        }

        // Child exited: drain whatever the OS pipe buffers still hold
        // before proc_close (discard stdout, append capped stderr tail).
        while (true) {
            $received = 0;
            $out = fread($pipes[1], self::DRAIN_CHUNK);
            if ($out !== false && $out !== '') {
                $received++;
            }
            $err = fread($pipes[2], 8192);
            if ($err !== false && $err !== '') {
                $received++;
                $stderrTail .= $err;
                if (strlen($stderrTail) > self::STDERR_TAIL_BYTES) {
                    $stderrTail = substr($stderrTail, -self::STDERR_TAIL_BYTES);
                }
            }
            if ($received === 0) {
                break;
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($proc);

        return ['exit' => $exit, 'stderr' => $stderrTail, 'bytes' => $bytes];
    }

    /**
     * The non-secret connection argument vector, mirroring the dumpers'
     * DatabaseTarget flag style but shaped for the CLIENT binaries:
     *  - mysql: [--socket= | --host=] [--port=] [--user=] --database=
     *    (mysql client accepts the database as an option; the socket
     *    wins over the host exactly like the dumper's connection opts);
     *  - pgsql: [--host=] [--port=] [--username=] --dbname=
     *    (psql's documented option form).
     * No charset/format sugar — keep minimal; pg_dump's plain-SQL slice
     * is already the psql dialect.
     *
     * @return list<string>
     */
    private function clientArgc(DatabaseTarget $target): array
    {
        $args = [];

        if ($target->driver === 'mysql') {
            if ($target->socket !== '') {
                $args[] = '--socket='.$target->socket;
            } elseif ($target->host !== '') {
                $args[] = '--host='.$target->host;
            }
            if ($target->port > 0) {
                $args[] = '--port='.$target->port;
            }
            if ($target->user !== '') {
                $args[] = '--user='.$target->user;
            }
            if ($target->database !== '') {
                $args[] = '--database='.$target->database;
            }

            return $args;
        }

        // pgsql
        if ($target->host !== '') {
            $args[] = '--host='.$target->host;
        }
        if ($target->port > 0) {
            $args[] = '--port='.$target->port;
        }
        if ($target->user !== '') {
            $args[] = '--username='.$target->user;
        }
        if ($target->database !== '') {
            $args[] = '--dbname='.$target->database;
        }

        return $args;
    }

    /**
     * Client binary + env password key for a driver. An unknown driver
     * fails LOUDLY here — restore never guesses a client.
     *
     * @return array{0: string, 1: string} [binary, passwordEnv]
     */
    private function clientFor(string $driver): array
    {
        if ($driver === 'mysql') {
            return ['mysql', 'MYSQL_PWD'];
        }

        if ($driver === 'pgsql') {
            return ['psql', 'PGPASSWORD'];
        }

        throw new BackupException("restore does not support database driver '{$driver}' (mysql|pgsql).");
    }

    /**
     * Source byte size for the injected-runner path (offline fakes are
     * process stand-ins that never see the bytes): fstat for streams,
     * filesize for paths, fallback 0. The built-in runner reports the
     * exact fanned-in byte count instead.
     *
     * @param  resource|string  $source
     * @param  resource  $handle
     */
    private function sourceBytes($source, $handle): int
    {
        if (is_resource($source)) {
            $stat = fstat($handle);
            if ($stat === false) {
                return 0;
            }

            $size = (int) $stat['size'];

            return $size > 0 ? $size : 0;
        }

        $bytes = filesize((string) $source);

        return $bytes === false ? 0 : $bytes;
    }

    /** Drain the closed child's remaining stdio on a stdin/write failure. */
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

    /** The last 4 KiB of stderr (mirror §3 cap), byte-safe. */
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
