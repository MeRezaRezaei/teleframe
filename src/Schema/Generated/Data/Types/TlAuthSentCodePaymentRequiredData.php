<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.sentCodePaymentRequired of auth.SentCode.
 */
final class TlAuthSentCodePaymentRequiredData extends TlAuthSentCodeAbstractData
{
    public function __construct(
    public string $storeProduct,
    public string $phoneCodeHash,
    public string $supportEmailAddress,
    public string $supportEmailSubject,
    public int $premiumDays,
    public string $currency,
    public int $amount,
    ) {
    }
}
