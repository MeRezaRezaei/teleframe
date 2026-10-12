<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputClientProxy of InputClientProxy.
 */
final class InputClientProxyData extends TlInputClientProxyAbstractData
{
    public function __construct(
    public string $address,
    public int $port,
    ) {
    }
}
