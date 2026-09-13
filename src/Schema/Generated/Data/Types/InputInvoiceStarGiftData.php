<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputInvoiceStarGift of InputInvoice.
 */
final class InputInvoiceStarGiftData extends TlInputInvoiceAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hideName' => ['flags', 0],
        'includeUpgrade' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hideName,
    public ?bool $includeUpgrade,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPeerAbstractData $peer,
    public int $giftId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $message,
    ) {
    }
}
