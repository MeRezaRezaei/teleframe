<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for p_q_inner_data_dc of P_Q_inner_data.
 */
final class PQInnerDataDcData extends TlPQInnerDataAbstractData
{
    public function __construct(
    public string $pq,
    public string $p,
    public string $q,
    public string $nonce,
    public string $serverNonce,
    public string $newNonce,
    public int $dc,
    ) {
    }
}
