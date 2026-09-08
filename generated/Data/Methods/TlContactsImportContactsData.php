<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.importContacts (crc32 2c800be5), returns contacts.ImportedContacts. */
final class TlContactsImportContactsData extends Data
{
    public const METHOD = 'contacts.importContacts';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $contacts,
    ) {
    }
}
