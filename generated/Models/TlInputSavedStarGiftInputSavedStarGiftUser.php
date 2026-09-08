<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputSavedStarGiftUser of InputSavedStarGift (crc32 69279795). */
final class TlInputSavedStarGiftInputSavedStarGiftUser extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_saved_star_gift_input_saved_star_gift_user';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'msg_id' => 'int',
    ];
}
