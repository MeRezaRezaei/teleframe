<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionStarGift of MessageAction.
 */
final class MessageActionStarGiftData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'nameHidden' => ['flags', 0],
        'saved' => ['flags', 2],
        'converted' => ['flags', 3],
        'upgraded' => ['flags', 5],
        'refunded' => ['flags', 9],
        'canUpgrade' => ['flags', 10],
        'prepaidUpgrade' => ['flags', 13],
        'upgradeSeparate' => ['flags', 16],
        'auctionAcquired' => ['flags', 17],
    ];

    public function __construct(
    public int $flags,
    public ?bool $nameHidden,
    public ?bool $saved,
    public ?bool $converted,
    public ?bool $upgraded,
    public ?bool $refunded,
    public ?bool $canUpgrade,
    public ?bool $prepaidUpgrade,
    public ?bool $upgradeSeparate,
    public ?bool $auctionAcquired,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAbstractData $gift,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $message,
    public ?int $convertStars,
    public ?int $upgradeMsgId,
    public ?int $upgradeStars,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public ?int $savedId,
    public ?string $prepaidUpgradeHash,
    public ?int $giftMsgId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $toId,
    public ?int $giftNum,
    ) {
    }
}
