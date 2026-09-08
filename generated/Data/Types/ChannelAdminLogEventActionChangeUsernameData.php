<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionChangeUsername of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionChangeUsernameData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public string $prevValue,
    public string $newValue,
    ) {
    }
}
