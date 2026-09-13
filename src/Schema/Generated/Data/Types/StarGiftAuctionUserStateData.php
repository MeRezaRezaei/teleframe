<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftAuctionUserState of StarGiftAuctionUserState.
 */
final class StarGiftAuctionUserStateData extends TlStarGiftAuctionUserStateAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'returned' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $returned,
    public ?int $bidAmount,
    public ?int $bidDate,
    public ?int $minBidAmount,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $bidPeer,
    public int $acquiredCount,
    ) {
    }
}
