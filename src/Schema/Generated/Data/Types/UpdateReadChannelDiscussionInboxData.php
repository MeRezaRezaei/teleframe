<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateReadChannelDiscussionInbox of Update.
 */
final class UpdateReadChannelDiscussionInboxData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public int $channelId,
    public int $topMsgId,
    public int $readMaxId,
    public ?int $broadcastId,
    public ?int $broadcastPost,
    ) {
    }
}
