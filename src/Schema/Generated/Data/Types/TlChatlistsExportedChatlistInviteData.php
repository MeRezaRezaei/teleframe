<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatlists.exportedChatlistInvite of chatlists.ExportedChatlistInvite.
 */
final class TlChatlistsExportedChatlistInviteData extends TlChatlistsExportedChatlistInviteAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDialogFilterAbstractData $filter,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlExportedChatlistInviteAbstractData $invite,
    ) {
    }
}
