<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use InvalidArgumentException;
use LogicException;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\PeerMembership;
use MeRezaRezaei\Teleframe\Store\Backup\PeerTarget;
use RuntimeException;
use Throwable;

/**
 * Channel-per-bucket wire ops (verbatim S5/S8, plan W2): one Telegram
 * channel per TStore bucket, exact-cap chunk documents named by content
 * hash (sealed → wire_sha256, raw → plaintext sha256), TFSTORE1 metadata
 * messages, TFREGISTRY1 registry message. Mirrors TelegramVault's pattern:
 * every call lives behind an injectable call map so tests run offline;
 * scopeApi() builds that map from a real UserAccountScope (a BotAccount-
 * Scope passes as-is — subclass) using teleframe's actual surfaces.
 *
 * PEER TARGETS: a configured PeerTarget (channel/group destination) IS
 * honoured — it becomes the destination peer for every wire op (put, get,
 * search, history, delete). See ROUTING below for the co-location
 * invariant that makes a restore find every chunk, and REFUSAL below for
 * the cases that still fail loudly.
 *
 * ROUTING (the index question, answered): the TFSTORE1 manifest message,
 * the TFREGISTRY1 registry message and every chunk document of a bucket are
 * written to ONE peer — the configured target when there is one, otherwise
 * the bucket's own channel. Nothing is split: a manifest that lives in the
 * target peer is never paired with chunks in the bucket channel, so a
 * restore that finds the manifest finds the chunks it describes. Reads
 * search the destination peer FIRST and fall back to the bucket's own
 * channel, because an object written before a target was configured is
 * still indexed there; and once a manifest has been resolved, that object's
 * chunks are read from and deleted in the peer the manifest came from
 * (findMessagesByNameIn/deleteMessagesIn) — never a fan-out across peers.
 * A fan-out would let a second peer shadow a chunk by planting a document
 * under the same content hash, which is a substitution the chunk hash check
 * is too late to prevent on an unauthenticated manifest.
 *
 * REFUSAL (what still fails, loudly, before any byte moves): a target with
 * no access hash (0/absent) cannot address the peer on the wire, and a
 * target the account demonstrably cannot post in is refused up front rather
 * than after a partial upload. Refusal is the exception, not the default.
 * Never a passphrase, access hash, session string or token appears in any
 * of these messages.
 *
 * The channel wraps the two long part loops — uploadPart (saveBigFilePart)
 * and getFile (upload.getFile) — in a ResilientCall built from the
 * injected map, so flood-wait / DC-migration retry works for ANY map
 * source (live scope or offline fake), and the sleep is injectable.
 *
 * Zero buffering > part size: putChunk streams planned parts from a
 * seekable handle one 512 KiB slice at a time; fetch streams upload.getFile
 * parts in a loop.
 */
final class TelegramStoreChannel
{
    public const CHANNEL_PREFIX = 'teleframe-store:';

    public const CHANNEL_ABOUT = 'teleframe store bucket';

    private const SEARCH_LIMIT = 100;

    private const PART_SIZE = ChunkPlanner::PART_SIZE; // 512 KiB

    private const API_KEYS = [
        'findChannel',
        'createChannel',
        'uploadPart',
        'sendDocument',
        'sendReference',
        'sendMessage',
        'searchMessages',
        'historyPage',
        'deleteMessages',
        'getFile',
    ];

    private readonly ResilientCall $resilient;

    private ?int $channelId = null;

    private int $channelAccessHash = 0;

    /**
     * @param  array<string, callable>  $api  injectable teleframe call map; see scopeApi()
     * @param  callable(int): callable|null  $rebuild  DC-migration rebuild for the resilient wrapper
     * @param  callable(int): void|null  $sleep  injectable flood sleeper (tests)
     * @param  PeerTarget|null  $peerTarget  a CONFIGURED channel/group destination — HONOURED
     *                                       as the destination peer (see ROUTING above)
     * @param  PeerMembership|null  $membership  the join/invite/post-rights seam. When bound AND a
     *                                           target is configured, membership is ensured and
     *                                           post rights are probed HERE, before the first
     *                                           upload, so an unusable peer is refused up front
     *                                           instead of failing opaquely mid-upload. When
     *                                           null (no live scope composed) the join/invite
     *                                           and rights steps are skipped and only the
     *                                           access-hash refusal below applies.
     *
     * @throws InvalidArgumentException when a required callable is missing
     * @throws LogicException when a configured target is unusable (no access hash, or the
     *                        account cannot post there) — refused BEFORE any upload
     * @throws BackupException when the membership seam itself fails to join/invite
     */
    public function __construct(
        private readonly string $bucket,
        private readonly array $api,
        ?callable $rebuild = null,
        private readonly int $maxFloodWaitSeconds = 120,
        ?callable $sleep = null,
        private readonly ?PeerTarget $peerTarget = null,
        ?PeerMembership $membership = null,
    ) {
        if ($this->peerTarget !== null) {
            $this->assertTargetUsable($this->peerTarget, $membership);
        }

        $missing = [];
        foreach (self::API_KEYS as $key) {
            if (! array_key_exists($key, $this->api)) {
                $missing[] = $key;
            }
        }
        if ($missing !== []) {
            throw new InvalidArgumentException(
                'store channel api map is missing callable(s): '.implode(', ', $missing),
            );
        }

        $this->resilient = new ResilientCall(
            fn (string $method, array $params): array => $this->dispatchResilient($method, $params),
            $rebuild,
            $this->maxFloodWaitSeconds,
            sleep: $sleep,
        );
    }

