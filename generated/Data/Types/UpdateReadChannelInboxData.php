<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateReadChannelInbox of Update.
 */
final class UpdateReadChannelInboxData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public ?int $folderId,
    public int $channelId,
    public int $maxId,
    public int $stillUnreadCount,
    public int $pts,
    ) {
    }
}
