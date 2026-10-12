<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.chatsSlice of messages.Chats.
 */
final class TlMessagesChatsSliceData extends TlMessagesChatsAbstractData
{
    public function __construct(
    public int $count,
    public array $chats,
    ) {
    }
}
