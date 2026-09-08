<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.sentCodeTypeSmsWord of auth.SentCodeType.
 */
final class TlAuthSentCodeTypeSmsWordData extends TlAuthSentCodeTypeAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $beginning,
    ) {
    }
}
