<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for invoice of Invoice.
 */
final class InvoiceData extends TlInvoiceAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'test' => ['flags', 0],
        'nameRequested' => ['flags', 1],
        'phoneRequested' => ['flags', 2],
        'emailRequested' => ['flags', 3],
        'shippingAddressRequested' => ['flags', 4],
        'flexible' => ['flags', 5],
        'phoneToProvider' => ['flags', 6],
        'emailToProvider' => ['flags', 7],
        'recurring' => ['flags', 9],
    ];

    public function __construct(
    public int $flags,
    public ?bool $test,
    public ?bool $nameRequested,
    public ?bool $phoneRequested,
    public ?bool $emailRequested,
    public ?bool $shippingAddressRequested,
    public ?bool $flexible,
    public ?bool $phoneToProvider,
    public ?bool $emailToProvider,
    public ?bool $recurring,
    public string $currency,
    public array $prices,
    public ?int $maxTipAmount,
    public ?array $suggestedTipAmounts,
    public ?string $termsUrl,
    public ?int $subscriptionPeriod,
    ) {
    }
}
