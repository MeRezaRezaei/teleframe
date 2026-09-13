<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for urlAuthResultAccepted of UrlAuthResult.
 */
final class UrlAuthResultAcceptedData extends TlUrlAuthResultAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $url,
    ) {
    }
}
