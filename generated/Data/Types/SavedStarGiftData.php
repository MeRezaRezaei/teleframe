<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for savedStarGift of SavedStarGift.
 */
final class SavedStarGiftData extends TlSavedStarGiftAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'nameHidden' => ['flags', 0],
        'unsaved' => ['flags', 5],
        'refunded' => ['flags', 9],
        'canUpgrade' => ['flags', 10],
        'pinnedToTop' => ['flags', 12],
        'upgradeSeparate' => ['flags', 17],
    ];

    public function __construct(
    public int $flags,
    public ?bool $nameHidden,
    public ?bool $unsaved,
    public ?bool $refunded,
    public ?bool $canUpgrade,
    public ?bool $pinnedToTop,
    public ?bool $upgradeSeparate,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public int $date,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAbstractData $gift,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $message,
    public ?int $msgId,
    public ?int $savedId,
    public ?int $convertStars,
    public ?int $upgradeStars,
    public ?int $canExportAt,
    public ?int $transferStars,
    public ?int $canTransferAt,
    public ?int $canResellAt,
    public ?array $collectionId,
    public ?string $prepaidUpgradeHash,
    public ?int $dropOriginalDetailsStars,
    public ?int $giftNum,
    public ?int $canCraftAt,
    ) {
    }
}
