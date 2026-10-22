<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for photos.photosSlice of photos.Photos.
 */
final class TlPhotosPhotosSliceData extends TlPhotosPhotosAbstractData
{
    public function __construct(
    public int $count,
    public array $photos,
    public array $users,
    ) {
    }
}
