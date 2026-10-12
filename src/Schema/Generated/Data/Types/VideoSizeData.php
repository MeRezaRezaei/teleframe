<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for videoSize of VideoSize.
 */
final class VideoSizeData extends TlVideoSizeAbstractData
{
    public function __construct(
    public int $flags,
    public string $type,
    public int $w,
    public int $h,
    public int $size,
    public ?float $videoStartTs,
    ) {
    }
}
