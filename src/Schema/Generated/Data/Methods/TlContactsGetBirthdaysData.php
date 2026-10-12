<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.getBirthdays (crc32 daeda864), returns contacts.ContactBirthdays. */
final class TlContactsGetBirthdaysData extends Data
{
    public const METHOD = 'contacts.getBirthdays';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
