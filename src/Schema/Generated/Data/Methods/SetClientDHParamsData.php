<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method set_client_DH_params (crc32 f5045f1f), returns Set_client_DH_params_answer. */
final class SetClientDHParamsData extends Data
{
    public const METHOD = 'set_client_DH_params';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $nonce,
    public string $serverNonce,
    public string $encryptedData,
    ) {
    }
}
