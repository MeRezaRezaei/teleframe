<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatlists.chatlistUpdates of chatlists.ChatlistUpdates.
 */
final class TlChatlistsChatlistUpdatesData extends TlChatlistsChatlistUpdatesAbstractData
{
    public function __construct(
    public array $missingPeers,
    public array $chats,
    public array $users,
    ) {
    }
}
