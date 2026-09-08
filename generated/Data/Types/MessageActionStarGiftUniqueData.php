<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionStarGiftUnique of MessageAction.
 */
final class MessageActionStarGiftUniqueData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'upgrade' => ['flags', 0],
        'transferred' => ['flags', 1],
        'saved' => ['flags', 2],
        'refunded' => ['flags', 5],
        'prepaidUpgrade' => ['flags', 11],
        'assigned' => ['flags', 13],
        'fromOffer' => ['flags', 14],
        'craft' => ['flags', 16],
    ];

    public function __construct(
    public int $flags,
    public ?bool $upgrade,
    public ?bool $transferred,
    public ?bool $saved,
    public ?bool $refunded,
    public ?bool $prepaidUpgrade,
    public ?bool $assigned,
    public ?bool $fromOffer,
    public ?bool $craft,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAbstractData $gift,
    public ?int $canExportAt,
    public ?int $transferStars,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public ?int $savedId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $resaleAmount,
    public ?int $canTransferAt,
    public ?int $canResellAt,
    public ?int $dropOriginalDetailsStars,
    public ?int $canCraftAt,
    ) {
    }
}
