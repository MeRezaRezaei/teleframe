<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionStarGiftPurchaseOffer of MessageAction.
 */
final class MessageActionStarGiftPurchaseOfferData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'accepted' => ['flags', 0],
        'declined' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $accepted,
    public ?bool $declined,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAbstractData $gift,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $price,
    public int $expiresAt,
    ) {
    }
}
