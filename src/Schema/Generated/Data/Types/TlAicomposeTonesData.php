<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for aicompose.tones of aicompose.Tones.
 */
final class TlAicomposeTonesData extends TlAicomposeTonesAbstractData
{
    public function __construct(
    public int $hash,
    public array $tones,
    public array $users,
    ) {
    }
}
