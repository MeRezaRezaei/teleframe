<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMessageReadMetric of InputMessageReadMetric.
 */
final class InputMessageReadMetricData extends TlInputMessageReadMetricAbstractData
{
    public function __construct(
    public int $msgId,
    public int $viewId,
    public int $timeInViewMs,
    public int $activeTimeInViewMs,
    public int $heightToViewportRatioPermille,
    public int $seenRangeRatioPermille,
    ) {
    }
}
