<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBotChatInviteRequester of Update.
 */
final class UpdateBotChatInviteRequesterData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $date,
    public int $userId,
    public string $about,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlExportedChatInviteAbstractData $invite,
    public int $qts,
    public ?int $queryId,
    ) {
    }
}
