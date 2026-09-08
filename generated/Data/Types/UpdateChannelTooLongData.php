<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateChannelTooLong of Update.
 */
final class UpdateChannelTooLongData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public int $channelId,
    public ?int $pts,
    ) {
    }
}
