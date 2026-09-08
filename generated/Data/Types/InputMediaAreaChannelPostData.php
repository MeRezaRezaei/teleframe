<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaAreaChannelPost of MediaArea.
 */
final class InputMediaAreaChannelPostData extends TlMediaAreaAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMediaAreaCoordinatesAbstractData $coordinates,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputChannelAbstractData $channel,
    public int $msgId,
    ) {
    }
}
