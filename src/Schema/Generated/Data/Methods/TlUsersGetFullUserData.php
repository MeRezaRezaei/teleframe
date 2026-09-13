<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method users.getFullUser (crc32 b60f5918), returns users.UserFull. */
final class TlUsersGetFullUserData extends Data
{
    public const METHOD = 'users.getFullUser';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    ) {
    }
}
