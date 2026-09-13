<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateChannelViewForumAsMessages of Update.
 */
final class UpdateChannelViewForumAsMessagesData extends TlUpdateAbstractData
{
    public function __construct(
    public int $channelId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $enabled,
    ) {
    }
}
