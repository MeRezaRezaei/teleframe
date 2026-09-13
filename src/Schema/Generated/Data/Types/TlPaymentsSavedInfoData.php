<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.savedInfo of payments.SavedInfo.
 */
final class TlPaymentsSavedInfoData extends TlPaymentsSavedInfoAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hasSavedCredentials' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hasSavedCredentials,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPaymentRequestedInfoAbstractData $savedInfo,
    ) {
    }
}
