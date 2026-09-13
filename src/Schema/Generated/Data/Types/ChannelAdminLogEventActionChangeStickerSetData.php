<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionChangeStickerSet of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionChangeStickerSetData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputStickerSetAbstractData $prevStickerset,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputStickerSetAbstractData $newStickerset,
    ) {
    }
}
