<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for photos.photos of photos.Photos.
 */
final class TlPhotosPhotosData extends TlPhotosPhotosAbstractData
{
    public function __construct(
    public array $photos,
    public array $users,
    ) {
    }
}
