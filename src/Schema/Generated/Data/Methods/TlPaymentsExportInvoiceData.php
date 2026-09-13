<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.exportInvoice (crc32 0f91b065), returns payments.ExportedInvoice. */
final class TlPaymentsExportInvoiceData extends Data
{
    public const METHOD = 'payments.exportInvoice';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $invoiceMedia,
    ) {
    }
}
