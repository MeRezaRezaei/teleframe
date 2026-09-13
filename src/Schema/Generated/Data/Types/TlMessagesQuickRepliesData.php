<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.quickReplies of messages.QuickReplies.
 */
final class TlMessagesQuickRepliesData extends TlMessagesQuickRepliesAbstractData
{
    public function __construct(
    public array $quickReplies,
    public array $messages,
    public array $chats,
    public array $users,
    ) {
    }
}