    /**
     * REFUSAL + MEMBERSHIP GATE, run at bind time — before the first byte
     * moves, so an unusable destination is a clear error rather than a
     * half-finished upload. Order is membership → rights → invite, because
     * a group the account has not joined cannot post at all, and inviting
     * redundant members into a peer the account cannot post in is pointless.
     *
     * 1. NO ACCESS HASH → refuse. A hash of 0 is the MTProto "unknown peer"
     *    sentinel; the invitation/join flows identify a peer by id, but an
     *    upload (sendDocument/sendMessage) does not, so the server rejects
     *    it mid-upload. Pure data check — no seam needed.
     * 2. JOIN (when a seam is bound): channels.joinChannel, tolerating ONLY
     *    the "already a participant" reply. Any other join error (a private
     *    peer we were never invited to, an invite link that expired) means
     *    this account cannot post there, and it propagates rather than
     *    being swallowed into a later opaque failure.
     * 3. POST RIGHTS (when a seam is bound): the STRICT three-state probe.
     *    A definitive false is refused here. null (unverifiable — offline,
     *    no scope, or a reply that says nothing about participants) is NOT
     *    a denial: refusing on "could not tell" would make offline and
     *    partially-probed installs unusable, and the probe's own docblock
     *    is explicit that it never fatals. It is documented rather than
     *    silently ignored, because unverifiable is a weaker guarantee than
     *    verified and the host should know that.
     * 4. INVITE the configured member accounts (when a seam is bound and
     *    the target names any) — the redundant-accounts seam, last.
     *
     * No access hash, passphrase, session string or token is ever named in
     * these messages: an access hash is a capability-granting value, and an
     * error message is exactly where it would leak.
     */
    private function assertTargetUsable(PeerTarget $target, ?PeerMembership $membership): void
    {
        if ((int) ($target->accessHash ?? 0) <= 0) {
            throw new LogicException(sprintf(
                'store bucket "%s": the configured peer target (peer_id %s, peer_type %s) has no '
                .'access hash. Telegram addresses a channel/group by id AND access hash on every '
                .'upload, and hash 0 is the "unknown peer" sentinel — it would be rejected '
                .'mid-upload. Supply the peer access hash in teleframe.tstore.peer_target, or unset '
                .'the target to store to the bucket channel ("%s").',
                $this->bucket,
                (string) $target->peerId,
                $target->peerType,
                self::channelTitle($this->bucket),
            ));
        }

        if ($membership === null) {
            return;
        }

        try {
            $membership->joinPublic($target);
        } catch (BackupException $e) {
            throw $e; // the seam could not even attempt a join — loud
        } catch (Throwable $e) {
            if (! self::isAlreadyMember($e)) {
                throw $e;
            }
            // Already a member: the join is a no-op and the account may post.
        }

        if ($membership->probePostRights($target) === false) {
            throw new LogicException(sprintf(
                'store bucket "%s": the account cannot post in the configured peer target '
                .'(peer_id %s, peer_type %s) — its administrator set is empty, so no identity '
                .'this account holds can post there. Nothing was uploaded. Grant the account '
                .'posting rights, or unset teleframe.tstore.peer_target to store to the bucket '
                .'channel ("%s").',
                $this->bucket,
                (string) $target->peerId,
                $target->peerType,
                self::channelTitle($this->bucket),
            ));
        }

        if ($target->memberAccounts !== []) {
            $membership->ensureMember($target, $target->memberAccounts);
        }
    }

    /**
     * Whether a join failure is the benign "this account is already in the
     * peer" reply. Zero regex: the Telegram error code is a plain substring
     * of the message. Every OTHER join error (a private peer never invited
     * to, an expired invite link, a transport fault) is deliberately NOT
     * tolerated — those are exactly the cases that would otherwise surface
     * as an opaque mid-upload write failure.
     */
    private static function isAlreadyMember(Throwable $e): bool
    {
        return str_contains(strtoupper($e->getMessage()), 'USER_ALREADY_PARTICIPANT');
    }

    /** Channel title for a bucket: CHANNEL_PREFIX + bucket. */
    public static function channelTitle(string $bucket): string
    {
        return self::CHANNEL_PREFIX.$bucket;
    }

    /**
     * Channel over a real scope with the default call map. $rebuild lets
     * the owner rebind the underlying call after a DC migration (see
     * ResilientCall); pass null when the scope cannot rebuild (migrations
     * then rethrow). $peerTarget becomes the destination peer and is
     * gated by $membership (join + post rights) at bind time.
     */
    public static function forScope(
        UserAccountScope $scope,
        string $bucket,
        ?callable $rebuild = null,
        int $maxFloodWaitSeconds = 120,
        ?callable $sleep = null,
        ?PeerTarget $peerTarget = null,
        ?PeerMembership $membership = null,
    ): self {
        return new self(
            $bucket,
            self::scopeApi($scope),
            $rebuild,
            $maxFloodWaitSeconds,
            $sleep,
            $peerTarget,
            // No seam was composed → no join/rights probe, only the
            // access-hash refusal. Documented, not silent: forScope callers
            // that configure a target are expected to pass the seam.
            $membership ?? ($peerTarget === null ? null : new PeerMembership($scope)),
        );
    }

