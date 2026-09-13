<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionChangeLinkedChat of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionChangeLinkedChatData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public int $prevValue,
    public int $newValue,
    ) {
    }
}
