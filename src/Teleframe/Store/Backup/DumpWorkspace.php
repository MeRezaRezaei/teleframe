<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * DumpWorkspace (remainder design §2/§3) — the vault-scope spool surface
 * for one dump run: a 0700 directory (created on first use), tempnam
 * spool files (prefix 'tfbk-', 0600), and a pending-file ledger whose
 * release() owns cleanup.
 *
 * Lifecycle:
 *  - spool(): create the workspace dir (0700, recursive when needed),
 *    tempnam a fresh spool file, open it for writing, register it in the
 *    pending ledger, and hand back {path, handle} for the dumper's fan-out.
 *  - track(): register an EXTERNALLY created spool path (injected-runner
 *    fakes create their own tempnam file and track it) so release()
 *    still owns it.
 *  - release($keepQueued): $keepQueued=false unlinks every pending spool
 *    (inline runs / failures — a failed run owns no worker, so no tfbk-*
 *    leak); $keepQueued=true keeps the files on disk (a genuinely
 *    dispatched queue worker owns them — putFromJob unlinks on success)
 *    and just drops the ledger. Idempotent.
 *
 * Default dir: '' resolves to sys_get_temp_dir()/teleframe-dumps — the
 * config-free fallback. A non-empty $dir is used verbatim (trailing
 * separators trimmed), and that is where teleframe.store.backup.spool_dir
 * arrives: the provider passes it to DatabaseBackupService, which hands it
 * here, so a host can spool its dumps on its own volume instead of system temp
 * (design §3: "DumpWorkspace default dir = teleframe.store.backup.spool_dir"
 * — the DEFAULT, not the only source).
 *
 * The directory is NEVER resolved or created at construction: dir() is pure
 * string work and only spool() calls mkdir. A workspace is therefore
 * constructible with no container, no config and no filesystem touched
 * (standalone-smoke gate), and a spool_dir that cannot be made 0700 fails
 * loud at the first dump, not at wiring.
 *
 * Vault-scope: tempnam already creates 0600 files; the directory is
 * created 0700; the config-free path contains nothing secret, and
 * release(false) removes the spool contents after inline puts.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2
 * (DumpWorkspace row), §3 (vault-scope safety, lines 119-125), §4
 * (release semantics).
 */
final class DumpWorkspace
{
    /** @var list<array{path: string, handle: resource|null}> */
    private array $ledger = [];

    public function __construct(
        private readonly string $dir = '',
    ) {}

    /**
     * The spool directory (the configured one, else
     * sys_get_temp_dir()/teleframe-dumps). Created 0700 on first spool().
     */
    public function dir(): string
    {
        if ($this->dir !== '') {
            return rtrim($this->dir, '/\\');
        }

        return rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'teleframe-dumps';
    }

    /**
     * Create a fresh spool file in the workspace dir.
     *
     * @return array{path: string, handle: resource}
     *
     * @throws BackupException when the workspace dir cannot be created or
     *                         the spool file cannot be opened
     */
    public function spool(): array
    {
        $dir = $this->dir();

        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new BackupException('cannot create the dump workspace directory: '.$dir);
        }

        $path = tempnam($dir, 'tfbk-');
        if ($path === false) {
            throw new BackupException('cannot create a spool file in: '.$dir);
        }

        $handle = fopen($path, 'w+b');
        if ($handle === false) {
            throw new BackupException('cannot open the spool file for writing: '.$path);
        }

        $this->ledger[] = ['path' => $path, 'handle' => $handle];

        return ['path' => $path, 'handle' => $handle];
    }

    /**
     * Register an externally created spool path in the pending ledger so
     * release() owns its cleanup. Handles an already-tracked path by
     * ignoring it (idempotent).
     */
    public function track(string $path): void
    {
        foreach ($this->ledger as $entry) {
            if ($entry['path'] === $path) {
                return;
            }
        }

        $this->ledger[] = ['path' => $path, 'handle' => null];
    }

    /**
     * Own the spool files' lifecycle: $keepQueued=false unlinks every
     * pending spool (inline runs and failures — a failed run owns no
     * worker); $keepQueued=true keeps the files for the queue worker
     * (putFromJob unlinks on success) and only drops the ledger.
     * Idempotent: a second release() is a no-op.
     */
    public function release(bool $keepQueued): void
    {
        foreach ($this->ledger as $entry) {
            if ($entry['handle'] !== null && is_resource($entry['handle'])) {
                fclose($entry['handle']);
            }

            if (! $keepQueued) {
                @unlink($entry['path']);
            }
        }

        $this->ledger = [];
    }
}
