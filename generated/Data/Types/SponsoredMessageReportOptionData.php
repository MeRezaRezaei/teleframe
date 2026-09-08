<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for sponsoredMessageReportOption of SponsoredMessageReportOption.
 *
 * bytes params carried as base64 strings: option
 */
final class SponsoredMessageReportOptionData extends TlSponsoredMessageReportOptionAbstractData
{
    public function __construct(
    public string $text,
    public string $option,
    ) {
    }
}
