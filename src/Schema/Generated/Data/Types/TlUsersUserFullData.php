<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for users.userFull of users.UserFull.
 */
final class TlUsersUserFullData extends TlUsersUserFullAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUserFullAbstractData $fullUser,
    public array $chats,
    public array $users,
    ) {
    }
}
