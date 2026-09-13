<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatParticipantAdmin of ChatParticipant.
 */
final class ChatParticipantAdminData extends TlChatParticipantAbstractData
{
    public function __construct(
    public int $flags,
    public int $userId,
    public int $inviterId,
    public int $date,
    public ?string $rank,
    ) {
    }
}
