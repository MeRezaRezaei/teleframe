<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for videoSizeStickerMarkup of VideoSize.
 */
final class VideoSizeStickerMarkupData extends TlVideoSizeAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputStickerSetAbstractData $stickerset,
    public int $stickerId,
    public array $backgroundColors,
    ) {
    }
}
