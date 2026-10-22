<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use InvalidArgumentException;
use LogicException;
use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use MeRezaRezaei\Teleframe\Store\Cache\HotCacheContract;
use MeRezaRezaei\Teleframe\Store\Index\StoreIndexContract;
use MeRezaRezaei\Teleframe\Store\Queue\UploadJob;
use MeRezaRezaei\Teleframe\Store\Queue\UploadQueueContract;
use RuntimeException;

/**
 * TStore orchestrator (plan W6, verbatim S2-S11): the account-scoped
 * fan-in that puts objects onto Telegram, fetches them back, and keeps
 * the tf_store_* truth tables + hot cache coherent. One instance = one
 * account; every port is injected, so the same class runs against a live
 * scope (Laravel wiring, W7), the standalone smoke, and offline tests
 * (fakes for every port).
 *
 * Memory discipline: put() streams a chunk through its 512 KiB parts
 * (never a full chunk in RAM; sealed chunks are sealed part-by-part and
 * the wire is regenerated deterministically for upload). get() buffers
 * chunk-at-a-time (the channel's fetch contract) and holds the whole
 * object only because the facade returns a string — the streaming read
 * face belongs to the facade wave.
 *
 * THE COLD POSTURE (V1.2, 2026-09-27): a host that wants minio semantics —
 * "delete it locally, it's still in Telegram" — constructs this service with
 * ReferencePolicy::coldRemoteAuthoritative() and every path below stops
 * treating a local truth row as a PRECONDITION. The local rows become a cache
 * the remote tier is asked to refill, so a copy whose database was rebuilt on
 * another machine still reads, still re-publishes by hash, and can still be
 * deleted. The DEFAULT stays ReferencePolicy::retainLocal(): inert, never
 * touching a disk, byte-for-byte what it always was (see ReferencePolicy's
 * docblock for why the default is not flipped).
 */
final class StoreService
{
    /** @var callable(string): TelegramStoreChannel */
    private $channelFactory;

    /** @var callable(): string */
    private $saltProvider;

    /** @var callable(string, string): string passphrase + saltHex => 32-byte raw key */
    private $keyDeriver;

    /** @var callable(): int */
    private $now;

    /** @var callable(): list<array{id: int, access_hash: int, title: string}>|null */
    private $dialogLister;

    private readonly ReferencePolicy $referencePolicy;

    private readonly RemoteTierContract $tier;

    /**
     * @param  callable(string): TelegramStoreChannel  $channelFactory  bucket => channel
     * @param  callable(string): string|null  $saltProvider  raw 16-byte salt (default random_bytes)
     * @param  callable(string, string): string|null  $keyDeriver  passphrase + saltHex => 32-byte key (default VaultCrypto Argon2id)
     * @param  callable(): int|null  $now  unix clock (default time)
     * @param  callable(): list<array{id: int, access_hash: int, title: string}>|null  $dialogLister  all dialogs (syncRegistry; W7 wires the real getDialogs walk)
     * @param  ReferencePolicy|null  $referencePolicy  the store's standing reclaim posture.
     *                                                 null = ReferencePolicy::retainLocal() — the shipped default, which never frees a
     *                                                 local file and never treats a missing local row as licence to consult the remote
     *                                                 tier. Pass coldRemoteAuthoritative() for the bucket-storage posture.
     * @param  RemoteTierContract|null  $tier  the remote resolver. Injected so a test decides the cold
     *                                         branch through the seam rather than through whatever this host can reach; defaults
     *                                         to the real resolver, which itself only ever talks to the injected channel.
     */
    public function __construct(
        private readonly int $accountId,
        callable $channelFactory,
        private readonly StoreIndexContract $index,
        private readonly HotCacheContract $cache,
        private readonly UploadQueueContract $queue,
        private readonly bool $allowCachedPlaintext = false,
        private readonly bool $premium = false,
        ?callable $saltProvider = null,
        ?callable $keyDeriver = null,
        ?callable $now = null,
        ?callable $dialogLister = null,
        ?ReferencePolicy $referencePolicy = null,
        ?RemoteTierContract $tier = null,
    ) {
        $this->channelFactory = $channelFactory;
        $this->saltProvider = $saltProvider ?? static fn (): string => random_bytes(16);
        $this->keyDeriver = $keyDeriver ?? static fn (string $passphrase, string $saltHex): string => VaultCrypto::deriveKey(
            $passphrase,
            hex2bin($saltHex),
        );
        $this->now = $now ?? static fn (): int => time();
        $this->dialogLister = $dialogLister;
        $this->referencePolicy = $referencePolicy ?? ReferencePolicy::retainLocal();
        $this->tier = $tier ?? new TelegramTier;
    }

    /**
     * The standing posture this service was constructed with. Reclaim callers
     * that pass their own policy per call override it; everything else reads it.
     */
    public function referencePolicy(): ReferencePolicy
    {
        return $this->referencePolicy;
    }

    /**
     * Store an object. $source is a local path, raw bytes, or a seekable
     * stream. Sealed mode requires a passphrase (per-object salt, key via
     * the injected deriver — Argon2id by default).
     *
     * $queued hands the upload to the queue when it is async (RabbitMQ);
     * the sync driver runs the upload inline regardless. A queued put
     * requires a re-readable source: paths pass through, bytes/streams are
     * materialized to a temp file the worker owns (unlinked after use).
     *
     * Returns ['queued' => true, 'job' => jobId] when dispatched, or
     * ['queued' => false, 'object' => row, 'manifest' => manifestArray]
     * when uploaded inline.
     *
     * @param  resource|string  $source
     * @return array{queued: bool, job?: string, object?: array<string, mixed>, manifest?: array<string, mixed>}
     */
    public function put(
        string $bucket,
        string $name,
        $source,
        string $mode = ObjectManifest::MODE_RAW,
        ?string $passphrase = null,
        string $mime = 'application/octet-stream',
        bool $queued = false,
    ): array {
        if ($name === '') {
            throw new InvalidArgumentException('object name must be non-empty');
        }
        if (! in_array($mode, [ObjectManifest::MODE_RAW, ObjectManifest::MODE_SEALED], true)) {
            throw new InvalidArgumentException("mode must be raw|sealed, got {$mode}");
        }
        if ($mode === ObjectManifest::MODE_SEALED && ($passphrase === null || $passphrase === '')) {
            throw new InvalidArgumentException('sealed objects require a passphrase');
        }

        $this->assertTargetSealPolicy($bucket, $mode);

        [$handle, $size] = $this->resolveSource($source);
        $closeHandle = ! is_resource($source); // caller-owned streams stay open

        try {
            if ($queued && $this->queue->isAsync()) {
                $job = $this->buildJob($bucket, $name, $mode, $passphrase, $source, $size);

                try {
                    $jobId = $this->queue->enqueue($job->toArray());
                } catch (\Throwable $e) {
                    // The worker never receives this job: drop the temp file
                    // the source was materialized into (never a caller-supplied
                    // path), then rethrow — cleanup never swallows.
                    $this->discardJobSource($job, $source);
                    throw $e;
                }

                return ['queued' => true, 'job' => $jobId];
            }

            $result = $this->uploadInline($bucket, $name, $mode, $passphrase, $mime, $size, $handle);

            return ['queued' => false, 'object' => $result['object'], 'manifest' => $result['manifest']];
        } finally {
            if ($closeHandle) {
                fclose($handle);
            }
        }
    }

    /**
     * Replay one queued upload job (worker path; the temp file the job
     * points at is unlinked after a successful upload).
     *
     * @return array<string, mixed>
     */
    public function putFromJob(array $payload): array
    {
        $job = UploadJob::fromArray($payload);
        if (! is_file($job->sourcePath)) {
            throw new RuntimeException('upload job source is not a readable file: '.$job->sourcePath);
        }

        // The queued path is the SAME upload and must honour the same seal
        // policy: a job enqueued before a target was configured must not
        // post readable plaintext into a shared group because the enqueue
        // was accepted.
        $this->assertTargetSealPolicy($job->bucket, $job->mode);

        $handle = fopen($job->sourcePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('cannot open upload job source: '.$job->sourcePath);
        }
        try {
            $result = $this->uploadInline(
                $job->bucket,
                $job->name,
                $job->mode,
                $job->passphrase,
                'application/octet-stream',
                $job->size,
                $handle,
            );
        } finally {
            fclose($handle);
        }

        // The temp file is removed ONLY after the upload returned. It used to
        // be unlinked from a `finally`, which combined with the queue's
        // ack-in-`finally` to make a transient upload failure total: the
        // message was acked and the only copy of the bytes was deleted, so a
        // retry had nothing left to send. The caller now requeues on throw, and
        // that requeue is only worth anything while the source still exists.
        @unlink($job->sourcePath);

        return $result['object'];
    }

