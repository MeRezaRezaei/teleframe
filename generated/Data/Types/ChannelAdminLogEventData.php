<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEvent of ChannelAdminLogEvent.
 */
final class ChannelAdminLogEventData extends TlChannelAdminLogEventAbstractData
{
    public function __construct(
    public int $id,
    public int $date,
    public int $userId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChannelAdminLogEventActionAbstractData $action,
    ) {
    }
}
