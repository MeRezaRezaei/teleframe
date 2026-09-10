<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for defaultHistoryTTL of DefaultHistoryTTL (crc32 43b46b20). */
final class TlDefaultHistoryTTLDefaultHistoryTTL extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_default_history_t_t_l_default_history_t_t_l';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'period' => 'int',
    ];
}
