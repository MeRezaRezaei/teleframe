<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getUserInfo (crc32 038a08d3), returns help.UserInfo. */
final class TlHelpGetUserInfoData extends Data
{
    public const METHOD = 'help.getUserInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $userId,
    ) {
    }
}
