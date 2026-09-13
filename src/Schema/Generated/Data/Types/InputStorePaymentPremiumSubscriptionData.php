<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputStorePaymentPremiumSubscription of InputStorePaymentPurpose.
 */
final class InputStorePaymentPremiumSubscriptionData extends TlInputStorePaymentPurposeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'restore' => ['flags', 0],
        'upgrade' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $restore,
    public ?bool $upgrade,
    ) {
    }
}
