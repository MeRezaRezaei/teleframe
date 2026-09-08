<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGift of StarGift.
 */
final class StarGiftData extends TlStarGiftAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'limited' => ['flags', 0],
        'soldOut' => ['flags', 1],
        'birthday' => ['flags', 2],
        'requirePremium' => ['flags', 7],
        'limitedPerUser' => ['flags', 8],
        'peerColorAvailable' => ['flags', 10],
        'auction' => ['flags', 11],
    ];

    public function __construct(
    public int $flags,
    public ?bool $limited,
    public ?bool $soldOut,
    public ?bool $birthday,
    public ?bool $requirePremium,
    public ?bool $limitedPerUser,
    public ?bool $peerColorAvailable,
    public ?bool $auction,
    public int $id,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $sticker,
    public int $stars,
    public ?int $availabilityRemains,
    public ?int $availabilityTotal,
    public ?int $availabilityResale,
    public int $convertStars,
    public ?int $firstSaleDate,
    public ?int $lastSaleDate,
    public ?int $upgradeStars,
    public ?int $resellMinStars,
    public ?string $title,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $releasedBy,
    public ?int $perUserTotal,
    public ?int $perUserRemains,
    public ?int $lockedUntilDate,
    public ?string $auctionSlug,
    public ?int $giftsPerRound,
    public ?int $auctionStartDate,
    public ?int $upgradeVariants,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftBackgroundAbstractData $background,
    ) {
    }
}
