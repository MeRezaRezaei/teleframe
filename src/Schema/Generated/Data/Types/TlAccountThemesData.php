<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.themes of account.Themes.
 */
final class TlAccountThemesData extends TlAccountThemesAbstractData
{
    public function __construct(
    public int $hash,
    public array $themes,
    ) {
    }
}
