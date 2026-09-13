<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starsGiveawayOption of StarsGiveawayOption.
 */
final class StarsGiveawayOptionData extends TlStarsGiveawayOptionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'extended' => ['flags', 0],
        'default' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $extended,
    public ?bool $default,
    public int $stars,
    public int $yearlyBoosts,
    public ?string $storeProduct,
    public string $currency,
    public int $amount,
    public array $winners,
    ) {
    }
}
