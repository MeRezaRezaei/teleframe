<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateChatUserTyping of Update.
 */
final class UpdateChatUserTypingData extends TlUpdateAbstractData
{
    public function __construct(
    public int $chatId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSendMessageActionAbstractData $action,
    ) {
    }
}
