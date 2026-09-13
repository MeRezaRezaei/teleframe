<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for client_DH_inner_data of Client_DH_Inner_Data.
 */
final class ClientDHInnerDataData extends TlClientDHInnerDataAbstractData
{
    public function __construct(
    public string $nonce,
    public string $serverNonce,
    public int $retryId,
    public string $gB,
    ) {
    }
}
