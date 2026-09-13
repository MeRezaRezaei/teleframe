<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateChannelReadMessagesContents of Update.
 */
final class UpdateChannelReadMessagesContentsData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public int $channelId,
    public ?int $topMsgId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $savedPeerId,
    public array $messages,
    ) {
    }
}
