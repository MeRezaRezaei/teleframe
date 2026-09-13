<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftAuctionState of StarGiftAuctionState.
 */
final class StarGiftAuctionStateData extends TlStarGiftAuctionStateAbstractData
{
    public function __construct(
    public int $version,
    public int $startDate,
    public int $endDate,
    public int $minBidAmount,
    public array $bidLevels,
    public array $topBidders,
    public int $nextRoundAt,
    public int $lastGiftNum,
    public int $giftsLeft,
    public int $currentRound,
    public int $totalRounds,
    public array $rounds,
    ) {
    }
}
