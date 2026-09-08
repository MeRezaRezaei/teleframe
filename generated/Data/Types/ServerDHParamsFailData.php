<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for server_DH_params_fail of Server_DH_Params.
 */
final class ServerDHParamsFailData extends TlServerDHParamsAbstractData
{
    public function __construct(
    public string $nonce,
    public string $serverNonce,
    public string $newNonceHash,
    ) {
    }
}
