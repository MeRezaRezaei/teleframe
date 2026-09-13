<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatParticipantCreator of ChatParticipant.
 */
final class ChatParticipantCreatorData extends TlChatParticipantAbstractData
{
    public function __construct(
    public int $flags,
    public int $userId,
    public ?string $rank,
    ) {
    }
}
