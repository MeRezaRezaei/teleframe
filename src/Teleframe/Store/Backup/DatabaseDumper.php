<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * DatabaseDumper (remainder design §2/§3) — the dump contract: spool a
 * consistent snapshot of one database to a SEEKABLE file, never a live
 * process pipe. The existing put path fseeks its source (ChunkPlanner
 * requires seekability) and reads each chunk at least twice, so the dump
 * process streams into a vault-scope spool file on disk (0800-less,
 * tempnam 0600) and the EXISTING ChunkPlanner→StreamSealer path uploads
 * from that file. Peak RAM stays at one 512 KiB part (S8 discipline).
 *
 * Implementations run the dump binary through an injectable process
 * runner (offline tests) or the built-in raw proc_open (production):
 * callable(array $argv, array $env, string $cwd): array{exit: int,
 * stderr: string}. Arguments are NON-SECRET (DatabaseTarget::argc());
 * credentials are env-at-run only (MYSQL_PWD / PGPASSWORD merged by the
 * dumper from the host environment — never argv, never the target).
 *
 * @see DatabaseTarget
 * @see DumpWorkspace
 * @see DumpResult
 */
interface DatabaseDumper
{
    /**
     * Spool one consistent snapshot of $target into a fresh
     * $workspace spool file.
     *
     * @throws BackupException when the dump binary is absent, the dump
     *                         fails (non-zero exit), or the spool cannot
     *                         be written
     */
    public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult;
}
