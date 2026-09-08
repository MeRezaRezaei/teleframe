<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method req_DH_params (crc32 d712e4be), returns Server_DH_Params. */
final class ReqDHParamsData extends Data
{
    public const METHOD = 'req_DH_params';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $nonce,
    public string $serverNonce,
    public string $p,
    public string $q,
    public int $publicKeyFingerprint,
    public string $encryptedData,
    ) {
    }
}
