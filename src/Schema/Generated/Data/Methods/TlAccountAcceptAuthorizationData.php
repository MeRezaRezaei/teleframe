<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.acceptAuthorization (crc32 f3ed4c73), returns Bool. */
final class TlAccountAcceptAuthorizationData extends Data
{
    public const METHOD = 'account.acceptAuthorization';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $botId,
    public string $scope,
    public string $publicKey,
    public array $valueHashes,
    public mixed $credentials,
    ) {
    }
}
