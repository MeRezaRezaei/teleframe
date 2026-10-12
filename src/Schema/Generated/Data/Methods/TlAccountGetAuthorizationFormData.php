<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getAuthorizationForm (crc32 a929597a), returns account.AuthorizationForm. */
final class TlAccountGetAuthorizationFormData extends Data
{
    public const METHOD = 'account.getAuthorizationForm';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $botId,
    public string $scope,
    public string $publicKey,
    ) {
    }
}
