<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.setBlocked (crc32 94c65c76), returns Bool. */
final class TlContactsSetBlockedData extends Data
{
    public const METHOD = 'contacts.setBlocked';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $myStoriesFrom,
    public array $id,
    public int $limit,
    ) {
    }
}
