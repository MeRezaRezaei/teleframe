<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for sendMessageEmojiInteraction of SendMessageAction.
 */
final class SendMessageEmojiInteractionData extends TlSendMessageActionAbstractData
{
    public function __construct(
    public string $emoticon,
    public int $msgId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDataJSONAbstractData $interaction,
    ) {
    }
}