    /**
     * The real teleframe call map (live path; offline tests inject
     * fakes). Shapes:
     *
     * - findChannel(title): ?array{id, access_hash}  dialog scan
     * - createChannel(title, about): array{id, access_hash}
     * - uploadPart(file_id, file_part, file_total_parts, bytes): array  saveBigFilePart
     * - sendDocument(peer, inputFile, caption): int msg id
     * - sendReference(peer, media, caption): int msg id  InputMedia
     *   (Cycle 14: the media array is built by MediaReference::toInputMedia()
     *   — inputMediaDocument/inputDocument or inputMediaPhoto/inputPhoto,
     *   per the reference's MediaType; Telegram already holds the bytes)
     * - sendMessage(peer, text): int msg id
     * - searchMessages(peer, query, limit): list<array{id, name, fetch}>
     * - historyPage(peer, limit, offsetId): list<array{id, name, fetch}>
     * - deleteMessages(peer, ids): array
     * - getFile(location, offset, limit): array{bytes: string}  upload.getFile
     *
     * uploadPart/getFile are invoked through the channel's resilient
     * wrapper, so they need no per-call retry here.
     *
     * @return array<string, callable>
     */
    public static function scopeApi(UserAccountScope $scope): array
    {
        return [
            'findChannel' => function (string $title) use ($scope): ?array {
                $dialogs = $scope->getDialogs(100);
                $chats = $dialogs['chats'] ?? [];
                if (! is_array($chats)) {
                    return null;
                }
                foreach ($chats as $chat) {
                    if (! is_array($chat) || ($chat['_'] ?? '') !== 'channel') {
                        continue;
                    }
                    if (($chat['title'] ?? null) === $title) {
                        return ['id' => (int) $chat['id'], 'access_hash' => (int) ($chat['access_hash'] ?? 0)];
                    }
                }

                return null;
            },
            'createChannel' => function (string $title, string $about) use ($scope): array {
                $updates = $scope->createChannel($title, $about);
                $chats = $updates['chats'] ?? [];
                $chat = is_array($chats) ? ($chats[0] ?? null) : null;
                if (is_array($chat) && isset($chat['id'])) {
                    return ['id' => (int) $chat['id'], 'access_hash' => (int) ($chat['access_hash'] ?? 0)];
                }

                throw new RuntimeException("failed to create store channel {$title}");
            },
            'uploadPart' => function (int $fileId, int $part, int $totalParts, string $bytes) use ($scope): array {
                return $scope->call('upload.saveBigFilePart', [
                    'file_id' => $fileId,
                    'file_part' => $part,
                    // `file_total_parts`, NOT `total_parts`:
                    // upload.saveBigFilePart#de7b673d declares
                    // `file_id:long file_part:int file_total_parts:int bytes:bytes`
                    // (api.tl:2758, api_full.tl:2699, TLRegistry.php:135).
                    // TLEncoder::encodeObject() iterates the REGISTRY's fields
                    // and looks each up BY NAME, so an undeclared key is not
                    // merely ignored — the declared field is then absent and
                    // the encode throws "missing field 'file_total_parts'".
                    // This is the same defect the backup vault carried
                    // (TelegramVault::uploadBytes, cab000e9); here it broke
                    // EVERY putChunk, the store channel's fundamental op.
                    //
                    // NOTE the two other `total_parts` keys in this file are
                    // NOT this: the one in putChunk() (handed to ResilientCall)
                    // and the one dispatchResilient() reads back are INTERNAL
                    // keys of the injected api map, and never reach the
                    // encoder. Renaming them would break the hand-off.
                    'file_total_parts' => $totalParts,
                    'bytes' => $bytes,
                ]);
            },
            'sendDocument' => function (array $peer, array $inputFile, string $caption) use ($scope): int {
                $updates = $scope->sendMedia($peer, [
                    '_' => 'inputMediaUploadedDocument',
                    'file' => $inputFile,
                    'mime_type' => 'application/octet-stream',
                    'attributes' => [
                        ['_' => 'documentAttributeFilename', 'file_name' => (string) ($inputFile['name'] ?? $caption)],
                    ],
                    'force_file' => true,
                ], $caption);

                return self::sentMessageId($scope, $peer, $updates);
            },
            'sendReference' => function (array $peer, array $media, string $caption) use ($scope): int {
                return self::sentMessageId($scope, $peer, $scope->sendMedia($peer, $media, $caption));
            },
            'sendMessage' => fn (array $peer, string $text): int => self::sentMessageId(
                $scope,
                $peer,
                $scope->sendMessage($peer, $text),
            ),
            'searchMessages' => fn (array $peer, string $query, int $limit, int $offsetId = 0): array => self::searchEntries($scope, $peer, $query, $limit, $offsetId),
            'historyPage' => fn (array $peer, int $limit, int $offsetId): array => self::historyEntries($scope, $peer, $limit, $offsetId),
            'deleteMessages' => fn (array $peer, array $ids): array => $scope->deleteMessages($ids, true),
            'getFile' => fn (array $location, int $offset, int $limit): array => $scope->call('upload.getFile', [
                'location' => $location,
                'offset' => $offset,
                'limit' => $limit,
            ]),
        ];
    }

