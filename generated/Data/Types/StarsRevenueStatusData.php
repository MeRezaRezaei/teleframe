<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starsRevenueStatus of StarsRevenueStatus.
 */
final class StarsRevenueStatusData extends TlStarsRevenueStatusAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'withdrawalEnabled' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $withdrawalEnabled,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $currentBalance,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $availableBalance,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $overallRevenue,
    public ?int $nextWithdrawalAt,
    ) {
    }
}