    /**
     * Fetch an object back as bytes. Raw mode verifies each chunk against
     * the manifest's plaintext sha256; sealed mode verifies the wire
     * (ciphertext) sha256, decrypts part-by-part, and verifies the
     * plaintext sha256 too. Cached plaintext chunks are served when the
     * object is raw, or when allowCachedPlaintext is on, or when a
     * passphrase is supplied (sealed). Cache misses fall through to the
     * channel, verify, and populate.
     *
     * The assembled bytes are THEN compared against the manifest's
     * whole-object sha256. Per-chunk checks alone are not enough: they
     * read their expectations from the (editable) manifest itself, so a
     * whole-object hash that is computed and never compared is decoration
     * — a dropped final chunk or a substituted chunk set otherwise
     * returns "success" over the wrong bytes.
     *
     * @throws RuntimeException when the object is missing or any verification fails
     */
    public function get(string $bucket, string $name, ?string $passphrase = null): string
    {
        [$manifest, $peer] = $this->manifestSourceFor($bucket, $name);
        $key = $this->keyFor($manifest, $passphrase);
        $this->assertManifestAuthenticated($manifest, $key);

        $wholeCtx = hash_init('sha256');
        $plain = '';
        foreach ($manifest->chunks() as $chunk) {
            $bytes = $this->fetchChunkPlain($bucket, $manifest, $chunk, $key, $peer);
            hash_update($wholeCtx, $bytes);
            $plain .= $bytes;
        }

        $this->assertAssembledHash($manifest, hash_final($wholeCtx), strlen($plain));

        return $plain;
    }

    /**
     * Whether the object is readable. A local row answers it for free; in the
     * COLD posture a MISS is not an answer, so the remote tier is asked before
     * "no" is reported. That inversion is the bucket semantics the user
     * described: "delete it locally, it's still in Telegram" has to mean
     * exists() stays true after the row and the file are both gone.
     */
    public function exists(string $bucket, string $name): bool
    {
        if ($this->index->findObject($this->accountId, $bucket, $name) !== null) {
            return true;
        }

        return $this->remoteManifestFor($bucket, $name) !== null;
    }

    /** Registered buckets for this account (registry truth rows). */
    public function buckets(): array
    {
        return $this->index->listBuckets($this->accountId);
    }

    /**
     * Objects in one bucket — the LOCAL projection (truth rows, newest first).
     *
     * Deliberately not a remote walk: this is the cheap list every page render
     * and every caller loops over, and turning it into a full history crawl
     * would make a UI a network round trip per keystroke. The cold twin is
     * listRemoteObjects(), which is explicit about costing a walk.
     *
     * @return list<array<string, mixed>>
     */
    public function objects(string $bucket): array
    {
        return $this->index->listObjects($this->accountId, $bucket);
    }

    /**
     * Objects in one bucket according to the REMOTE tier, for a host whose
     * local index has not caught up (or was rebuilt elsewhere). Same shape as
     * listObjects()'s rows plus `msg_id` and `peer`, and it costs a marker
     * search per read peer — a walk, not a lookup.
     *
     * @return list<array{name: string, size: int, sha256: string, mode: string, mime: string, created: string, msg_id: int, peer: array<string, mixed>}>
     */
    public function listRemoteObjects(string $bucket, int $limit = 100): array
    {
        return $this->tier->listObjects(($this->channelFactory)($bucket), $limit);
    }

    /**
     * Object size in bytes, or null when the object is unknown. Like
     * exists(), a local miss is only an answer in the retaining postures.
     */
    public function size(string $bucket, string $name): ?int
    {
        $row = $this->index->findObject($this->accountId, $bucket, $name);
        if ($row !== null) {
            return (int) ($row['size'] ?? 0);
        }

        $manifest = $this->remoteManifestFor($bucket, $name);

        return $manifest === null ? null : $manifest->size();
    }

    /**
     * Delete an object from Telegram and the truth tables: removes every
     * chunk document + the TFSTORE1 metadata message, drops the index row
     * and all cached chunks. Idempotent (missing messages delete nothing).
     *
     * The revoke is addressed to the peer the object's metadata message was
     * found in — with a target configured that is NOT necessarily the
     * current destination, and revoking against the wrong peer would leave
     * the messages in place while reporting success.
     *
     * COLD: with no local object row the cold posture resolves the manifest
     * from the remote tier, so "remove what is no longer requested" still
     * works on a host whose index is empty. Without that posture a missing
     * row stays a no-op, exactly as before: inventing a revoke out of a
     * missing row is a delete this module was never asked to make.
     */
    public function delete(string $bucket, string $name): void
    {
        $channel = ($this->channelFactory)($bucket);
        $row = $this->index->findObject($this->accountId, $bucket, $name);
        $peer = null;

        if ($row !== null) {
            $manifest = ObjectManifest::fromArray($row['metadata']);
            $peer = $this->owningPeer($channel, $name);
        } else {
            $found = $this->referencePolicy->trustsRemote()
                ? $this->tier->manifestFor($channel, $name)
                : null;
            if ($found === null) {
                return;
            }
            $manifest = $found['manifest'];
            $peer = $found['peer'];
        }

        $ids = [];
        $ids = array_merge($ids, $this->messageIdsFor($channel, MetadataMessage::MARKER.' '.$name, $peer, $name, true));
        foreach ($manifest->chunks() as $chunk) {
            $docName = $manifest->chunkDocName($chunk['n']);
            $ids = array_merge($ids, $this->messageIdsFor($channel, $docName, $peer, $docName, false));
        }
        $channel->deleteMessagesIn($peer, array_values(array_unique($ids)));

        $this->cache->forget($bucket, $manifest->sha256());
        $this->index->deleteObject($this->accountId, $bucket, $name);
    }

    /**
     * Walk ALL dialogs (injected lister, W7 wires the scope's getDialogs
     * full-cursor pagination), classify titles into store/data/backup
     * buckets, upsert the tf_store_buckets truth rows, and ensure each
     * store-kind bucket carries its TFREGISTRY1 message.
     *
     * @param  list<string>  $configuredBuckets  bucket names this install manages
     * @return array{walked: int, upserted: int}
     */
    public function syncRegistry(array $configuredBuckets): array
    {
        if ($this->dialogLister === null) {
            throw new LogicException('syncRegistry requires a dialog lister (wire the scope getDialogs walk in W7)');
        }

        $dialogs = ($this->dialogLister)();
        $walked = count($dialogs);
        $upserted = 0;
        $seen = [];

        foreach ($dialogs as $dialog) {
            $title = (string) $dialog['title'];
            $classified = ChannelRegistry::classifyTitle($title);
            if ($classified === null) {
                continue;
            }
            $seen[$classified['bucket']] = true;
            $this->upsertBucketRow($classified['bucket'], $classified['kind'], (int) $dialog['id'], (int) $dialog['access_hash']);
            $upserted++;
            if ($classified['kind'] === ChannelRegistry::KIND_STORE) {
                ($this->channelFactory)($classified['bucket'])->ensureRegistry();
            }
        }

        foreach ($configuredBuckets as $bucket) {
            if (isset($seen[$bucket])) {
                continue;
            }
            $channel = ($this->channelFactory)($bucket);
            $peer = $channel->inputPeer();
            $this->upsertBucketRow($bucket, ChannelRegistry::KIND_STORE, (int) $peer['channel_id'], (int) $peer['access_hash']);
            $channel->ensureRegistry();
            $upserted++;
        }

        return ['walked' => $walked, 'upserted' => $upserted];
    }

