<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionPinTopic of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionPinTopicData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlForumTopicAbstractData $prevTopic,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlForumTopicAbstractData $newTopic,
    ) {
    }
}
