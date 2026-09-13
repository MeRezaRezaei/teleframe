<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for error of Error.
 */
final class ErrorData extends TlErrorAbstractData
{
    public function __construct(
    public int $code,
    public string $text,
    ) {
    }
}
