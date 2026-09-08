<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionGiftStars of MessageAction.
 */
final class MessageActionGiftStarsData extends TlMessageActionAbstractData
{
    public function __construct(
    public int $flags,
    public string $currency,
    public int $amount,
    public int $stars,
    public ?string $cryptoCurrency,
    public ?int $cryptoAmount,
    public ?string $transactionId,
    ) {
    }
}
