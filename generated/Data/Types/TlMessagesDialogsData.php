<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.dialogs of messages.Dialogs.
 */
final class TlMessagesDialogsData extends TlMessagesDialogsAbstractData
{
    public function __construct(
    public array $dialogs,
    public array $messages,
    public array $chats,
    public array $users,
    ) {
    }
}
