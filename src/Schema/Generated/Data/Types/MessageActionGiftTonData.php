<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionGiftTon of MessageAction.
 */
final class MessageActionGiftTonData extends TlMessageActionAbstractData
{
    public function __construct(
    public int $flags,
    public string $currency,
    public int $amount,
    public string $cryptoCurrency,
    public int $cryptoAmount,
    public ?string $transactionId,
    ) {
    }
}
