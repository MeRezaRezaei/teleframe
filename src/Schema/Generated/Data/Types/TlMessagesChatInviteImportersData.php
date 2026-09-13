<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.chatInviteImporters of messages.ChatInviteImporters.
 */
final class TlMessagesChatInviteImportersData extends TlMessagesChatInviteImportersAbstractData
{
    public function __construct(
    public int $count,
    public array $importers,
    public array $users,
    ) {
    }
}
