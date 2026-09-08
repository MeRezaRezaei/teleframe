<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateChatParticipantRank of Update.
 */
final class UpdateChatParticipantRankData extends TlUpdateAbstractData
{
    public function __construct(
    public int $chatId,
    public int $userId,
    public string $rank,
    public int $version,
    ) {
    }
}
