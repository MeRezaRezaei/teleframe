<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for mediaAreaChannelPost of MediaArea.
 */
final class MediaAreaChannelPostData extends TlMediaAreaAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMediaAreaCoordinatesAbstractData $coordinates,
    public int $channelId,
    public int $msgId,
    ) {
    }
}
