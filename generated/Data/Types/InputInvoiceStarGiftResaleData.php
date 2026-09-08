<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputInvoiceStarGiftResale of InputInvoice.
 */
final class InputInvoiceStarGiftResaleData extends TlInputInvoiceAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'ton' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $ton,
    public string $slug,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPeerAbstractData $toId,
    ) {
    }
}
