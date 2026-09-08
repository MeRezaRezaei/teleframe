<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for photoSize of PhotoSize.
 */
final class PhotoSizeData extends TlPhotoSizeAbstractData
{
    public function __construct(
    public string $type,
    public int $w,
    public int $h,
    public int $size,
    ) {
    }
}
