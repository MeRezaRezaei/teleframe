<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for dh_gen_fail of Set_client_DH_params_answer.
 */
final class DhGenFailData extends TlSetClientDHParamsAnswerAbstractData
{
    public function __construct(
    public string $nonce,
    public string $serverNonce,
    public string $newNonceHash3,
    ) {
    }
}
