<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Realtime;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Store\ChannelRegistry;
use MeRezaRezaei\Teleframe\Store\Index\StoreIndexContract;
use MeRezaRezaei\Teleframe\Store\MediaType;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * UpdateStored → TStore media index bridge (plan W9, verbatim S3/S11):
 * when the ingested root model is a message from one of this account's
 * declared DATA channels and it carries document media, upsert a
 * tf_store_media presence row keyed (account_id, bucket, msg_id).
 *
 * The mirror defers the nested Document object (no doc_id/access_hash/
 * file_reference linkage in tf_messages_media), so IDENTITY fields stay
 * lazy here — the `teleframe:tstore index` history walk fills them with
 * the exact Telegram-native values, and `StoreService::archiveMessageMedia`
 * (the raw-update bus path) indexes full identity directly. The listener
 * is therefore an OPTIONAL promptness optimization: it makes freshly
 * landed data-channel media visible in tf_store_media immediately, at
 * message-granularity, without waiting for the next reconcile walk.
 *
 * Failures never throw into the ingest transaction: the mirror is the
 * source of truth, this is a projection. A failed index is logged (if a
 * logger was injected) and dropped.
 */
final class UpdateStoredTstoreListener
{
    public function __construct(
        private readonly StoreIndexContract $index,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function __invoke(UpdateStored $stored): void
    {
        $model = $stored->model;
        if (! $model instanceof TfMessage) {
            return;
        }

        try {
            $this->indexMessage($stored->accountId, $model);
        } catch (Throwable $e) {
            $this->logger?->warning('tstore live media index failed', [
                'account_id' => $stored->accountId,
                'message_id' => $model->getAttribute('id'),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function indexMessage(int $accountId, TfMessage $message): void
    {
        $peerType = (int) ($message->getAttribute('peer_type') ?? 0);
        $peerId = (int) ($message->getAttribute('peer_id') ?? 0);
        $msgId = (int) ($message->getAttribute('id') ?? 0);
        if ($peerId <= 0 || $msgId <= 0) {
            return;
        }

        $bucket = $this->dataBucketFor($accountId, $peerType, $peerId);
        if ($bucket === null) {
            return;
        }

        $media = $message->getRelationValue('media');
        if (! $media instanceof Model) {
            // The media child row exists only when the ingest saw media.
            return;
        }

        $ctor = (string) ($media->getAttribute('constructor') ?? '');
        if (! in_array($ctor, ['messageMediaDocument', 'messageMediaWebPage'], true)) {
            return;
        }

        // Presence row only: the mirror defers the nested Document object,
        // so doc identity + size/mime stay 0/'' here. The history reconcile
        // walk (`teleframe:tstore index`) and the raw bus path
        // (StoreService::archiveMessageMedia) overwrite the row with exact
        // Telegram-native values on the same natural key.
        $this->index->upsertMedia($accountId, $bucket, $msgId, [
            'media_type' => MediaType::Document->value,
            'doc_id' => 0,
            'access_hash' => 0,
            'file_reference' => '',
            'size' => 0,
            'mime' => '',
            'content_sha256' => null,
            'indexed_at_unix' => time(),
        ]);
    }

    private function dataBucketFor(int $accountId, int $peerType, int $peerId): ?string
    {
        foreach ($this->index->listBuckets($accountId, ChannelRegistry::KIND_DATA) as $row) {
            // peer_type 1/2/3 → user/chat/channel; the data-channel truth
            // rows are channels (peer_type 3). A chat or user peer never
            // matches a data bucket.
            if ($peerType !== 3) {
                continue;
            }
            if ((int) ($row['channel_id'] ?? 0) === $peerId) {
                return (string) $row['bucket'];
            }
        }

        return null;
    }
}
