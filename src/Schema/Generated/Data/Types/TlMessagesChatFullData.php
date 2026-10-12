<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.chatFull of messages.ChatFull.
 */
final class TlMessagesChatFullData extends TlMessagesChatFullAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatFullAbstractData $fullChat,
    public array $chats,
    public array $users,
    ) {
    }
}
