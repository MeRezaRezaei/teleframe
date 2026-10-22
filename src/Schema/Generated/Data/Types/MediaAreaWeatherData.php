<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for mediaAreaWeather of MediaArea.
 */
final class MediaAreaWeatherData extends TlMediaAreaAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMediaAreaCoordinatesAbstractData $coordinates,
    public string $emoji,
    public float $temperatureC,
    public int $color,
    ) {
    }
}
