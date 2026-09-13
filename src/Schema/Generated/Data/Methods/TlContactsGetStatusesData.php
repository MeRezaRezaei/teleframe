<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.getStatuses (crc32 c4a353ee), returns Vector<ContactStatus>. */
final class TlContactsGetStatusesData extends Data
{
    public const METHOD = 'contacts.getStatuses';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
