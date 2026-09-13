<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.discussionMessage of messages.DiscussionMessage.
 */
final class TlMessagesDiscussionMessageData extends TlMessagesDiscussionMessageAbstractData
{
    public function __construct(
    public int $flags,
    public array $messages,
    public ?int $maxId,
    public ?int $readInboxMaxId,
    public ?int $readOutboxMaxId,
    public int $unreadCount,
    public array $chats,
    public array $users,
    ) {
    }
}
