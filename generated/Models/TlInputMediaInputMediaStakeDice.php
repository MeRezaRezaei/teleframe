<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputMediaStakeDice of InputMedia (crc32 f3a9244a). */
final class TlInputMediaInputMediaStakeDice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_media_input_media_stake_dice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'game_hash' => 'string',
        'ton_amount' => 'int',
        'client_seed' => 'string',
    ];
}
