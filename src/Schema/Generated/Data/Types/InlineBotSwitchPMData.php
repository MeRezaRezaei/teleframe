<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inlineBotSwitchPM of InlineBotSwitchPM.
 */
final class InlineBotSwitchPMData extends TlInlineBotSwitchPMAbstractData
{
    public function __construct(
    public string $text,
    public string $startParam,
    ) {
    }
}
