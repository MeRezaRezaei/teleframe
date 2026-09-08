<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatInvitePeek of ChatInvite.
 */
final class ChatInvitePeekData extends TlChatInviteAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatAbstractData $chat,
    public int $expires,
    ) {
    }
}
