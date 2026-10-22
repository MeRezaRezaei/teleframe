<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftUnique of StarGift.
 */
final class StarGiftUniqueData extends TlStarGiftAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'requirePremium' => ['flags', 6],
        'resaleTonOnly' => ['flags', 7],
        'themeAvailable' => ['flags', 9],
        'burned' => ['flags', 14],
        'crafted' => ['flags', 15],
    ];

    public function __construct(
    public int $flags,
    public ?bool $requirePremium,
    public ?bool $resaleTonOnly,
    public ?bool $themeAvailable,
    public ?bool $burned,
    public ?bool $crafted,
    public int $id,
    public int $giftId,
    public string $title,
    public string $slug,
    public int $num,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $ownerId,
    public ?string $ownerName,
    public ?string $ownerAddress,
    public array $attributes,
    public int $availabilityIssued,
    public int $availabilityTotal,
    public ?string $giftAddress,
    public ?array $resellAmount,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $releasedBy,
    public ?int $valueAmount,
    public ?string $valueCurrency,
    public ?int $valueUsdAmount,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $themePeer,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerColorAbstractData $peerColor,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $hostId,
    public ?int $offerMinStars,
    public ?int $craftChancePermille,
    ) {
    }
}
