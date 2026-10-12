<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * DatabaseTarget (remainder design §2/§3) — an immutable DB descriptor,
 * deliberately WITHOUT a password field: credentials are env-at-run only
 * (MYSQL_PWD / PGPASSWORD injected into the forked dump process by the
 * dumper via getenv), so the target itself can never leak a secret into
 * argv, manifests, index rows, queue payloads or logs (ps-safe).
 *
 * argc() is the driver-shaped NON-SECRET dump argument list:
 *  - mysql: --single-transaction --quick --no-tablespaces, connection
 *    opts (--host= / --socket= / --port= / --user=), the database name
 *    positionally, then the table whitelist positionally (canonical
 *    `mysqldump [OPTIONS] db [tbl ...]`);
 *  - pgsql: --format=plain (the ONLY slice format — plain SQL, nothing
 *    compressed/custom), connection opts (--host= --port= --username=),
 *    the database name positionally, then one --table= per whitelist
 *    entry (pg_dump's `-t table` pattern).
 * env() is the child-process env BASE — empty by design (the password
 * key is the dumper's job, read from the host environment at run time).
 *
 * Pure data: readonly ctor, no behaviour beyond argv/env shaping. Zero
 * regex — string concatenation only.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2
 * (DatabaseTarget row), §3 (ctor + ps-safe ruling).
 */
final class DatabaseTarget
{
    /**
     * @param  list<string>|null  $tables  optional table whitelist; null = whole database
     */
    public function __construct(
        public readonly string $driver,        // 'mysql' | 'pgsql'
        public readonly string $database,
        public readonly string $host = '127.0.0.1',
        public readonly int $port = 0,         // 0 = driver default
        public readonly string $user = '',
        public readonly string $socket = '',   // mysql unix socket override
        public readonly ?array $tables = null,
    ) {}

    /**
     * The non-secret argument vector for the dump binary: driver scope
     * flags, connection options, the database name, and (when present)
     * the table whitelist. NO password anywhere — never.
     *
     * @return list<string>
     */
    public function argc(): array
    {
        if ($this->driver === 'mysql') {
            return $this->mysqlArgc();
        }

        if ($this->driver === 'pgsql') {
            return $this->pgsqlArgc();
        }

        // Unknown driver: connection opts only, no scope flags — the
        // dumper seam rejects unconfigured drivers loudly before this is
        // ever handed to a process.
        $args = [];
        if ($this->host !== '') {
            $args[] = '--host='.$this->host;
        }

        return $args;
    }

    /**
     * The child-process env base for this driver. Deliberately EMPTY:
     * the password entry (MYSQL_PWD / PGPASSWORD) is the DUMPER's job —
     * read from the HOST environment via getenv() at run time, never
     * stored on the target, never in argv, manifests, rows or logs.
     *
     * @return array<string, string>
     */
    public function env(): array
    {
        return [];
    }

    /** @return list<string> */
    private function mysqlArgc(): array
    {
        $args = [
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
        ];

        if ($this->socket !== '') {
            $args[] = '--socket='.$this->socket;
        } elseif ($this->host !== '') {
            $args[] = '--host='.$this->host;
        }

        if ($this->port > 0) {
            $args[] = '--port='.$this->port;
        }

        if ($this->user !== '') {
            $args[] = '--user='.$this->user;
        }

        if ($this->database !== '') {
            $args[] = $this->database;
        }

        foreach ($this->tables ?? [] as $table) {
            $args[] = (string) $table;
        }

        return $args;
    }

    /** @return list<string> */
    private function pgsqlArgc(): array
    {
        $args = ['--format=plain'];

        if ($this->host !== '') {
            $args[] = '--host='.$this->host;
        }

        if ($this->port > 0) {
            $args[] = '--port='.$this->port;
        }

        if ($this->user !== '') {
            $args[] = '--username='.$this->user;
        }

        if ($this->database !== '') {
            $args[] = $this->database;
        }

        foreach ($this->tables ?? [] as $table) {
            $args[] = '--table='.(string) $table;
        }

        return $args;
    }
}
