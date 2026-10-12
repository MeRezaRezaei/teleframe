<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.search (crc32 05f58d0f), returns contacts.Found. */
final class TlContactsSearchData extends Data
{
    public const METHOD = 'contacts.search';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $broadcasts,
    public ?bool $bots,
    public string $q,
    public int $limit,
    ) {
    }
}
