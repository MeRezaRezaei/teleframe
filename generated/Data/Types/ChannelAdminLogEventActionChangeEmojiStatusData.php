<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionChangeEmojiStatus of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionChangeEmojiStatusData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlEmojiStatusAbstractData $prevValue,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlEmojiStatusAbstractData $newValue,
    ) {
    }
}
