<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputGameID of InputGame.
 */
final class InputGameIDData extends TlInputGameAbstractData
{
    public function __construct(
    public int $id,
    public int $accessHash,
    ) {
    }
}
