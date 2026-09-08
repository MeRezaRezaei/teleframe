<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.sentCodeTypeMissedCall of auth.SentCodeType.
 */
final class TlAuthSentCodeTypeMissedCallData extends TlAuthSentCodeTypeAbstractData
{
    public function __construct(
    public string $prefix,
    public int $length,
    ) {
    }
}
