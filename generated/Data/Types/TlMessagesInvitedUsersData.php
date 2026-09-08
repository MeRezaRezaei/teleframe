<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.invitedUsers of messages.InvitedUsers.
 */
final class TlMessagesInvitedUsersData extends TlMessagesInvitedUsersAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUpdatesAbstractData $updates,
    public array $missingInvitees,
    ) {
    }
}
