<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatlists.exportedInvites of chatlists.ExportedInvites.
 */
final class TlChatlistsExportedInvitesData extends TlChatlistsExportedInvitesAbstractData
{
    public function __construct(
    public array $invites,
    public array $chats,
    public array $users,
    ) {
    }
}
