<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageExtendedMedia of MessageExtendedMedia.
 */
final class MessageExtendedMediaData extends TlMessageExtendedMediaAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $media,
    ) {
    }
}
