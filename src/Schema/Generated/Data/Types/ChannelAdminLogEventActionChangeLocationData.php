<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionChangeLocation of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionChangeLocationData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChannelLocationAbstractData $prevValue,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChannelLocationAbstractData $newValue,
    ) {
    }
}
