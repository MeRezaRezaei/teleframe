<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for premium.boostsStatus of premium.BoostsStatus.
 */
final class TlPremiumBoostsStatusData extends TlPremiumBoostsStatusAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'myBoost' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $myBoost,
    public int $level,
    public int $currentLevelBoosts,
    public int $boosts,
    public ?int $giftBoosts,
    public ?int $nextLevelBoosts,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsPercentValueAbstractData $premiumAudience,
    public string $boostUrl,
    public ?array $prepaidGiveaways,
    public ?array $myBoostSlots,
    ) {
    }
}
