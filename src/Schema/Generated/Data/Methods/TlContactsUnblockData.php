<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.unblock (crc32 b550d328), returns Bool. */
final class TlContactsUnblockData extends Data
{
    public const METHOD = 'contacts.unblock';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $myStoriesFrom,
    public mixed $id,
    ) {
    }
}
