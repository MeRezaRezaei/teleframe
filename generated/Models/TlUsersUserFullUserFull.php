<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUsersUserFullUserFullChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUsersUserFullUserFullUsers;

/** Constructor model for users.userFull of users.UserFull (crc32 3b6d152e). */
final class TlUsersUserFullUserFull extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_users_user_full_user_full';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlUsersUserFullUserFullChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlUsersUserFullUserFullUsers::class);
    }

    public function fullUser(): BelongsTo
    {
        return $this->belongsTo(TlUserFull::class, 'full_user');
    }
}
