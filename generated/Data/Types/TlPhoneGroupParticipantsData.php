<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phone.groupParticipants of phone.GroupParticipants.
 */
final class TlPhoneGroupParticipantsData extends TlPhoneGroupParticipantsAbstractData
{
    public function __construct(
    public int $count,
    public array $participants,
    public string $nextOffset,
    public array $chats,
    public array $users,
    public int $version,
    ) {
    }
}
