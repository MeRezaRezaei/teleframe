<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for photoStrippedSize of PhotoSize.
 *
 * bytes params carried as base64 strings: bytes
 */
final class PhotoStrippedSizeData extends TlPhotoSizeAbstractData
{
    public function __construct(
    public string $type,
    public string $bytes,
    ) {
    }
}
