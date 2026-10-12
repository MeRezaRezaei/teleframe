<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * BackupException (remainder design §2) — ONE loud exception type for the
 * whole store-backup slice: a missing dump binary, a failed dump
 * (non-zero exit, carrying the last 4 KiB stderr tail in the message), an
 * unwritable spool, an unconfigured database driver, a non-admin
 * delete/prune (wave 2). Loud, never silent — the orchestrator logs at
 * error level (when a logger is bound) and rethrows, and the missed-dump
 * watchdog alert is exactly what a failed run must trigger.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2
 * (BackupException usage across the module), §3 ("loud, never silent").
 */
final class BackupException extends \RuntimeException {}
