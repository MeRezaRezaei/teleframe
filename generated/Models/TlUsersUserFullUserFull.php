<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUsersUserFullUserFullChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUsersUserFullUserFullUsers;

/** Constructor model for users.userFull of users.UserFull (crc32 3b6d152e). */
final class TlUsersUserFullUserFull extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_users_user_full_user_full';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'full_user' => 'string',
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlUsersUserFullUserFullChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlUsersUserFullUserFullUsers::class);
    }
}
