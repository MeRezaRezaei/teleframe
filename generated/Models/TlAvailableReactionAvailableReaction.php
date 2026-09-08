<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for availableReaction of AvailableReaction (crc32 c077ec01). */
final class TlAvailableReactionAvailableReaction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_available_reaction_available_reaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'inactive' => 'bool',
        'premium' => 'bool',
        'reaction' => 'string',
        'title' => 'string',
        'static_icon' => 'string',
        'appear_animation' => 'string',
        'select_animation' => 'string',
        'activate_animation' => 'string',
        'effect_animation' => 'string',
        'around_animation' => 'string',
        'center_icon' => 'string',
    ];
}