    /**
     * Walk one DATA channel's full history and index every document into
     * tf_store_media (S3): Telegram-native doc identity + sizes/mime, with
     * the content hash left lazy. Returns the number of media rows
     * upserted.
     */
    public function indexDataChannel(string $dataBucket): int
    {
        $channel = ($this->channelFactory)($dataBucket);
        $channel->inputPeer();

        $count = 0;
        foreach ($channel->listAllRows() as $row) {
            $document = $row['media']['document'] ?? null;
            if (! is_array($document) || ($document['_'] ?? '') !== 'document') {
                continue;
            }
            $msgId = (int) ($row['id'] ?? 0);
            if ($msgId <= 0) {
                continue;
            }
            $this->index->upsertMedia($this->accountId, $dataBucket, $msgId, [
                'media_type' => MediaType::Document->value,
                'doc_id' => (int) ($document['id'] ?? 0),
                'access_hash' => (int) ($document['access_hash'] ?? 0),
                'file_reference' => (string) ($document['file_reference'] ?? ''),
                'size' => (int) ($document['size'] ?? 0),
                'mime' => (string) ($document['mime_type'] ?? 'application/octet-stream'),
                'content_sha256' => null,
                'indexed_at_unix' => ($this->now)(),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Re-publish an already-stored file to another peer by its Telegram
     * hash (Cycle 14, verbatim 2026-09-24: "for posting them in our public
     * channel we just pass the hash telegram gives"): resolve the
     * tf_store_media row for ($bucket, $msgId) and hand the file_reference
     * straight to the wire as inputMediaDocument. No download, no upload,
     * no temp file — the hash row plus the Telegram copy are the reference,
     * which is what makes local reclamation possible later.
     *
     * A row that is not indexed fails LOUDLY: silently re-uploading is
     * exactly the cost this feature exists to remove, so it must never be
     * an invisible fallback.
     *
     * COLD: in the cold posture a missing tf_store_media row is re-resolved
     * from the remote tier — the message id still addresses a document
     * Telegram holds — and the row is written back so the next call is a
     * local hit. The upload count stays ZERO either way; what changes is
     * whether the hash row is a prerequisite or a cache. Outside that posture
     * a missing row still throws, unchanged.
     *
     * @param  array<string, mixed>  $targetPeer  inputPeer of the target channel/group
     *
     * @throws RuntimeException when the message has no tf_store_media row for this account
     */
    public function publishByReference(string $bucket, int $msgId, array $targetPeer, string $caption = ''): int
    {
        if ($msgId <= 0) {
            throw new InvalidArgumentException('msg id must be positive');
        }

        $row = $this->index->findMedia($this->accountId, $bucket, $msgId);
        if ($row === null) {
            $row = $this->remoteMediaRow($bucket, $msgId);
        }
        if ($row === null) {
            throw new RuntimeException(
                "tf_store_media has no reference for bucket {$bucket} message {$msgId} on account {$this->accountId}; index it first (indexDataChannel/archiveMessageMedia)",
            );
        }

        return ($this->channelFactory)($bucket)->sendReference(
            $targetPeer,
            MediaReference::fromIndexRow($row),
            $caption,
        );
    }

    /**
     * Free the LOCAL Laravel file once Telegram's hash row is durable
     * (Cycle 16 W2, verbatim 2026-09-24: "this kind is very important it
     * helps keeping storage of laravel free … since we delete something in
     * main laravel storage but its hash in telegram is still in database
     * and the file is in the channel we can store big data and keep our hot
     * data and cold data intact").
     *
     * THE ORDERING IS THE WHOLE FEATURE and is enforced here, not left to the
     * caller: the reference is proven BEFORE the deleter is reachable. Deleting
     * first and proving second would be silent data loss.
     *
     * ── WHAT THE PROOF IS (2026-09-27, rewritten) ────────────────────────
     * It used to be REFERENCE-shaped: a non-empty file_reference plus a
     * positive doc_id, i.e. "document 777 is addressable". That is exactly what
     * publishByReference() needs and it is NOT what makes deleting a file safe.
     * It never mentioned the file. Three holes followed from the gap between
     * those two sentences, and all three deleted host data:
     *
     *   1. a row's content_sha256 was written null and read NOWHERE, so nothing
     *      bound $path to the row — the delete proceeded on unrelated bytes;
     *   2. findMedia() was consulted FIRST and short-circuited the remote, so a
     *      cached row kept proving durability after the document was revoked —
     *      a cache is a performance fact, never a proof;
     *   3. a tf_store_media row is authored by whoever can post in a declared
     *      DATA channel (archiveMessageMedia), so "a local row wins" — true for
     *      tf_store_objects — was false here.
     *
     * The proof is now a CONJUNCTION, and each clause is checked by this class
     * rather than asserted by the caller:
     *
     *   (a) IDENTITY    $expect must NAME an object — bucket + msg_id. A bare
     *                   row is a claim about bytes, not about an object, and it
     *                   is no longer accepted on its own.
     *   (b) ADDRESSABILITY  the RESOLVED row (never the caller's copy of it)
     *                   must carry a file_reference, a positive doc_id and a
     *                   positive access_hash.
     *   (c) FRESHNESS   under the cold posture the remote tier is asked about
     *                   this exact message id on THIS call, and its answer — not
     *                   the cached row — is the reference. A cached row is
     *                   written back for speed and read by nothing here.
     *   (d) CONTENT     the bytes at $path are hashed and compared with the
     *                   sha256 of the document Telegram actually holds. Under
     *                   the cold posture that hash is computed HERE, from a
     *                   fresh download, so the binding is established by this
     *                   method and not by anybody who posted a message.
     *
     * Clause (d) is also the provenance fix: a row a stranger authored carries
     * content_sha256 = null (archiveMessageMedia writes null and cannot be made
     * to write otherwise), so it can never satisfy a content proof on its own.
     * A stranger can still NOMINATE a message id; they cannot make the host's
     * bytes match their document.
     *
     * ── WHAT THE PROOF IS NOT, AND WHERE IT STOPS ───────────────────────
     * TOCTOU, honestly, and in three parts rather than one:
     *
     *   - LOCAL swap, before the deleter: CLOSED. This method hashes $path
     *     AFTER the (slow) remote consult and again immediately before the
     *     deleter, so a file replaced while the proof is being gathered, or
     *     between the two reads, is caught and refused. A test in
     *     ReclaimDurabilityAttackTest swaps the file inside the history walk
     *     and watches it go red.
     *   - LOCAL swap, INSIDE the deleter: NOT CLOSED, and not closeable here.
     *     The deleter is the last call this method makes, so no check placed
     *     before it can observe what the deleter itself does. The fix is
     *     offered where it can actually work — the verified hash is passed to
     *     the deleter as a THIRD argument, and a deleter that accepts it can
     *     re-verify before unlinking. Two-argument deleters are unaffected (PHP
     * ignores extra arguments), so this is additive, and the limit is written
     *     on the $unlink parameter rather than implied.
     *   - REMOTE swap (the document at that message id is replaced between the
     *     download and the unlink): NOT re-checked, and deliberately not
     *     treated as a deletion hazard. It cannot make the wrong file pass —
     *     the bytes compared are the local ones, and a substituted document
     *     holding different bytes would have failed clause (d) — so this is a
     *     LIVENESS limit (a window in which a revoke is not yet observed), not
     *     a data-loss one. An earlier draft of this docblock claimed the
     *     re-check covered "every swap", which was an overclaim about the
     *     remote half; it is corrected here rather than left to be found.
     *
     * MEMORY: clause (d) reads $path with hash_file() (streamed, never buffered)
     * and, under the cold posture, downloads the whole document to hash it. The
     * store therefore READS a disk here for the first time. It still never
     * DELETES one: unlinking stays injected, so a dry run is a real dry run and
     * this class still cannot free anything by itself.
     *
     * ── THE COST, STATED RATHER THAN HIDDEN ──────────────────────────────
     * Re-consulting the remote and hashing bytes cost time: a cold reclaim now
     * pays one bounded history walk and one full document download it did not
     * pay before. That is the right trade — the alternative is a cheaper delete
     * that can remove a stranger's file from a host's disk — but it is a real
     * regression in reclaim latency and it is paid on purpose.
     *
     * AND IT IS PAID IN MEMORY, WHICH IS THE PART THAT IS NOT YET FIXED.
     * TelegramTier::durableMediaFor() hashes the remote document by way of
     * TelegramStoreChannel::fetchDocument(), which BUFFERS THE WHOLE OBJECT
     * before handing it over. The local side of the comparison streams
     * (hashLocalFile); the remote side does not. So reclaiming a 2 GB local
     * file transiently holds 2 GB of RAM just to learn that it may be deleted.
     * There is no size ceiling on that path today.
     *
     * Stated rather than quietly capped. An arbitrary ceiling would refuse
     * reclaims of exactly the large files this feature exists for, which is a
     * worse failure than a documented memory cost; and streaming the download
     * means a new hashing path on TelegramStoreChannel, i.e. a change to that
     * class's fetch contract and therefore a separate goal with its own tests.
     * Until it lands, the operator-facing statement is: reclaim is not for
     * files larger than this host can hold twice.
     *
     * IT IS PAID BY $dryRun TOO, which is worth knowing before an operator
     * points a multi-gigabyte dry run at a bucket: the dry run is the most
     * expensive branch in this method, not the cheapest. That is the price of a
     * dry run that reports the same answer the real call would give; a dry run
     * that skipped the proof would answer "dry-run" for a file it had not
     * checked, which is the exact lie ReclaimOutcome::freedBytes() exists to
     * prevent.
     *
     * ── THE POSTURES, which still mean different things ─────────────────
     * `drop-local-after-index` never consults the wire (that is the posture's
     * definition) and therefore takes its content hash from the local row's
     * content_sha256 column, or from the caller's own `content_sha256`; absent
     * both it REFUSES with `no-content-proof`, because that posture has no other
     * way to bind content. `cold-remote-authoritative` derives the hash from the
     * remote and needs nothing from the caller.
     *
     * @param  string  $disk  logical disk label carried through to the deleter (e.g. 'local')
     * @param  string  $path  path of the local file to free
     * @param  array<string, mixed>  $expect  MUST name the object: $expect['bucket'] and
     *                                        $expect['msg_id']. A `content_sha256` key is
     *                                        read as the caller's content binding for the
     *                                        local posture; `doc_id` / `file_reference`
     *                                        keys are IGNORED — the row is resolved here,
     *                                        never taken from the caller
     * @param  ReferencePolicy|null  $policy  null = this service's configured posture (default
     *                                        ReferencePolicy::retainLocal(): never touches the filesystem)
     * @param  callable(string, string, string): bool|null  $unlink  ($disk, $path, $verifiedSha256) => freed?
     *                                                               REQUIRED under any reclaiming posture. The third argument is
     *                                                               the content hash this method just verified; a deleter that
     *                                                               declares it SHOULD re-hash $path and return false on any
     *                                                               difference, which is the only place the final TOCTOU hop
     *                                                               can be closed. Returns false when the bytes are still
     *                                                               present, reported as `refused`, never as freed
     * @param  bool  $dryRun  decide and report WITHOUT deleting
     *
     * @throws InvalidArgumentException on an empty $path, or when $expect names no (bucket, msg_id)
     * @throws RuntimeException when no durable reference can be resolved at all
     * @throws LogicException when reclaiming without an $unlink callable
     */
    public function reclaimLocal(
        string $disk,
        string $path,
        array $expect = [],
        ?ReferencePolicy $policy = null,
        ?callable $unlink = null,
        bool $dryRun = false,
    ): ReclaimOutcome {
        // Default is the service's configured posture, which is the safe one
        // unless the operator opted into a reclaiming posture: no operator
        // configuration, no deletion.
        $policy ??= $this->referencePolicy;

        if (trim($path) === '') {
            throw new InvalidArgumentException('path must not be empty');
        }

        // The safe default must not even look at the row: adopting this method
        // with no policy configured has to be a no-op, not a lookup.
        if (! $policy->reclaims()) {
            return new ReclaimOutcome(
                ReclaimOutcome::RETAINED,
                ReclaimOutcome::REASON_POLICY_RETAINS,
            );
        }

        // NOTHING ON DISK, checked before anything is proven, and that ordering
        // is the point rather than an optimisation. A reclaim that crashes
        // between the proof and the unlink is replayed, and the replay must be
        // idempotent: asking a caller for a valid object, a live reference and
        // a matching hash for a file that is already gone would turn the retry
        // that SAVES the day into an exception, and an operator's instinct on
        // seeing that exception is to stop retrying.
        //
        // It is not a fail-open, and the reason is worth stating because it
        // looks like one: the ONLY destructive step in this method is the
        // injected deleter, and this return happens before the deleter is even
        // looked at. There are no bytes here to lose. The two situations an
        // operator must be able to tell apart get different tokens —
        // `already-gone` for this one, `deleter-declined` for a deleter that
        // was asked and said no — because "nothing to delete" and "I tried and
        // could not" are the same word for a disk and opposite words for a
        // human.
        if (! is_file($path)) {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_ALREADY_GONE,
            );
        }

        // ── (a) IDENTITY ────────────────────────────────────────────────
        // A reclaim is always about one specific object in one specific bucket.
        // A bare row used to be accepted verbatim here, with no lookup and no
        // wire call, which meant an invented row freed a file on its own
        // testimony. Naming the object is the cheapest way to make the rest of
        // the proof checkable: everything below resolves a row for THIS
        // (bucket, msg_id) and ignores whatever row-shaped keys came in.
        $bucket = trim((string) ($expect['bucket'] ?? ''));
        $msgId = (int) ($expect['msg_id'] ?? 0);
        if ($bucket === '' || $msgId <= 0) {
            throw new InvalidArgumentException(
                'reclaimLocal needs $expect[bucket] and $expect[msg_id] naming the object whose durability it is '
                .'asking about; a bare row is a claim about bytes, not about an object, and this method proves objects',
            );
        }

        $row = $this->index->findMedia($this->accountId, $bucket, $msgId);

        // ── (c) FRESHNESS, first half: is there anything at all? ─────────
        // The remote answer is memoised in $live purely so a row resolved from
        // the remote is not walked twice; it is NEVER allowed to stand in for a
        // cached local row (that substitution is hole 2).
        $live = null;
        if ($policy->trustsRemote() && $row === null) {
            $live = $this->tier->durableMediaFor(($this->channelFactory)($bucket), $msgId);
            if ($live !== null) {
                $row = $live['row'];
            }
        }

        if ($row === null) {
            // No local truth and no live remote truth: the local file really is
            // the only copy, and saying so loudly is the honest answer.
            throw new RuntimeException(sprintf(
                'no durable reference for bucket %s message %d on account %d under the %s policy; '
                .'the local file is the only copy and must not be freed',
                $bucket,
                $msgId,
                $this->accountId,
                $policy->mode(),
            ));
        }

        // ── (b) ADDRESSABILITY, checked on the RESOLVED row ──────────────
        // Loud refusals. A missing reference here means the local file is the
        // ONLY copy, which is precisely when deleting would destroy data. These
        // run BEFORE the freshness consult on purpose: a row this method has
        // already resolved is judged on its own contents, and a caller cannot
        // reorder the diagnosis by pointing the message id somewhere else.
        $fileReference = trim((string) ($row['file_reference'] ?? ''));
        if ($fileReference === '') {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_EMPTY_FILE_REFERENCE,
            );
        }

        $docId = (int) ($row['doc_id'] ?? 0);
        if ($docId <= 0) {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_INVALID_DOC_ID,
            );
        }

        // A zero access_hash is the MTProto "unknown peer" sentinel: TelegramTier
        // has always refused to build a reference from one, and a reclaim that
        // accepted it here would prove durability for a document that no
        // publishByReference() could ever re-publish.
        $accessHash = (int) ($row['access_hash'] ?? 0);
        if ($accessHash <= 0) {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_INVALID_ACCESS_HASH,
            );
        }

        // ── (c) FRESHNESS, second half: ask the authority ────────────────
        $contentHash = null;
        if ($policy->trustsRemote()) {
            $live ??= $this->tier->durableMediaFor(($this->channelFactory)($bucket), $msgId);
            if ($live === null) {
                // A local row exists, so the file is not provably the only
                // copy — but under this posture the REMOTE is the proof and it
                // just declined. Refusing (rather than throwing) is deliberate:
                // "the document is gone from Telegram" is a diagnosis, and the
                // bytes stay.
                return new ReclaimOutcome(
                    ReclaimOutcome::REFUSED,
                    ReclaimOutcome::REASON_REMOTE_DOCUMENT_GONE,
                );
            }

            // NOTE WHAT THIS DOES NOT DO when the row was itself resolved
            // from the remote a few lines ago: it compares the live document to
            // itself, which is a tautology. That is fine and deliberate — an
            // absent local row cannot have been tampered with, so there is
            // nothing to drift FROM. The check earns its keep exactly when a
            // local row EXISTS and the wire has moved on, which is the stale
            // cache this clause exists to catch.
            if (! self::sameReference($row, $live['row'])) {
                // The message id now addresses a DIFFERENT document than the
                // cached row names. Either a row was tampered with or the
                // channel was rewritten under us; both are refusals, not
                // something to prefer one side of.
                return new ReclaimOutcome(
                    ReclaimOutcome::REFUSED,
                    ReclaimOutcome::REASON_REFERENCE_DRIFT,
                );
            }

            $contentHash = $live['content_sha256'];
            $this->cacheMediaRow($bucket, $msgId, $live['row']);
        }

        // ── (d) CONTENT ─────────────────────────────────────────────────
        // The caller's own binding, cross-checked against the row's and against
        // what the remote holds. A disagreement is a refusal, never a "pick the
        // one that lets the delete through".
        $asserted = self::contentHash($expect['content_sha256'] ?? null);
        $declared = self::contentHash($row['content_sha256'] ?? null);
        if ($asserted !== null && $declared !== null && ! hash_equals($declared, $asserted)) {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_BINDING_CONFLICT,
            );
        }

        // ORDER MATTERS, and it was wrong here. An independent verification pass
        // found this comparison could never fire: `$contentHash ?? $declared ??
        // $asserted` means that whenever $contentHash is non-null it IS the value
        // the very next line compares against itself. So the "the caller/row
        // binding contradicts the bytes Telegram actually holds" diagnosis was
        // unreachable — dead code carrying a promise in a security-critical file.
        //
        // Two separate questions, checked separately:
        //   1. does the REMOTE's own hash for these bytes match what the row (or
        //      the caller) DECLARED? A disagreement means somebody is wrong
        //      about which document this is.
        //   2. is there any hash at all binding these bytes to that document?
        //      If not, that is the no-proof refusal below.
        if ($contentHash !== null && $declared !== null && ! hash_equals($contentHash, $declared)) {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_CONTENT_MISMATCH,
            );
        }

