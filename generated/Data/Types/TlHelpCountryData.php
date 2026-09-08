<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.country of help.Country.
 */
final class TlHelpCountryData extends TlHelpCountryAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hidden' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hidden,
    public string $iso2,
    public string $defaultName,
    public ?string $name,
    public array $countryCodes,
    ) {
    }
}
