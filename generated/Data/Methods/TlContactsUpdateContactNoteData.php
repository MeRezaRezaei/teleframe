<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.updateContactNote (crc32 139f63fb), returns Bool. */
final class TlContactsUpdateContactNoteData extends Data
{
    public const METHOD = 'contacts.updateContactNote';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    public mixed $note,
    ) {
    }
}
