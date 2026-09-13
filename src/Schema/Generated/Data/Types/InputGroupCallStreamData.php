<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputGroupCallStream of InputFileLocation.
 */
final class InputGroupCallStreamData extends TlInputFileLocationAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputGroupCallAbstractData $call,
    public int $timeMs,
    public int $scale,
    public ?int $videoChannel,
    public ?int $videoQuality,
    ) {
    }
}
