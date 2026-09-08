<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatParticipantsForbidden of ChatParticipants.
 */
final class ChatParticipantsForbiddenData extends TlChatParticipantsAbstractData
{
    public function __construct(
    public int $flags,
    public int $chatId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatParticipantAbstractData $selfParticipant,
    ) {
    }
}
