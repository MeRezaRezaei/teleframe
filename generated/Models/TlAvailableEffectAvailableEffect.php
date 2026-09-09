<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for availableEffect of AvailableEffect (crc32 93c3e27e). */
final class TlAvailableEffectAvailableEffect extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_available_effect_available_effect';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'premium_required' => 'bool',
        'tl_id' => 'int',
        'emoticon' => 'string',
        'static_icon_id' => 'int',
        'effect_sticker_id' => 'int',
        'effect_animation_id' => 'int',
    ];
}
