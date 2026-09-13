<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatAdminWithInvites of ChatAdminWithInvites.
 */
final class ChatAdminWithInvitesData extends TlChatAdminWithInvitesAbstractData
{
    public function __construct(
    public int $adminId,
    public int $invitesCount,
    public int $revokedInvitesCount,
    ) {
    }
}
