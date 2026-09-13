<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionEditMessage of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionEditMessageData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageAbstractData $prevMessage,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageAbstractData $newMessage,
    ) {
    }
}
