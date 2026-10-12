<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.editUserInfo (crc32 66b91b70), returns help.UserInfo. */
final class TlHelpEditUserInfoData extends Data
{
    public const METHOD = 'help.editUserInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $userId,
    public string $message,
    public array $entities,
    ) {
    }
}
