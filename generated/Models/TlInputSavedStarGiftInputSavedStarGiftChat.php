<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputSavedStarGiftChat of InputSavedStarGift (crc32 f101aa7f). */
final class TlInputSavedStarGiftInputSavedStarGiftChat extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_input_saved_star_gift_input_saved_star_gift_chat';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'saved_id' => 'int',
    ];
}
