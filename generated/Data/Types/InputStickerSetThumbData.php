<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputStickerSetThumb of InputFileLocation.
 */
final class InputStickerSetThumbData extends TlInputFileLocationAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputStickerSetAbstractData $stickerset,
    public int $thumbVersion,
    ) {
    }
}
