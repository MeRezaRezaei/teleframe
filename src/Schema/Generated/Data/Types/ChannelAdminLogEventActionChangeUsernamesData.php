<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionChangeUsernames of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionChangeUsernamesData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public array $prevValue,
    public array $newValue,
    ) {
    }
}
