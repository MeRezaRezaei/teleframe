<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for accountDaysTTL of AccountDaysTTL (crc32 b8d0afdf). */
final class TlAccountDaysTTLAccountDaysTTL extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_days_t_t_l_account_days_t_t_l';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'days' => 'int',
    ];
}
