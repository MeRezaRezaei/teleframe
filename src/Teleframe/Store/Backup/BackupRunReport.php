<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * One backup run's truth (remainder design §2, run-ledger row): the
 * ledger-facing summary of a dump → put cycle. bucket/name/driver/database
 * identify the run; bytes/chunks/sha256/mode describe the stored object as
 * OBSERVED from the put result; heartbeatAt is the run's observed heartbeat
 * (unix); queued distinguishes a dispatched async put from an inline upload.
 *
 * Provenance: docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md
 * (§2 BackupRunReport row, §4 chain, §6 index rows) — the verbatim's "only a
 * matter of managing indexes". Pure data: readonly ctor, toArray(), and
 * fromPut() which maps a StoreService::put() result (its actual return
 * shapes: ['queued' => true, 'job' => jobId] on dispatch, or
 * ['queued' => false, 'object' => row, 'manifest' => manifestArray] on
 * inline upload — a queued dispatch cannot observe bytes/chunks/sha256 yet,
 * so those map to zero/empty; the orchestrator fills identity fields
 * (bucket/driver/database/mode) via the with* withers).
 *
 * Zero regex — pure reads and string concatenation only.
 */
final class BackupRunReport
{
    public function __construct(
        public readonly string $bucket,
        public readonly string $name,
        public readonly string $driver,
        public readonly string $database,
        public readonly int $bytes,
        public readonly int $chunks,
        public readonly string $sha256,
        public readonly string $mode,
        public readonly ?int $heartbeatAt,
        public readonly bool $queued,
    ) {}

    /**
     * Map a StoreService::put() result (observed truth) + the object name
     * the run was stored under. Inline uploads read size/sha256 from the
     * manifest, chunk count + mode from the object row, and the heartbeat
     * from the object's created_at_unix. Dispatched (queued) puts cannot
     * observe any of that yet — bytes/chunks/sha256/mode stay empty and
     * heartbeatAt null (the worker records the upload truth on replay).
     *
     * @param  array<string, mixed>  $putResult  StoreService::put() return value
     */
    public static function fromPut(string $objectName, array $putResult): self
    {
        if ((bool) ($putResult['queued'] ?? false)) {
            return new self('', $objectName, '', '', 0, 0, '', '', null, true);
        }

        $object = $putResult['object'] ?? [];
        $manifest = $putResult['manifest'] ?? [];
        $object = is_array($object) ? $object : [];
        $manifest = is_array($manifest) ? $manifest : [];

        $chunks = $manifest['chunks'] ?? $object['chunks'] ?? [];

        return new self(
            '',
            $objectName,
            '',
            '',
            (int) ($manifest['size'] ?? $object['size'] ?? 0),
            is_array($chunks) ? count($chunks) : 0,
            (string) ($manifest['sha256'] ?? ''),
            (string) ($object['mode'] ?? $manifest['mode'] ?? ''),
            isset($object['created_at_unix']) ? (int) $object['created_at_unix'] : null,
            false,
        );
    }

    public function withBucket(string $bucket): self
    {
        return new self($bucket, $this->name, $this->driver, $this->database, $this->bytes, $this->chunks, $this->sha256, $this->mode, $this->heartbeatAt, $this->queued);
    }

    public function withDriver(string $driver): self
    {
        return new self($this->bucket, $this->name, $driver, $this->database, $this->bytes, $this->chunks, $this->sha256, $this->mode, $this->heartbeatAt, $this->queued);
    }

    public function withDatabase(string $database): self
    {
        return new self($this->bucket, $this->name, $this->driver, $database, $this->bytes, $this->chunks, $this->sha256, $this->mode, $this->heartbeatAt, $this->queued);
    }

    public function withMode(string $mode): self
    {
        return new self($this->bucket, $this->name, $this->driver, $this->database, $this->bytes, $this->chunks, $this->sha256, $mode, $this->heartbeatAt, $this->queued);
    }

    public function withHeartbeat(?int $heartbeatAt): self
    {
        return new self($this->bucket, $this->name, $this->driver, $this->database, $this->bytes, $this->chunks, $this->sha256, $this->mode, $heartbeatAt, $this->queued);
    }

    /** @return array{bucket: string, name: string, driver: string, database: string, bytes: int, chunks: int, sha256: string, mode: string, heartbeatAt: ?int, queued: bool} */
    public function toArray(): array
    {
        return [
            'bucket' => $this->bucket,
            'name' => $this->name,
            'driver' => $this->driver,
            'database' => $this->database,
            'bytes' => $this->bytes,
            'chunks' => $this->chunks,
            'sha256' => $this->sha256,
            'mode' => $this->mode,
            'heartbeatAt' => $this->heartbeatAt,
            'queued' => $this->queued,
        ];
    }
}
