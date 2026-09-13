<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftAuctionAcquiredGift of StarGiftAuctionAcquiredGift.
 */
final class StarGiftAuctionAcquiredGiftData extends TlStarGiftAuctionAcquiredGiftAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'nameHidden' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $nameHidden,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $date,
    public int $bidAmount,
    public int $round,
    public int $pos,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $message,
    public ?int $giftNum,
    ) {
    }
}
