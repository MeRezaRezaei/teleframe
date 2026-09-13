<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageMediaInvoice of MessageMedia.
 */
final class MessageMediaInvoiceData extends TlMessageMediaAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'shippingAddressRequested' => ['flags', 1],
        'test' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $shippingAddressRequested,
    public ?bool $test,
    public string $title,
    public string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebDocumentAbstractData $photo,
    public ?int $receiptMsgId,
    public string $currency,
    public int $totalAmount,
    public string $startParam,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageExtendedMediaAbstractData $extendedMedia,
    ) {
    }
}