    /**
     * Upload one chunk-document by streaming 512 KiB parts from a seekable
     * handle (never whole-chunk in memory). $chunkOffset is the byte
     * offset of this chunk inside the object file (0 for a single-chunk
     * object); the plan row passed to ChunkPlanner::readPart is rooted at
     * that offset. Returns the sent message id.
     *
     * $transform, when given, receives each plaintext part (plus whether
     * it is the final part of the document) and returns the bytes that
     * are actually uploaded. Sealed-mode callers use this to encrypt the
     * part, prepending the secretstream header to the first part; the
     * returned part bytes may therefore be longer than the plaintext
     * part. null = upload the plaintext verbatim.
     *
     * @param  callable(string, bool): string|null  $transform
     */
    public function putChunk(string $docName, int $chunkSize, $handle, int $chunkOffset = 0, ?callable $transform = null): int
    {
        $chunk = ['n' => 0, 'offset' => $chunkOffset, 'size' => $chunkSize];
        $fileId = random_int(1, PHP_INT_MAX);
        $parts = ChunkPlanner::planParts($chunkSize);
        $totalParts = count($parts);
        $lastIndex = $totalParts - 1;

        foreach ($parts as $index => $part) {
            $bytes = ChunkPlanner::readPart($handle, $chunk, $part);
            if ($transform !== null) {
                $bytes = $transform($bytes, $index === $lastIndex);
            }
            ($this->resilient)('uploadPart', [
                'file_id' => $fileId,
                'file_part' => $part['part'],
                'total_parts' => $totalParts,
                'bytes' => $bytes,
            ]);
        }

        $inputFile = [
            '_' => 'inputFileBig',
            'id' => $fileId,
            'parts' => $totalParts,
            'name' => $docName,
        ];

        return ($this->api['sendDocument'])($this->inputPeer(), $inputFile, $docName);
    }

