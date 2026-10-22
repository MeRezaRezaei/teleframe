<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for langPackStringPluralized of LangPackString.
 */
final class LangPackStringPluralizedData extends TlLangPackStringAbstractData
{
    public function __construct(
    public int $flags,
    public string $key,
    public ?string $zeroValue,
    public ?string $oneValue,
    public ?string $twoValue,
    public ?string $fewValue,
    public ?string $manyValue,
    public string $otherValue,
    ) {
    }
}
