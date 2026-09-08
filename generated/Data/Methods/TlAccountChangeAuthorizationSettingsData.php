<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.changeAuthorizationSettings (crc32 40f48462), returns Bool. */
final class TlAccountChangeAuthorizationSettingsData extends Data
{
    public const METHOD = 'account.changeAuthorizationSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $confirmed,
    public int $hash,
    public mixed $encryptedRequestsDisabled,
    public mixed $callRequestsDisabled,
    ) {
    }
}
