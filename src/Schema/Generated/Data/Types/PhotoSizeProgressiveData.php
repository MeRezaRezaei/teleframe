<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for photoSizeProgressive of PhotoSize.
 */
final class PhotoSizeProgressiveData extends TlPhotoSizeAbstractData
{
    public function __construct(
    public string $type,
    public int $w,
    public int $h,
    public array $sizes,
    ) {
    }
}
