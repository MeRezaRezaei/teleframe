<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.messages of messages.Messages.
 */
final class TlMessagesMessagesData extends TlMessagesMessagesAbstractData
{
    public function __construct(
    public array $messages,
    public array $topics,
    public array $chats,
    public array $users,
    ) {
    }
}