        if ($contentHash !== null && $asserted !== null && ! hash_equals($contentHash, $asserted)) {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_BINDING_CONFLICT,
            );
        }

        $expected = $contentHash ?? $declared ?? $asserted;
        if ($expected === null) {
            // No hash binds these bytes to that document. Under the local
            // posture this is the normal shape of a row a message authored —
            // content_sha256 is null there and cannot be otherwise — and it is
            // precisely the case that used to free a file on a stranger's
            // testimony.
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_NO_CONTENT_PROOF,
            );
        }

        $actual = self::hashLocalFile($path);
        if ($actual === null) {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_CONTENT_UNREADABLE,
            );
        }

        if (! hash_equals($expected, $actual)) {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_CONTENT_MISMATCH,
            );
        }

        if ($dryRun) {
            return new ReclaimOutcome(ReclaimOutcome::REFUSED, ReclaimOutcome::REASON_DRY_RUN);
        }

        if ($unlink === null) {
            throw new LogicException(
                'reclaimLocal needs an $unlink callable in the drop-local-after-index policy; src/Teleframe/ never reaches a disk directly',
            );
        }

        // The TOCTOU narrowing, stated as what it is: a re-read of the file in
        // the instant before the deleter, which catches anything that replaced
        // it while the (slow) remote proof was being gathered. It cannot catch
        // a replacement made BY the deleter — see this method's docblock.
        $recheck = self::hashLocalFile($path);
        if ($recheck === null || ! hash_equals($expected, $recheck)) {
            return new ReclaimOutcome(
                ReclaimOutcome::REFUSED,
                ReclaimOutcome::REASON_CONTENT_CHANGED,
            );
        }

        // The third argument is additive: a two-parameter deleter ignores it
        // (PHP passes extra arguments to userland callables without complaint),
        // and one that declares it can re-verify before unlinking — the only
        // place the last hop is closable.
        $deleted = (bool) $unlink($disk, $path, $expected);

        return new ReclaimOutcome(
            $deleted ? ReclaimOutcome::RECLAIMED : ReclaimOutcome::REFUSED,
            $deleted ? ReclaimOutcome::REASON_DELETED : ReclaimOutcome::REASON_DELTER_DECLINED,
        );
    }

    /**
     * S11 handler helper: index ONE live update's media into tf_store_media
     * (the TFSTORE1/registry slices reuse this): when the update carries a
     * message with a document and the message's peer is one of this account's
     * indexed DATA channels, upsert the media row with Telegram-native
     * identity + sizes (content hash stays lazy). No-op for updates that are
     * not document messages or whose peer is not a declared data channel.
     *
     * ── WHY content_sha256 IS null HERE, AND WHY THAT IS LOAD-BEARING ─────
     * A message is attacker-writable: anyone who can post in a declared DATA
     * channel can therefore author a row in this table naming THEIR document.
     * That is the provenance asymmetry this method is the source of — the claim
     * "a local row wins because a chat message is attacker-writable" is true
     * for tf_store_objects (only this module writes those) and FALSE here.
     *
     * The asymmetry used to be a hole because nothing downstream could tell the
     * two kinds of row apart. It no longer is: reclaimLocal() requires a
     * CONTENT-SHAPED proof, the only source of a content hash for a media row
     * is a writer that has actually seen the bytes, and this method — which has
     * not, and structurally cannot — writes null. A row from here can therefore
     * nominate a message id for a read or a re-publish, and can never by
     * itself authorise deleting a local file.
     *
     * Do not "fix" the null below by hashing the document off the wire: a hash
     * of the STRANGER's bytes would bind the reference to their content, not to
     * the host's local file, and would make the row look trusted for a reason
     * that has nothing to do with it.
     *
     * @param  array<string, mixed>  $update  raw TL update array (bus shape)
     * @return array{indexed: bool, bucket?: string, msg_id?: int, reason: string}
     */
    public function archiveMessageMedia(array $update): array
    {
        $message = $update['message'] ?? null;
        if (! is_array($message)) {
            return ['indexed' => false, 'reason' => 'no message in update'];
        }

        $document = $message['media']['document'] ?? null;
        if (! is_array($document) || ($document['_'] ?? '') !== 'document') {
            return ['indexed' => false, 'reason' => 'message carries no document media'];
        }

        $peer = $message['peer_id'] ?? $message['to_id'] ?? null;
        $channelId = is_array($peer) ? (int) ($peer['channel_id'] ?? 0) : 0;
        if ($channelId <= 0) {
            return ['indexed' => false, 'reason' => 'message has no channel peer'];
        }

        $bucket = $this->dataBucketForChannel($channelId);
        if ($bucket === null) {
            return ['indexed' => false, 'reason' => "channel {$channelId} is not a declared data channel"];
        }

        $msgId = (int) ($message['id'] ?? 0);
        if ($msgId <= 0) {
            return ['indexed' => false, 'reason' => 'message has no id'];
        }

        $this->index->upsertMedia($this->accountId, $bucket, $msgId, [
            'media_type' => MediaType::Document->value,
            'doc_id' => (int) ($document['id'] ?? 0),
            'access_hash' => (int) ($document['access_hash'] ?? 0),
            'file_reference' => (string) ($document['file_reference'] ?? ''),
            'size' => (int) ($document['size'] ?? 0),
            'mime' => (string) ($document['mime_type'] ?? 'application/octet-stream'),
            'content_sha256' => null,
            'indexed_at_unix' => ($this->now)(),
        ]);

        return ['indexed' => true, 'bucket' => $bucket, 'msg_id' => $msgId, 'reason' => 'media indexed'];
    }

    /** Find the first data-kind bucket whose truth-row channel_id matches. */
    private function dataBucketForChannel(int $channelId): ?string
    {
        foreach ($this->index->listBuckets($this->accountId, ChannelRegistry::KIND_DATA) as $row) {
            if ((int) ($row['channel_id'] ?? 0) === $channelId) {
                return (string) $row['bucket'];
            }
        }

        return null;
    }

    /**
     * Re-download every chunk and verify the manifest invariants: raw
     * chunks must match plaintext sha256; sealed chunks must match both
     * wire and plaintext sha256; a sealed manifest must carry a valid
     * MAC; and the REASSEMBLED bytes must match the manifest's
     * whole-object sha256 — the check that actually catches a manifest
     * edited to describe a different byte string.
     *
     * @return array{ok: bool, chunks: int, errors: list<string>}
     */
    public function verify(string $bucket, string $name, ?string $passphrase = null): array
    {
        [$manifest, $peer] = $this->manifestSourceFor($bucket, $name);
        $key = $this->keyFor($manifest, $passphrase);
        $errors = [];

        try {
            $this->assertManifestAuthenticated($manifest, $key);
        } catch (RuntimeException $e) {
            return ['ok' => false, 'chunks' => $manifest->chunkCount(), 'errors' => ['manifest: '.$e->getMessage()]];
        }

        $wholeCtx = hash_init('sha256');
        $assembled = 0;

        foreach ($manifest->chunks() as $chunk) {
            try {
                $bytes = $this->fetchChunkPlain($bucket, $manifest, $chunk, $key, $peer);
                hash_update($wholeCtx, $bytes);
                $assembled += strlen($bytes);
            } catch (RuntimeException $e) {
                $errors[] = 'chunk '.$chunk['n'].': '.$e->getMessage();
            }
        }

        // Only meaningful when every chunk verified: a partial assembly
        // would "mismatch" for a reason already reported per chunk.
        if ($errors === []) {
            try {
                $this->assertAssembledHash($manifest, hash_final($wholeCtx), $assembled);
            } catch (RuntimeException $e) {
                $errors[] = 'object: '.$e->getMessage();
            }
        }

        return ['ok' => $errors === [], 'chunks' => $manifest->chunkCount(), 'errors' => $errors];
    }

    /** Evict cold chunks from the hot cache. Returns the evicted count. */
    public function prune(): int
    {
        return $this->cache->evict();
    }

    // ── internals ──────────────────────────────────────────────────────

    /**
     * The remote manifest for an object with no local row — but ONLY in the
     * cold posture. Under the retaining postures this returns null without
     * touching the wire at all, which is what keeps the default path
     * byte-for-byte what it was: an unconfigured store never pays a search it
     * did not ask for, and never reports a remote message as local truth.
     */
    private function remoteManifestFor(string $bucket, string $name): ?ObjectManifest
    {
        if (! $this->referencePolicy->trustsRemote()) {
            return null;
        }

        $found = $this->tier->manifestFor(($this->channelFactory)($bucket), $name);
        if ($found === null) {
            return null;
        }

        // Refill the cache so the next call is local again — the cold posture
        // pays a walk ONCE, not once per read.
        $this->cacheManifestRow($bucket, $name, $found['manifest']);

        return $found['manifest'];
    }

    /**
     * The tf_store_media row for a message id, re-resolved from the remote
     * tier and written back as a cache — but ONLY in the cold posture.
     *
     * Outside that posture this returns null without touching the wire, which
     * is what keeps the loud "index it first" failure of
     * publishByReference() intact by default: a store that has not been told
     * to trust the remote must not start treating a walk result as truth.
     *
     * NOT the reclaim path any more. A reclaim under the cold posture asks
     * RemoteTierContract::durableMediaFor() instead, because it needs the
     * CONTENT hash as well and because a cached row is not a proof; this
     * method is for the read/publish paths, where a cache hit is exactly the
     * performance fact they are allowed to rely on.
     *
     * @return array<string, mixed>|null
     */
    private function remoteMediaRow(string $bucket, int $msgId): ?array
    {
        if (! $this->referencePolicy->trustsRemote()) {
            return null;
        }

        $found = $this->tier->mediaFor(($this->channelFactory)($bucket), $msgId);
        if ($found === null) {
            return null;
        }

        $this->cacheMediaRow($bucket, $msgId, $found['row']);

        return $found['row'];
    }

    /**
     * Write a remotely-resolved media row into the local truth table, stamped
     * with the time it was resolved. A CACHE WRITE, and nothing more: no proof
     * in this class reads it back as evidence.
     *
     * @param  array<string, mixed>  $row
     */
    private function cacheMediaRow(string $bucket, int $msgId, array $row): void
    {
        $this->index->upsertMedia(
            $this->accountId,
            $bucket,
            $msgId,
            $row + ['indexed_at_unix' => ($this->now)()],
        );
    }

    /**
     * The sha256 of the file at $path, streamed — never buffered, because the
     * files this feature exists to free are the large ones. null when the path
     * cannot be opened, which the caller must treat as "no proof" rather than
     * "no bytes": a file this class cannot read is one it must not delete.
     */
    private static function hashLocalFile(string $path): ?string
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $handle);

            return hash_final($context);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Whether two media rows name the SAME Telegram document. doc_id,
     * access_hash and file_reference are the document's identity on the wire;
     * a reclaim that compares anything looser (size, mime, a timestamp) would
     * call a substituted document the same one.
     *
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private static function sameReference(array $a, array $b): bool
    {
        return (int) ($a['doc_id'] ?? 0) === (int) ($b['doc_id'] ?? 0)
            && (int) ($a['access_hash'] ?? 0) === (int) ($b['access_hash'] ?? 0)
            && trim((string) ($a['file_reference'] ?? '')) === trim((string) ($b['file_reference'] ?? ''));
    }

    /**
     * Normalise a claimed content hash to 64 lowercase hex characters, or null
     * when the value is absent or is not a sha256 at all.
     *
     * null is the interesting answer, not an edge case: `content_sha256` is
     * written NULL by every writer that takes its row from a chat message
     * (archiveMessageMedia, indexDataChannel, TelegramTier::mediaRowFromDocument),
     * and that null is what makes such a row unable to authorise a delete. A
     * malformed value is treated exactly like a missing one — never coerced
     * into something that could match.
     */
    private static function contentHash(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $hex = strtolower(trim($value));
        if (strlen($hex) !== 64 || ! ctype_xdigit($hex)) {
            return null;
        }

        return $hex;
    }

    /**
     * Write a remotely-resolved manifest into the local truth table, so the
     * local index is a cache of the remote tier rather than a requirement.
     */
    private function cacheManifestRow(string $bucket, string $name, ObjectManifest $manifest): void
    {
        $now = ($this->now)();
        $this->index->upsertObject($this->accountId, $bucket, $name, [
            'mode' => $manifest->mode(),
            'size' => $manifest->size(),
            'chunks' => $manifest->chunks(),
            'metadata' => $manifest->toArray(),
            'created_at_unix' => $now,
            'updated_at_unix' => $now,
        ]);
    }

    /**
     * @param  resource|string  $source
     * @return array{0: resource, 1: int} seekable handle + byte size
     */
    private function resolveSource($source): array
    {
        if (is_resource($source)) {
            $stat = fstat($source);
            if ($stat === false) {
                throw new InvalidArgumentException('cannot stat source stream');
            }

            return [$source, (int) $stat['size']];
        }

        if (! is_string($source)) {
            throw new InvalidArgumentException('source must be a path, bytes, or a stream');
        }

        if (is_file($source)) {
            $handle = fopen($source, 'rb');
            if ($handle === false) {
                throw new RuntimeException('cannot open source path: '.$source);
            }

            return [$handle, filesize($source)];
        }

        $handle = fopen('php://temp', 'w+b');
        fwrite($handle, $source);
        rewind($handle);

        return [$handle, strlen($source)];
    }

    private function buildJob(string $bucket, string $name, string $mode, ?string $passphrase, $source, int $size): UploadJob
    {
        if (is_string($source) && is_file($source)) {
            return new UploadJob($this->accountId, $bucket, $name, $mode, $source, $size, passphrase: $passphrase);
        }

        return new UploadJob($this->accountId, $bucket, $name, $mode, $this->materializeSource($source), $size, passphrase: $passphrase);
    }

    /**
     * A job the worker will never run (enqueue failed) must not leak its
     * temp file. Caller-supplied source paths pass through untouched — only
     * the files this service materialized for bytes/stream sources are
     * unlinked.
     *
     * @param  resource|string  $source
     */
    private function discardJobSource(UploadJob $job, $source): void
    {
        if (is_string($source) && is_file($source)) {
            return;
        }

        @unlink($job->sourcePath);
    }

    /**
     * Materialize a bytes/stream source to a temp file the queue worker
     * owns (unlinked after use). Never leaves a half-written file behind:
     * any creation/copy/write failure cleans the temp file up and throws.
     *
     * @param  resource|string  $source
     */
    private function materializeSource($source): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tfstore-');
        if ($path === false) {
            throw new RuntimeException('cannot create upload job temp file');
        }

        $handle = @fopen($path, 'w+b');
        if ($handle === false) {
            @unlink($path);
            throw new RuntimeException('cannot open upload job temp file: '.$path);
        }

        try {
            if (is_resource($source)) {
                $copied = stream_copy_to_stream($source, $handle);
                if ($copied === false) {
                    throw new RuntimeException('cannot copy upload job source to temp file: '.$path);
                }
            } else {
                $bytes = (string) $source;
                if (fwrite($handle, $bytes) !== strlen($bytes)) {
                    throw new RuntimeException('cannot write upload job temp file: '.$path);
                }
            }
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($path);

            throw $e;
        }
        fclose($handle);

        return $path;
    }

    /**
     * Inline upload of a seekable source. Returns the object row + the
     * manifest array. Assumes the handle is positioned at the start.
     *
     * @param  resource  $handle
     * @return array{object: array<string, mixed>, manifest: array<string, mixed>}
     */
    private function uploadInline(string $bucket, string $name, string $mode, ?string $passphrase, string $mime, int $size, $handle): array
    {
        $channel = ($this->channelFactory)($bucket);
        $peer = $channel->inputPeer();
        $this->upsertBucketRow($bucket, ChannelRegistry::KIND_STORE, (int) $peer['channel_id'], (int) $peer['access_hash']);
        $channel->ensureRegistry();

        $saltHex = null;
        $key = null;
        if ($mode === ObjectManifest::MODE_SEALED) {
            $saltHex = bin2hex(($this->saltProvider)());
            $key = ($this->keyDeriver)($passphrase, $saltHex);
        }

        $chunks = [];
        $wholeCtx = hash_init('sha256');
        foreach (ChunkPlanner::planChunks($size, $this->premium) as $chunk) {
            $chunkRow = $this->uploadOneChunk($channel, $handle, $chunk, $mode, $key, $wholeCtx);
            $chunks[] = $chunkRow;
        }

        $manifest = ObjectManifest::new(
            $name,
            $size,
            hash_final($wholeCtx),
            $mode,
            $mime,
            $saltHex,
            $chunks,
        );

        // A sealed manifest is authenticated with the SAME key that sealed
        // its chunks — the one secret this module holds, and one the channel
        // audience does not. Raw manifests carry no mac (no key exists).
        if ($mode === ObjectManifest::MODE_SEALED && $key !== null) {
            $manifest = $manifest->withMac(ManifestMac::compute($key, $manifest->toArray()));
        }

        $channel->putMessage(MetadataMessage::render($manifest));

        $now = ($this->now)();
        $object = [
            'mode' => $mode,
            'size' => $size,
            'chunks' => $chunks,
            'metadata' => $manifest->toArray(),
            'created_at_unix' => $now,
            'updated_at_unix' => $now,
        ];
        $this->index->upsertObject($this->accountId, $bucket, $name, $object);

        return ['object' => $object, 'manifest' => $manifest->toArray()];
    }

    /**
     * Upload one planned chunk. Raw: hash pass (plaintext sha256, doc
     * named by it), then raw part upload. Sealed: build the wire ONCE
     * (header + sealed parts) on a spool stream, hash that exact wire for
     * the doc name, and upload from the spool. A re-seal cannot work:
     * the secretstream header is random per init_push, so a second seal
     * would not reproduce the hashed wire.
     *
     * @param  resource  $handle
     * @param  array{n: int, offset: int, size: int}  $chunk
     * @param  mixed  $wholeCtx  \HashContext for the whole-object plaintext hash
     * @return array{n: int, size: int, sha256: string, wire_sha256?: string}
     */
    private function uploadOneChunk(TelegramStoreChannel $channel, $handle, array $chunk, string $mode, ?string $key, $wholeCtx): array
    {
        if ($mode === ObjectManifest::MODE_RAW) {
            $sha = $this->rawChunkSha($handle, $chunk, $wholeCtx);
            $channel->putChunk($sha, $chunk['size'], $handle, $chunk['offset']);

            return ['n' => $chunk['n'], 'size' => $chunk['size'], 'sha256' => $sha];
        }

        [$wire, $wireSha, $plainSha] = $this->sealChunk($handle, $chunk, $key, $wholeCtx);
        $wireSize = (int) (fstat($wire)['size'] ?? 0);
        $channel->putChunk($wireSha, $wireSize, $wire);
        unset($wire);

        return ['n' => $chunk['n'], 'size' => $chunk['size'], 'sha256' => $plainSha, 'wire_sha256' => $wireSha];
    }

    /**
     * Raw chunk hash pass: stream the chunk parts into the chunk sha256
     * AND the whole-object context (no crypto, disk/I-O only).
     *
     * @param  resource  $handle
     * @param  array{n: int, offset: int, size: int}  $chunk
     * @param  mixed  $wholeCtx
     */
    private function rawChunkSha($handle, array $chunk, $wholeCtx): string
    {
        $ctx = hash_init('sha256');
        $matrix = ['n' => 0, 'offset' => $chunk['offset'], 'size' => $chunk['size']];
        foreach (ChunkPlanner::planParts($chunk['size']) as $part) {
            $plain = ChunkPlanner::readPart($handle, $matrix, $part);
            hash_update($ctx, $plain);
            hash_update($wholeCtx, $plain);
        }

        return hash_final($ctx);
    }

    /**
     * Seal one chunk into a spool stream in a single pass: header(24) ||
     * push(part0) || ... || push(last, FINAL). The caller uploads the spool
     * verbatim, so the ciphertext hash describes the uploaded bytes exactly.
     * Plaintext flows into the chunk ctx + the whole-object ctx.
     *
     * Returns [spoolStream, wireSha256, plainSha256]. The stream is
     * rewound and positioned for upload.
     *
     * @param  resource  $handle
     * @param  array{n: int, offset: int, size: int}  $chunk
     * @param  mixed  $wholeCtx
     * @return array{0: resource, 1: string, 2: string}
     */
    private function sealChunk($handle, array $chunk, string $key, $wholeCtx): array
    {
        $sealer = new StreamSealer;
        $spool = fopen('php://temp', 'w+b');
        if ($spool === false) {
            throw new RuntimeException('cannot open sealed wire spool');
        }

        $header = $sealer->startSeal($key);
        fwrite($spool, $header);

        $wireCtx = hash_init('sha256');
        $plainCtx = hash_init('sha256');
        hash_update($wireCtx, $header);

        $matrix = ['n' => 0, 'offset' => $chunk['offset'], 'size' => $chunk['size']];
        $parts = ChunkPlanner::planParts($chunk['size']);
        $lastIndex = count($parts) - 1;

        foreach ($parts as $index => $part) {
            $plain = ChunkPlanner::readPart($handle, $matrix, $part);
            hash_update($plainCtx, $plain);
            hash_update($wholeCtx, $plain);
            $wire = $sealer->push($key, $plain, $index === $lastIndex);
            hash_update($wireCtx, $wire);
            fwrite($spool, $wire);
        }
        rewind($spool);

        return [$spool, hash_final($wireCtx), hash_final($plainCtx)];
    }

    /**
     * Resolve a chunk to its plaintext: hot cache, else channel fetch +
     * (for sealed) verify wire sha, decrypt, verify plaintext sha. Cache
     * hits are always verified against the manifest before use.
     *
     * $peer is the peer the object's manifest came from (see
     * manifestSourceFor): the chunk is searched THERE ONLY, so a second
     * peer cannot shadow a chunk with a same-named document. null falls
     * back to the channel's ordered read peers.
     *
     * @param  array{n: int, size: int, sha256: string, wire_sha256?: string}  $chunk
     * @param  array<string, mixed>|null  $peer
     * @return string the chunk's plaintext bytes
     */
    private function fetchChunkPlain(string $bucket, ObjectManifest $manifest, array $chunk, ?string $key, ?array $peer = null): string
    {
        $sha = $manifest->sha256();
        $cached = $this->cache->get($bucket, $sha, $chunk['n']);

        $sealed = $manifest->mode() === ObjectManifest::MODE_SEALED;
        $serveCache = $cached !== false
            && (! $sealed || $this->allowCachedPlaintext || $key !== null);
        if ($serveCache && hash('sha256', $cached) === $chunk['sha256']) {
            return $cached;
        }

        $channel = ($this->channelFactory)($bucket);
        $docName = $manifest->chunkDocName($chunk['n']);
        $entries = $peer === null
            ? $channel->findMessagesByName($docName, 1)
            : $channel->findMessagesByNameIn($peer, $docName, 1);
        if ($entries === []) {
            throw new RuntimeException("chunk document not found: {$docName}");
        }
        $wire = (string) ($entries[0]['fetch'] ?? '')();

        if (! $sealed) {
            if (hash('sha256', $wire) !== $chunk['sha256']) {
                throw new RuntimeException("chunk {$chunk['n']} sha256 mismatch");
            }
            $this->cache->put($bucket, $sha, $chunk['n'], $wire);

            return $wire;
        }

        if ($key === null) {
            throw new RuntimeException('sealed object requires a passphrase');
        }
        if (hash('sha256', $wire) !== ($chunk['wire_sha256'] ?? '')) {
            throw new RuntimeException("sealed chunk {$chunk['n']} wire sha256 mismatch");
        }

        $plain = $this->unsealChunk($key, $wire, $chunk['size']);
        if (hash('sha256', $plain) !== $chunk['sha256']) {
            throw new RuntimeException("sealed chunk {$chunk['n']} plaintext sha256 mismatch");
        }
        $this->cache->put($bucket, $sha, $chunk['n'], $plain);

        return $plain;
    }

    /**
     * Unseal one chunk's wire: slice it back at the exact part boundaries
     * (24 B header || push(part0) || push(part1) ... || push(last, FINAL),
     * each push = plaintext + ABYTES) and pull part-by-part.
     */
    private function unsealChunk(string $key, string $wire, int $chunkSize): string
    {
        $sealer = new StreamSealer;
        $headerBytes = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES;
        $abytes = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES;

        $parts = ChunkPlanner::planParts($chunkSize);
        $lastIndex = count($parts) - 1;
        $offset = $headerBytes;
        $plain = '';

        foreach ($parts as $index => $part) {
            $sliceLen = $part['size'] + $abytes;
            $slice = $index === 0
                ? substr($wire, 0, $headerBytes + $sliceLen)
                : substr($wire, $offset, $sliceLen);
            $plain .= $sealer->pull($key, $slice, $index === $lastIndex);
            $offset += $sliceLen;
        }

        if (! $sealer->pulled()) {
            throw new RuntimeException('sealed chunk stream was not finalized');
        }

        return $plain;
    }

    /**
     * SEAL POLICY for a configured peer target. A group/channel destination
     * the account shares with other people is exactly the owner's case:
     * "you can make them public but put pass on whatever you send there".
     * PeerTarget::requiresSeal() says so — a group always does, and an
     * owned channel does under the sealed default — so a RAW put aimed at
     * a target that requires a seal is refused HERE, before a single byte
     * is written, rather than posting readable plaintext into a shared peer.
     *
     * Untargeted buckets (no peer target) keep their existing raw/sealed
     * freedom: they write to the account's own private bucket channel, so
     * there is no audience to protect from.
     */
    private function assertTargetSealPolicy(string $bucket, string $mode): void
    {
        $target = ($this->channelFactory)($bucket)->peerTarget();
        if ($target === null || ! $target->requiresSeal()) {
            return;
        }

        if ($mode !== ObjectManifest::MODE_RAW) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'bucket "%s" is routed to a %s peer target (peer_id %s) that requires sealed uploads, '
            .'but this put is raw. A shared group/channel destination would carry readable plaintext — '
            .'store it sealed (ObjectManifest::MODE_SEALED plus a passphrase), or unset the peer '
            .'target to store raw to the account\'s own private bucket channel.',
            $bucket,
            $target->peerType,
            (string) $target->peerId,
        ));
    }

    /**
     * The object's manifest AND the peer it was found in.
     *
     * The peer is not incidental: the manifest is the index, and it is
     * always co-located with the chunks it describes (the channel writes
     * both to one destination peer). So a caller that gets the manifest
     * back MUST read that object's chunks from the same peer — a fan-out
     * across peers would let a second peer shadow a chunk by planting a
     * document under the same content hash.
     *
     * Truth table first, channel TFSTORE1 message fallback (fresh-login
     * rediscovery; the row is re-upserted). When the truth row already has
     * the manifest, the owning peer is still located (cheaply, and only
     * when more than one peer is in play) so a legacy object keeps reading
     * from the peer it was actually written to.
     *
     * THE ORDER IS A TRUST DECISION, not an optimisation. A local row is not
     * attacker-writable; a TFSTORE1 message in a bucket other people can post
     * in IS. So a row that is present stays authoritative — the remote copy
     * never overrides it — and the remote tier is consulted only when there is
     * nothing local, which is exactly the case a cold copy is in. Inverting
     * that order would hand the editable copy precedence over the trusted one.
     *
     * @return array{0: ObjectManifest, 1: array<string, mixed>}
     */
    private function manifestSourceFor(string $bucket, string $name): array
    {
        $channel = ($this->channelFactory)($bucket);
        $row = $this->index->findObject($this->accountId, $bucket, $name);

        if ($row !== null) {
            $manifest = ObjectManifest::fromArray($row['metadata']);

            return [$manifest, $this->owningPeer($channel, $name)];
        }

        $found = $this->tier->manifestFor($channel, $name);
        if ($found === null) {
            throw new RuntimeException("object not found: {$bucket}/{$name}");
        }

        $manifest = $found['manifest'];
        $this->cacheManifestRow($bucket, $name, $manifest);

        return [$manifest, $found['peer']];
    }

    /**
     * Which peer holds an object's metadata message — the peer its chunks
     * must be read from. With a single read peer (the default, no target
     * configured) this is that peer with NO extra wire call, so the default
     * read path is unchanged; with a target configured it is one search,
     * which is the price of reading a legacy object out of the peer it was
     * actually written to.
     *
     * @return array<string, mixed>
     */
    private function owningPeer(TelegramStoreChannel $channel, string $name): array
    {
        $peers = $channel->readPeers();
        if (count($peers) < 2) {
            return $peers[0];
        }

        $found = $channel->locate(MetadataMessage::MARKER.' '.$name, 1);

        // Not locatable in any peer: fall back to the destination. A missing
        // index message is a loud, honest error — the chunk fetch below
        // raises it — rather than a silent read from somewhere plausible.
        return $found === null ? $peers[0] : $found['peer'];
    }

    private function keyFor(ObjectManifest $manifest, ?string $passphrase): ?string
    {
        if ($manifest->mode() === ObjectManifest::MODE_RAW) {
            return null;
        }
        if ($passphrase === null || $passphrase === '') {
            return null;
        }

        return ($this->keyDeriver)($passphrase, $manifest->saltHex() ?? '');
    }

    /**
     * The manifest trust gate for reads.
     *
     * A SEALED object is authenticated: its manifest must carry a MAC that
     * verifies under the key derived from the caller's passphrase. Missing
     * key or missing/mismatched MAC is a hard refusal — an editable,
     * unauthenticated manifest must never be able to describe the bytes
     * this module hands back, because that manifest is also where every
     * per-chunk expectation comes from.
     *
     * A RAW object has no key by construction, so there is nothing to
     * authenticate with. Its guarantee is the whole-object sha256
     * comparison (assertAssembledHash) plus canonical chunk order. That is
     * documented as a limit rather than papered over with a MAC under a
     * value the channel audience also holds — which would authenticate
     * nothing (see ManifestMac's docblock).
     */
    private function assertManifestAuthenticated(ObjectManifest $manifest, ?string $key): void
    {
        if ($manifest->mode() === ObjectManifest::MODE_RAW) {
            return;
        }

        if ($key === null) {
            throw new RuntimeException('sealed object requires a passphrase to authenticate its manifest');
        }

        ManifestMac::verify($key, $manifest->toArray(), $manifest->macHex(), 'object '.$manifest->name());
    }

    /**
     * Compare the assembled bytes against the manifest's declared
     * whole-object sha256 (and its declared size). A hash that is computed
     * and never compared is decoration: this is the check that turns "every
     * chunk verified against the manifest" into "the OBJECT is the one the
     * manifest describes".
     */
    private function assertAssembledHash(ObjectManifest $manifest, string $actualHex, int $actualSize): void
    {
        if ($actualSize !== $manifest->size()) {
            throw new RuntimeException(
                "object {$manifest->name()} assembled {$actualSize} bytes but its manifest declares {$manifest->size()}",
            );
        }

        if (! hash_equals($manifest->sha256(), $actualHex)) {
            throw new RuntimeException(
                "object {$manifest->name()} whole-object sha256 mismatch: the assembled bytes are not the ones the manifest describes",
            );
        }
    }

    private function upsertBucketRow(string $bucket, string $kind, int $channelId, int $accessHash): void
    {
        $this->index->upsertBucket($this->accountId, $bucket, [
            'kind' => $kind,
            'channel_id' => $channelId,
            'access_hash' => $accessHash,
            'created_at_unix' => ($this->now)(),
        ]);
    }

    /**
     * Message ids for a name in ONE specific peer (the peer the object was
     * located in) — a revoke must address the peer that actually holds the
     * messages.
     *
     * EXACT-NAME FILTER, applied locally to the rows already fetched. The
     * lookup path is messages.search with 'q' => $prefix, which is a
     * SUBSTRING match on the server: "TFSTORE1: foo" is a literal substring
     * of "TFSTORE1: foo-bar", so an unfiltered delete('foo') also revokes
     * foo-bar's metadata message. Chunk document names are full 64-hex
     * digests, for which a substring collision is not reachable, but the
     * same exact comparison covers both cases. This costs ZERO extra I/O —
     * findMessagesByNameIn() has already round-tripped and materialised the
     * rows.
     *
     * THE SEARCH IS PAGED TO EXHAUSTION, NOT SAMPLED, because the exact-name
     * filter cannot be pushed to the server and the two halves have to meet.
     * The filter decides which rows are safe to revoke; exhaustion decides
     * whether the target's own rows were among the candidates at all. Take one
     * capped page and the filter is applied to a set the target may not be in:
     * a peer holding more than a page of messages sharing the prefix ranks the
     * exact row below the cut, every returned row fails the exact comparison,
     * and the delete reports success while the object's own messages stay in the
     * chat — `exists()` then says the object is gone. That was this method's
     * behaviour under a hardcoded limit of 100, which was an arbitrary rank
     * rather than a cost bound; see TelegramStoreChannel::findAllMessagesByNameIn
     * for why the bound is now a page size rather than a ceiling.
     *
     * @param  array<string, mixed>  $peer
     * @param  bool  $isMetadata  true for the TFSTORE1 manifest message
     *                            (compared via MetadataMessage::nameFromText);
     *                            false for a chunk document (compared as an
     *                            exact document file name)
     * @return list<int>
     */
    private function messageIdsFor(TelegramStoreChannel $channel, string $prefix, array $peer, string $wantedName, bool $isMetadata): array
    {
        $ids = [];
        foreach ($channel->findAllMessagesByNameIn($peer, $prefix) as $entry) {
            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $name = $entry['name'] ?? null;
            if (! is_string($name)) {
                continue;
            }
            $matches = $isMetadata
                ? MetadataMessage::nameFromText($name) === $wantedName
                : $name === $wantedName;
            if ($matches) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
