<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for sendMessageEmojiInteractionSeen of SendMessageAction.
 */
final class SendMessageEmojiInteractionSeenData extends TlSendMessageActionAbstractData
{
    public function __construct(
    public string $emoticon,
    ) {
    }
}
