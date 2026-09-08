<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionEditTopic of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionEditTopicData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlForumTopicAbstractData $prevTopic,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlForumTopicAbstractData $newTopic,
    ) {
    }
}
