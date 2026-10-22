<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaPaidMedia of InputMedia.
 */
final class InputMediaPaidMediaData extends TlInputMediaAbstractData
{
    public function __construct(
    public int $flags,
    public int $starsAmount,
    public array $extendedMedia,
    public ?string $payload,
    ) {
    }
}
