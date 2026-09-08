<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatlists.chatlistInviteAlready of chatlists.ChatlistInvite.
 */
final class TlChatlistsChatlistInviteAlreadyData extends TlChatlistsChatlistInviteAbstractData
{
    public function __construct(
    public int $filterId,
    public array $missingPeers,
    public array $alreadyPeers,
    public array $chats,
    public array $users,
    ) {
    }
}
