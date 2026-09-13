<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionChangeHistoryTTL of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionChangeHistoryTTLData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public int $prevValue,
    public int $newValue,
    ) {
    }
}
