<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaInvoice of InputMedia.
 *
 * bytes params carried as base64 strings: payload
 */
final class InputMediaInvoiceData extends TlInputMediaAbstractData
{
    public function __construct(
    public int $flags,
    public string $title,
    public string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputWebDocumentAbstractData $photo,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInvoiceAbstractData $invoice,
    public string $payload,
    public ?string $provider,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDataJSONAbstractData $providerData,
    public ?string $startParam,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputMediaAbstractData $extendedMedia,
    ) {
    }
}
