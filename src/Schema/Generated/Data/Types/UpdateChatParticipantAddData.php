<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateChatParticipantAdd of Update.
 */
final class UpdateChatParticipantAddData extends TlUpdateAbstractData
{
    public function __construct(
    public int $chatId,
    public int $userId,
    public int $inviterId,
    public int $date,
    public int $version,
    ) {
    }
}
