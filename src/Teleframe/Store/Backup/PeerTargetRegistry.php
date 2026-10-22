<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

use Closure;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;

/**
 * PeerTargetRegistry (remainder design §2/§6/§8, cycle 11 wave 2) — the
 * config-facing side of backup targets: config target rows
 * ({bucket, peer_id|peerId, access_hash|accessHash, peer_type|peerType,
 * mode, passphrase, members}) → an immutable list of PeerTarget value
 * objects, plus the dialog-walk classification seam (channel vs
 * supergroup) used when a fresh login must rediscover targets by chat
 * shape (design §6: "ensureRegistry becomes best-effort … classification
 * falls back to title + config target rows").
 *
 * RULING (invalid rows): a row without a bucket or a peer id — or with an
 * unparseable peer_type / mode / access_hash / members — is a CONFIG
 * ERROR. targets() throws BackupException LOUDLY on the first bad row,
 * never silently drops: a registry that cannot be fully mapped is not a
 * registry you can trust to index. byBucket() walks the same rows and
 * throws identically.
 *
 * RULING (classification): channels AND supergroups both arrive as
 * {_:'channel'} chat objects; the megagroup flag separates them —
 * {_:'channel', megagroup: true} → 'group', broadcast channels → 'channel',
 * everything else (any other/unknown type) → 'channel' (the default). An
 * injectable $classify callable (tests) replaces the built-in string
 * checks; both built-in and injected paths are zero-regex.
 *
 * Plain data + one pure transform — no container calls, no logger.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2
 * (PeerTargetRegistry row), §6 (channel/group target RULINGS table),
 * §8 (wave 2 — cycle 11 wave 2-A).
 */
final class PeerTargetRegistry
{
    /**
     * @param  list<array<string, mixed>|null>  $rows  config target rows (a null
     *                                                 entry = malformed config;
     *                                                 targets() throws loudly)
     * @param  callable(array<string, mixed>): string|null  $classify  injected
     *                                                                 chat → 'channel'|'group' (tests)
     */
    public function __construct(
        private readonly array $rows = [],
        ?callable $classify = null,
    ) {
        // Properties cannot carry the callable type in PHP — the repo
        // precedent (MysqlDumper/PostgresDumper/DatabaseRestorer) stores
        // callable ctor params as ?\Closure via Closure::fromCallable().
        $this->classify = $classify !== null ? Closure::fromCallable($classify) : null;
    }

    private readonly ?Closure $classify;

    /**
     * Build a registry straight from the shipped config rows.
     *
     * @param  list<array<string, mixed>|null>  $rows
     */
    public static function fromConfig(array $rows): self
    {
        return new self($rows);
    }

    /**
     * Map every config row to a PeerTarget. THROWS BackupException on the
     * first invalid row (missing bucket / peer id, non-numeric access
     * hash, bad peer_type, bad mode, non-array members, non-string
     * passphrase) — loud, never silently dropped.
     *
     * @return list<PeerTarget>
     */
    public function targets(): array
    {
        $targets = [];
        foreach ($this->rows as $index => $row) {
            if (! is_array($row)) {
                throw new BackupException("peer target row #{$index} is not an array");
            }
            $targets[] = $this->targetFromRow($row, $index);
        }

        return $targets;
    }

    /** First target mapped for the bucket, or null when no row maps to it. */
    public function byBucket(string $bucket): ?PeerTarget
    {
        foreach ($this->targets() as $target) {
            if ($target->bucket === $bucket) {
                return $target;
            }
        }

        return null;
    }

    /**
     * Classify one chat object as 'channel' | 'group'. Uses the injected
     * $classify callable when provided (tests); otherwise the built-in
     * zero-regex checks: megagroup-flagged channels → 'group'; broadcast
     * channels → 'channel'; any unknown/other type → 'channel' (default).
     *
     * @param  array<string, mixed>  $chat  MTProto chat/channel object
     */
    public function classifyPeerType(array $chat): string
    {
        if ($this->classify !== null) {
            return (string) ($this->classify)($chat);
        }

        $type = $chat['_'] ?? '';
        if (! is_string($type) || $type !== 'channel') {
            return 'channel';
        }

        return ! empty($chat['megagroup']) ? 'group' : 'channel';
    }

    /**
     * @param  array<string, mixed>  $row
     *
     * @throws BackupException on any invalid row
     */
    private function targetFromRow(array $row, int $index): PeerTarget
    {
        $bucket = $row['bucket'] ?? null;
        if (! is_string($bucket) || $bucket === '') {
            throw new BackupException("peer target row #{$index} has no bucket");
        }

        $peerId = $row['peer_id'] ?? $row['peerId'] ?? null;
        if (! is_int($peerId) && ! (is_string($peerId) && $peerId !== '')) {
            throw new BackupException("peer target row #{$index} ({$bucket}) has no usable peer id");
        }

        $accessHash = $row['access_hash'] ?? $row['accessHash'] ?? null;
        if ($accessHash === null) {
            $accessHash = null;
        } elseif (is_int($accessHash) || (is_string($accessHash) && is_numeric($accessHash))) {
            $accessHash = (int) $accessHash;
        } else {
            throw new BackupException("peer target row #{$index} ({$bucket}) has a non-numeric access hash");
        }

        $peerType = $row['peer_type'] ?? $row['peerType'] ?? 'channel';
        if (! is_string($peerType) || ! in_array($peerType, ['channel', 'group'], true)) {
            throw new BackupException("peer target row #{$index} ({$bucket}) has an invalid peer type");
        }

        $mode = $row['mode'] ?? ObjectManifest::MODE_SEALED;
        if (! is_string($mode) || ! in_array($mode, [ObjectManifest::MODE_RAW, ObjectManifest::MODE_SEALED], true)) {
            throw new BackupException("peer target row #{$index} ({$bucket}) has an invalid mode");
        }

        $passphrase = $row['passphrase'] ?? null;
        if ($passphrase !== null && ! is_string($passphrase)) {
            throw new BackupException("peer target row #{$index} ({$bucket}) has a non-string passphrase");
        }

        $members = $row['members'] ?? [];
        if (! is_array($members)) {
            throw new BackupException("peer target row #{$index} ({$bucket}) has non-array members");
        }

        return new PeerTarget(
            bucket: $bucket,
            peerId: $peerId,
            accessHash: $accessHash,
            peerType: $peerType,
            mode: $mode,
            passphrase: $passphrase,
            memberAccounts: array_values($members),
        );
    }
}
