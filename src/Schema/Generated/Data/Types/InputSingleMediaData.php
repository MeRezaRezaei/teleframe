<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputSingleMedia of InputSingleMedia.
 */
final class InputSingleMediaData extends TlInputSingleMediaAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputMediaAbstractData $media,
    public int $randomId,
    public string $message,
    public ?array $entities,
    ) {
    }
}
