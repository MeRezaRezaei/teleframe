<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateChannelMessageViews of Update.
 */
final class UpdateChannelMessageViewsData extends TlUpdateAbstractData
{
    public function __construct(
    public int $channelId,
    public int $id,
    public int $views,
    ) {
    }
}
