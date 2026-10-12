<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for http_wait of HttpWait.
 */
final class HttpWaitData extends TlHttpWaitAbstractData
{
    public function __construct(
    public int $maxDelay,
    public int $waitAfter,
    public int $maxWait,
    ) {
    }
}
