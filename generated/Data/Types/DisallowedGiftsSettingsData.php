<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for disallowedGiftsSettings of DisallowedGiftsSettings.
 */
final class DisallowedGiftsSettingsData extends TlDisallowedGiftsSettingsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'disallowUnlimitedStargifts' => ['flags', 0],
        'disallowLimitedStargifts' => ['flags', 1],
        'disallowUniqueStargifts' => ['flags', 2],
        'disallowPremiumGifts' => ['flags', 3],
        'disallowStargiftsFromChannels' => ['flags', 4],
    ];

    public function __construct(
    public int $flags,
    public ?bool $disallowUnlimitedStargifts,
    public ?bool $disallowLimitedStargifts,
    public ?bool $disallowUniqueStargifts,
    public ?bool $disallowPremiumGifts,
    public ?bool $disallowStargiftsFromChannels,
    ) {
    }
}
