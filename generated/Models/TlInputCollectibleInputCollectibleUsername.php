<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputCollectibleUsername of InputCollectible (crc32 e39460a9). */
final class TlInputCollectibleInputCollectibleUsername extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_collectible_input_collectible_username';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'username' => 'string',
    ];
}