    /**
     * Stream one chunk-document's parts from the given bytes (a whole
     * 512 KiB part per slice; the caller never holds a full chunk in
     * memory through this path).
     */
    public function putChunkBytes(string $docName, string $bytes): int
    {
        $handle = fopen('php://temp', 'w+b');
        try {
            fwrite($handle, $bytes);
            rewind($handle);

            return $this->putChunk($docName, strlen($bytes), $handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Post a file Telegram ALREADY holds to another peer by reference
     * (Cycle 14, verbatim "for posting them in our public channel we just
     * pass the hash telegram gives"): the message goes out as
     * inputMediaDocument + inputDocument, so no part is ever uploaded and no
     * local copy is needed. Returns the sent message id.
     *
     * $peer is an explicit inputPeer (the target channel/group — NOT this
     * bucket's channel): a file_reference is only addressable from the
     * account that indexed it, which is why publishing across accounts
     * still goes through the normal re-upload path.
     *
     * @param  array<string, mixed>  $peer
     */
    public function sendReference(array $peer, MediaReference $reference, string $caption = ''): int
    {
        if ((int) ($peer['channel_id'] ?? 0) <= 0) {
            throw new InvalidArgumentException('reference publish needs a target peer with a positive channel_id');
        }

        // The media array is NOT built here. Both the outer constructor and
        // the `id` type come off the reference's MediaType, so a photo
        // reference cannot reach the wire as a document. What the encoder
        // does NOT check is why that matters: the declared field is
        // `id:InputDocument`/`id:InputPhoto` (api.tl:37/38) and the old
        // hand-written `document` + `force_file` were BOTH undeclared here,
        // copied from the UPLOADED-document shape (`force_file` is
        // inputMediaUploadedDocument flags.4, api.tl:36). TLEncoder looks
        // declared fields up BY NAME, so every reference publish threw
        // "missing field 'id'"; and the VALUE type is not checked either, so
        // inputDocumentFileLocation (an InputFileLocation, belonging to
        // upload.getFile) would encode cleanly and still be rejected by the
        // server. Both halves of that are now decided in one place.
        return ($this->api['sendReference'])($peer, $reference->toInputMedia(), $caption);
    }

    /**
     * Post a text message to the bucket channel (metadata TFSTORE1 /
     * registry TFREGISTRY1). Returns the message id.
     */
    public function putMessage(string $text): int
    {
        return ($this->api['sendMessage'])($this->inputPeer(), $text);
    }

    /**
     * Post the registry message for this bucket if none exists yet
     * (idempotent re-post guarded by search). Returns the message id or
     * null when a registry message already existed.
     */
    public function ensureRegistry(?array $extra = null): ?int
    {
        foreach ($this->findMessagesByName(ChannelRegistry::MARKER) as $entry) {
            $name = (string) ($entry['name'] ?? '');
            if (str_starts_with($name, ChannelRegistry::MARKER)) {
                return null; // already registered
            }
        }

        return $this->putMessage(ChannelRegistry::render(
            ChannelRegistry::KIND_STORE,
            $this->bucket,
            $extra,
        ));
    }

    /**
     * Find TStore messages by a name/query prefix ('' = all). Entries are
     * {id, name, fetch()} — fetch() takes NO arguments and streams the
     * document back. Searches the destination peer first, then the bucket's
     * own channel (objects written before a target was configured); use
     * locate() when the caller needs to know WHICH peer answered.
     *
     * @return list<array<string, mixed>>
     */
    public function findMessagesByName(string $prefix, int $limit = 0): array
    {
        $found = $this->locate($prefix, $limit);

        return $found === null ? [] : $found['entries'];
    }

    /**
     * Walk the full history uncapped (offset_id cursor), the same shape
     * as findMessagesByName — used by syncRegistry and index walks.
     *
     * @return list<array<string, mixed>>
     */
    public function listAllEntries(int $pageSize = 100, int $hardCap = 10000): array
    {
        $entries = [];
        foreach ($this->walkHistory($pageSize, $hardCap) as $row) {
            $entries[] = ['id' => (string) $row['id'], 'name' => (string) $row['name']];
        }

        return $entries;
    }

    /**
     * Walk the full history and return the RAW message rows (media
     * included) — the media-hash index walk (S3) needs document metadata
     * (doc_id, access_hash, file_reference, size, mime) that the slim
     * listAllEntries shape drops.
     *
     * @return list<array<string, mixed>> rows carry id, name, media
     */
    public function listAllRows(int $pageSize = 100, int $hardCap = 10000): array
    {
        return $this->walkHistory($pageSize, $hardCap);
    }

    /**
     * Shared uncapped history crawl (offset_id cursor, dedup, progress
     * guard, hard cap), walked per read peer with an INDEPENDENT cursor:
     * each peer is its own chat with its own message-id space, so paging
     * one peer's ids against another's would skip messages. Returns full
     * rows; name is resolved the same way rowsToEntries resolves it, so
     * callers see a stable {id, name, ...} shape on top of the raw message.
     *
     * @return list<array<string, mixed>>
     */
    private function walkHistory(int $pageSize, int $hardCap): array
    {
        $rows = [];
        $seen = [];
        $fetched = 0;

        foreach ($this->readPeers() as $peer) {
            $peerRows = $this->walkPeerHistory($peer, $pageSize, $hardCap, $fetched);
            foreach ($peerRows as $row) {
                $id = (int) $row['id'];
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $rows[] = $row;
            }
            $fetched += count($peerRows);
            if ($fetched > $hardCap) {
                throw new RuntimeException(
                    'store channel '.$this->bucket.' exceeds the '.$hardCap.'-entry listing hard cap; aborting for manual review',
                );
            }
        }

        return $rows;
    }

    /**
     * One peer's history, paged below $offsetId (0 = newest) until empty.
     *
     * @param  array<string, mixed>  $peer
     * @param  int  $fetched  entries already seen in earlier peers (hard cap)
     * @return list<array<string, mixed>>
     */
    private function walkPeerHistory(array $peer, int $pageSize, int $hardCap, int $fetched): array
    {
        $rows = [];
        $offsetId = 0;
        while (true) {
            $page = ($this->api['historyPage'])($peer, $pageSize, $offsetId);
            $page = is_array($page) ? array_values(array_filter($page, 'is_array')) : [];
            if ($page === []) {
                break;
            }

            $fetched += count($page);
            if ($fetched > $hardCap) {
                throw new RuntimeException(
                    'store channel '.$this->bucket.' exceeds the '.$hardCap.'-entry listing hard cap; aborting for manual review',
                );
            }

            $oldest = 0;
            foreach ($page as $row) {
                $id = (int) ($row['id'] ?? 0);
                $oldest = $oldest === 0 ? $id : min($oldest, $id);
                // historyPage rows arrive as {id, name, fetch} entry shapes;
                // when a raw message row slips through (media index walk)
                // resolve the name from the document filename/message text.
                $name = is_string($row['name'] ?? null) && $row['name'] !== ''
                    ? $row['name']
                    : (self::messageName($row) ?? '');
                $rows[] = ['id' => $id, 'name' => $name, 'media' => $row['media'] ?? null];
            }

            if ($oldest <= 0 || ($offsetId !== 0 && $oldest >= $offsetId)) {
                throw new RuntimeException(
                    'store channel '.$this->bucket.' history pagination made no forward progress at offset_id '.$offsetId,
                );
            }
            $offsetId = $oldest;
        }

        return $rows;
    }

    /**
     * Fetch a document message's bytes via the upload.getFile part loop.
     * The message is one of the {id, name, fetch} entries returned by
     * findMessagesByName/listAllEntries (its fetch closure does the same
     * work — this is the direct form taking a raw message row).
     */
    public function fetchDocument(array $message): string
    {
        $document = $message['media']['document'] ?? null;
        if (! is_array($document) || ($document['_'] ?? '') !== 'document') {
            throw new RuntimeException('message carries no downloadable document');
        }

        $location = [
            '_' => 'inputDocumentFileLocation',
            'id' => $document['id'] ?? 0,
            'access_hash' => $document['access_hash'] ?? 0,
            'file_reference' => $document['file_reference'] ?? '',
            'thumb_size' => '',
        ];

        $bytes = '';
        $offset = 0;
        while (true) {
            $file = ($this->resilient)('getFile', [
                'location' => $location,
                'offset' => $offset,
                'limit' => self::PART_SIZE,
            ]);
            $part = is_string($file['bytes'] ?? null) ? $file['bytes'] : '';
            if ($part === '') {
                break;
            }
            $bytes .= $part;
            $offset += strlen($part);
            if (strlen($part) < self::PART_SIZE) {
                break;
            }
        }

        return $bytes;
    }

    /**
     * Delete messages by id from the DESTINATION peer (revoke). Idempotent:
     * absent ids delete nothing. An object written before a target was
     * configured lives in the bucket's own channel — use
     * deleteMessagesIn() with the peer its manifest came from when the
     * index row says so.
     *
     * @param  list<int>  $ids
     */
    public function deleteMessages(array $ids): void
    {
        $this->deleteMessagesIn($this->inputPeer(), $ids);
    }

    public function bucket(): string
    {
        return $this->bucket;
    }

    /**
     * Route a resilient call to the api map. Only uploadPart/getFile are
     * wrapped (they are the long multi-part loops where a flood or DC
     * migration must not abort mid-stream); one-shot calls run directly.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function dispatchResilient(string $method, array $params): array
    {
        if ($method === 'uploadPart') {
            return ($this->api['uploadPart'])(
                (int) $params['file_id'],
                (int) $params['file_part'],
                (int) $params['total_parts'],
                (string) $params['bytes'],
            );
        }
        if ($method === 'getFile') {
            return ($this->api['getFile'])(
                (array) $params['location'],
                (int) $params['offset'],
                (int) $params['limit'],
            );
        }

        throw new LogicException("method {$method} is not in the resilient set");
    }

    /** Lazily resolved (find-or-create) and cached channel id. */
    public function channelId(): int
    {
        if ($this->channelId !== null) {
            return $this->channelId;
        }

        $title = self::channelTitle($this->bucket);
        $found = ($this->api['findChannel'])($title);
        $ref = is_array($found)
            ? ['id' => (int) $found['id'], 'access_hash' => (int) ($found['access_hash'] ?? 0)]
            : ($this->api['createChannel'])($title, self::CHANNEL_ABOUT);
        $this->channelId = (int) $ref['id'];
        $this->channelAccessHash = (int) ($ref['access_hash'] ?? 0);

        return $this->channelId;
    }

    /**
     * THE DESTINATION PEER: the configured target when there is one,
     * otherwise the bucket's own channel. Every write goes here (chunk
     * documents, the TFSTORE1 manifest message, the TFREGISTRY1 registry
     * message) — co-located by construction, which is what makes a restore
     * able to find every chunk. Reads search this peer first and fall back
     * to bucketInputPeer() for objects written before a target existed.
     *
     * @return array<string, mixed> MTProto inputPeerChannel
     */
    public function inputPeer(): array
    {
        if ($this->peerTarget !== null) {
            return $this->peerTarget->inputPeer();
        }

        return $this->bucketInputPeer();
    }

    /**
     * The bucket's OWN channel, whatever the target is. It stays reachable
     * for the legacy read fallback and for the bucket identity; it is never
     * a write destination once a target is configured.
     *
     * @return array<string, mixed> MTProto inputPeerChannel
     */
    public function bucketInputPeer(): array
    {
        $this->channelId();

        return ['_' => 'inputPeerChannel', 'channel_id' => $this->channelId, 'access_hash' => $this->channelAccessHash];
    }

    /**
     * The configured target, or null when this channel writes to its own
     * bucket channel. Lets a caller tell "which destination am I talking
     * to" without inspecting peer arrays.
     */
    public function peerTarget(): ?PeerTarget
    {
        return $this->peerTarget;
    }

    /**
     * The ordered READ peers: the destination peer first, then the bucket's
     * own channel for objects written before the target was configured.
     * Exactly one entry when no target is configured, so the default path is
     * byte-for-byte what it always was.
     *
     * @return list<array<string, mixed>>
     */
    public function readPeers(): array
    {
        $peers = [$this->inputPeer()];
        if ($this->peerTarget === null) {
            return $peers;
        }

        $legacy = $this->bucketInputPeer();
        if ((int) ($legacy['channel_id'] ?? 0) !== (int) ($peers[0]['channel_id'] ?? 0)) {
            $peers[] = $legacy;
        }

        return $peers;
    }

    /**
     * Locate a name in the ordered read peers, returning BOTH the entries
     * and the peer they were found in. The peer is the point: a caller that
     * resolves a MANIFEST here must read that object's chunks from the same
     * peer (findMessagesByNameIn), not fan out — otherwise a second peer can
     * shadow a chunk with a same-named document.
     *
     * @return array{peer: array<string, mixed>, entries: list<array<string, mixed>>}|null null when no peer holds the name
     */
    public function locate(string $prefix, int $limit = 0): ?array
    {
        $searchLimit = $limit > 0 ? $limit : self::SEARCH_LIMIT;
        foreach ($this->readPeers() as $peer) {
            $rows = ($this->api['searchMessages'])($peer, $prefix, $searchLimit);
            $rows = is_array($rows) ? $rows : [];
            if ($rows !== []) {
                return ['peer' => $peer, 'entries' => $rows];
            }
        }

        return null;
    }

    /**
     * Find ONE message by its Telegram message id, walking the read peers'
     * history DOWN to that id. Returns the message row AND the peer it lives
     * in, so a caller that found a document there can address it without a
     * fan-out (a file_reference is only valid for the account and chat that
     * produced it).
     *
     * Why a walk and not a search: messages.search takes a QUERY, and a
     * message id is not a query — the only way to address a message by id is
     * to page history. The walk is BOUNDED twice over: it stops as soon as the
     * id is found, and it stops a peer as soon as a page's oldest id is BELOW
     * the id we want (that id is newer than everything further down, so it can
     * never appear again). A peer whose history makes no forward progress is
     * abandoned rather than looped on.
     *
     * @return array{peer: array<string, mixed>, message: array<string, mixed>}|null
     */
    public function findMessageById(int $msgId, int $pageSize = 100): ?array
    {
        if ($msgId <= 0 || $pageSize <= 0) {
            return null;
        }

        foreach ($this->readPeers() as $peer) {
            $offsetId = 0;
            while (true) {
                $page = ($this->api['historyPage'])($peer, $pageSize, $offsetId);
                $page = is_array($page) ? array_values(array_filter($page, 'is_array')) : [];
                if ($page === []) {
                    break;
                }

                $oldest = 0;
                foreach ($page as $row) {
                    $id = (int) ($row['id'] ?? 0);
                    if ($id === $msgId) {
                        return ['peer' => $peer, 'message' => $row];
                    }
                    $oldest = $oldest === 0 ? $id : min($oldest, $id);
                }

                if ($oldest <= 0 || ($offsetId !== 0 && $oldest >= $offsetId)) {
                    break; // no forward progress — abandon this peer
                }
                if ($offsetId !== 0 && $msgId > $oldest) {
                    break; // paged below the id: it is newer than this page
                }
                $offsetId = $oldest;
            }
        }

        return null;
    }

    /**
     * Find TStore messages by a name/query prefix ('' = all) in ONE
     * specific peer — the pinned form used for chunk reads and deletes once
     * the owning manifest has been located.
     *
     * @return list<array<string, mixed>>
     */
    public function findMessagesByNameIn(array $peer, string $prefix, int $limit = 0, int $offsetId = 0): array
    {
        $rows = ($this->api['searchMessages'])($peer, $prefix, $limit > 0 ? $limit : self::SEARCH_LIMIT, $offsetId);

        return is_array($rows) ? $rows : [];
    }

    /**
     * EVERY message in $peer whose name/text contains $prefix — the whole
     * substring result set, paged to exhaustion.
     *
     * WHY THIS EXISTS AND WHY ONE PAGE IS NOT ENOUGH. findMessagesByNameIn()
     * takes at most $limit hits, which is correct for a READ (fetch this
     * chunk: the first match is the match) and wrong for a DELETE. `q` is a
     * server-side SUBSTRING match, so the number of hits for a prefix is
     * decided by the peer's contents: "TFSTORE1: foo" matches "TFSTORE1: foo",
     * "TFSTORE1: foo-bar", and everything else sharing that stem. A caller that
     * filters those hits down to an exact name — which is what a revoke must do,
     * so a neighbour is never deleted with its target — cannot treat one page as
     * the answer, because the exact row may be ranked below the cut. Under a
     * cap the delete silently keeps the row: `exists()` then reports the object
     * gone while its metadata message is still in the chat.
     *
     * WHY KEYSET PAGES AND NOT A BIGGER LIMIT. A larger constant is the same
     * defect at a different rank: it moves the line where rows start surviving
     * instead of removing the line. The bound is not what makes the query
     * affordable — SEARCH_LIMIT is now a PAGE SIZE, one round trip's worth of
     * rows — and cost is charged per page actually fetched, so a peer with one
     * matching row still costs one call, exactly as before.
     *
     * The walk stops on a short page (the server has no more hits) and on a page
     * that fails to move the cursor. The second guard is not defensive
     * decoration: it is what makes this safe against a peer or a test double
     * that ignores offset_id, which would otherwise return the same page
     * forever. findMessageById() abandons a peer on the same condition.
     *
     * @return list<array<string, mixed>>
     */
    public function findAllMessagesByNameIn(array $peer, string $prefix): array
    {
        $rows = [];
        $offsetId = 0;

        while (true) {
            $page = $this->findMessagesByNameIn($peer, $prefix, self::SEARCH_LIMIT, $offsetId);

            if ($page === []) {
                break;
            }

            $oldest = 0;
            foreach ($page as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $rows[] = $row;
                $oldest = $oldest === 0 ? $id : min($oldest, $id);
            }

            // A short page is the last page: the server had fewer hits left
            // than it was asked for.
            if (count($page) < self::SEARCH_LIMIT || $oldest <= 0) {
                break;
            }

            // No forward progress — the peer or the double is not honouring
            // offset_id. Stop rather than re-fetch the same page forever.
            if ($offsetId !== 0 && $oldest >= $offsetId) {
                break;
            }

            $offsetId = $oldest;
        }

        return $rows;
    }

    /**
     * Delete messages by id from ONE specific peer (revoke). Idempotent:
     * absent ids delete nothing.
     *
     * @param  list<int>  $ids
     */
    public function deleteMessagesIn(array $peer, array $ids): void
    {
        if ($ids !== []) {
            ($this->api['deleteMessages'])($peer, $ids);
        }
    }

    /**
     * messages.search by $prefix mapped to entries; name is the document
     * file name for documents and the message text otherwise (markers
     * match either). fetch() streams the document back.
     *
     * $offsetId pages the search: messages.search returns hits strictly OLDER
     * than $offsetId, so a caller that needs every hit walks with the page's
     * smallest id (see findAllMessagesByNameIn). A single call is one page, not
     * the result set — 'q' is a SUBSTRING match, so how many rows exist for a
     * prefix is decided by the peer's contents, not by anything this side knows.
     *
     * @return list<array<string, mixed>>
     */
    private static function searchEntries(UserAccountScope $scope, array $peer, string $prefix, int $limit, int $offsetId = 0): array
    {
        $rows = self::resultRows($scope->searchMessages($peer, $prefix, $limit, ['offset_id' => $offsetId]));

        return self::rowsToEntries($scope, $rows);
    }

    /**
     * One messages.getHistory page strictly below $offsetId (0 = newest).
     *
     * @return list<array<string, mixed>>
     */
    private static function historyEntries(UserAccountScope $scope, array $peer, int $limit, int $offsetId): array
    {
        $rows = self::resultRows($scope->getHistory($peer, $limit, $offsetId));

        return self::rowsToEntries($scope, $rows);
    }

    /** @return list<array<string, mixed>> */
    private static function rowsToEntries(UserAccountScope $scope, array $rows): array
    {
        $entries = [];
        foreach ($rows as $message) {
            $name = self::messageName($message);
            if ($name === null) {
                continue;
            }
            $entries[] = [
                'id' => (int) ($message['id'] ?? 0),
                'name' => $name,
                'media' => $message['media'] ?? null,
                // Zero-arg on purpose: BOTH production call sites — StoreService::fetchChunkPlain()
                // and TelegramTier::entryText() — invoke it with NO arguments, because the
                // closure already closed over $message and needs no name. A required
                // `string $fetchName` here raised ArgumentCountError at the call site, which
                // is an Error and NOT an Exception, so it escaped every catch (RuntimeException)
                // in this subsystem (verify()'s per-chunk catch included) instead of becoming
                // a clean failure. The Backup twin (TelegramVault) keeps its parameter: there
                // the caller DOES pass a name, and its own fake indexes by it.
                'fetch' => fn (): string => self::downloadDocument($scope, $message),
            ];
        }

        return $entries;
    }

    /** @return list<array<string, mixed>> */
    private static function resultRows(array $result): array
    {
        $rows = $result['messages'] ?? [];

        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    /** Document file name, else the message text; null when neither applies. */
    private static function messageName(array $message): ?string
    {
        $document = $message['media']['document'] ?? null;
        if (is_array($document)) {
            $attributes = $document['attributes'] ?? [];
            if (is_array($attributes)) {
                foreach ($attributes as $attribute) {
                    if (is_array($attribute)
                        && ($attribute['_'] ?? '') === 'documentAttributeFilename'
                        && is_string($attribute['file_name'] ?? null)
                        && $attribute['file_name'] !== ''
                    ) {
                        return $attribute['file_name'];
                    }
                }
            }
        }

        $text = $message['message'] ?? null;

        return is_string($text) && $text !== '' ? $text : null;
    }

    /** upload.getFile part loop over a message's document. */
    private static function downloadDocument(UserAccountScope $scope, array $message): string
    {
        $document = $message['media']['document'] ?? null;
        if (! is_array($document) || ($document['_'] ?? '') !== 'document') {
            throw new RuntimeException('message carries no downloadable document');
        }

        $location = [
            '_' => 'inputDocumentFileLocation',
            'id' => $document['id'] ?? 0,
            'access_hash' => $document['access_hash'] ?? 0,
            'file_reference' => $document['file_reference'] ?? '',
            'thumb_size' => '',
        ];

        $bytes = '';
        $offset = 0;
        while (true) {
            $file = $scope->call('upload.getFile', [
                'location' => $location,
                'offset' => $offset,
                'limit' => self::PART_SIZE,
            ]);
            $part = is_string($file['bytes'] ?? null) ? $file['bytes'] : '';
            if ($part === '') {
                break;
            }
            $bytes .= $part;
            $offset += strlen($part);
            if (strlen($part) < self::PART_SIZE) {
                break;
            }
        }

        return $bytes;
    }

    /**
     * Pull the sent message id out of a send{Message,Media} updates reply;
     * updatesTooLong-shaped replies fall back to the newest history row.
     */
    private static function sentMessageId(UserAccountScope $scope, array $peer, array $updates): int
    {
        $messages = $updates['messages'] ?? null;
        $first = is_array($messages) ? ($messages[0] ?? null) : null;
        $id = is_array($first) ? ($first['id'] ?? null) : ($updates['id'] ?? null);
        if (is_numeric($id)) {
            return (int) $id;
        }

        $history = $scope->getHistory($peer, 1);
        $rows = is_array($history['messages'] ?? null) ? $history['messages'] : [];
        $newest = $rows[0] ?? null;
        if (is_array($newest) && is_numeric($newest['id'] ?? null)) {
            return (int) $newest['id'];
        }

        throw new RuntimeException('cannot resolve sent message id in channel '.($peer['channel_id'] ?? '?'));
    }
}
