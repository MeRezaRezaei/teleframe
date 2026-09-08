<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpUserInfoUserInfoEntities;

/** Constructor model for help.userInfo of help.UserInfo (crc32 01eb3758). */
final class TlHelpUserInfoUserInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_help_user_info_user_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'message' => 'string',
        'author' => 'string',
        'date' => 'int',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlHelpUserInfoUserInfoEntities::class);
    }
}
