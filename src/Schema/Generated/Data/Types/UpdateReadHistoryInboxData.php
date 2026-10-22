<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateReadHistoryInbox of Update.
 */
final class UpdateReadHistoryInboxData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public ?int $folderId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public ?int $topMsgId,
    public int $maxId,
    public int $stillUnreadCount,
    public int $pts,
    public int $ptsCount,
    ) {
    }
}
