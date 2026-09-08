<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaStakeDice of InputMedia.
 *
 * bytes params carried as base64 strings: client_seed
 */
final class InputMediaStakeDiceData extends TlInputMediaAbstractData
{
    public function __construct(
    public string $gameHash,
    public int $tonAmount,
    public string $clientSeed,
    ) {
    }
}
