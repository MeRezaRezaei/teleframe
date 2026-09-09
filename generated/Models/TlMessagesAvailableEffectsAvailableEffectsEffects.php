<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param effects (table tl_messages_available_effects_available_effects__effects). */
final class TlMessagesAvailableEffectsAvailableEffectsEffects extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_available_effects_available_effects__effects';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
