<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for server_DH_inner_data of Server_DH_inner_data.
 */
final class ServerDHInnerDataData extends TlServerDHInnerDataAbstractData
{
    public function __construct(
    public string $nonce,
    public string $serverNonce,
    public int $g,
    public string $dhPrime,
    public string $gA,
    public int $serverTime,
    ) {
    }
}
