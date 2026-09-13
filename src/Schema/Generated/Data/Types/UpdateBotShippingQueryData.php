<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBotShippingQuery of Update.
 *
 * bytes params carried as base64 strings: payload
 */
final class UpdateBotShippingQueryData extends TlUpdateAbstractData
{
    public function __construct(
    public int $queryId,
    public int $userId,
    public string $payload,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPostAddressAbstractData $shippingAddress,
    ) {
    }
}
