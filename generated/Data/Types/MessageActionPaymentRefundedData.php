<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionPaymentRefunded of MessageAction.
 *
 * bytes params carried as base64 strings: payload
 */
final class MessageActionPaymentRefundedData extends TlMessageActionAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public string $currency,
    public int $totalAmount,
    public ?string $payload,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPaymentChargeAbstractData $charge,
    ) {
    }
}
