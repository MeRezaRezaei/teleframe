<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageMediaGeoLive of MessageMedia.
 */
final class MessageMediaGeoLiveData extends TlMessageMediaAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGeoPointAbstractData $geo,
    public ?int $heading,
    public int $period,
    public ?int $proximityNotificationRadius,
    ) {
    }
}
