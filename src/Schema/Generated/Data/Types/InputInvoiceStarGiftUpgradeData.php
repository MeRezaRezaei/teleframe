<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputInvoiceStarGiftUpgrade of InputInvoice.
 */
final class InputInvoiceStarGiftUpgradeData extends TlInputInvoiceAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'keepOriginalDetails' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $keepOriginalDetails,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputSavedStarGiftAbstractData $stargift,
    ) {
    }
}
