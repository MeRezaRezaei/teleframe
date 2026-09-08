<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionPaymentSent of MessageAction.
 */
final class MessageActionPaymentSentData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'recurringInit' => ['flags', 2],
        'recurringUsed' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $recurringInit,
    public ?bool $recurringUsed,
    public string $currency,
    public int $totalAmount,
    public ?string $invoiceSlug,
    public ?int $subscriptionUntilDate,
    ) {
    }
}
