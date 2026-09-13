<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.getSaved (crc32 82f1e39f), returns Vector<SavedContact>. */
final class TlContactsGetSavedData extends Data
{
    public const METHOD = 'contacts.getSaved';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
