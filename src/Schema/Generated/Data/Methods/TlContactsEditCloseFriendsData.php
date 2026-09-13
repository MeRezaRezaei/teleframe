<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.editCloseFriends (crc32 ba6705f0), returns Bool. */
final class TlContactsEditCloseFriendsData extends Data
{
    public const METHOD = 'contacts.editCloseFriends';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $id,
    ) {
    }
}
