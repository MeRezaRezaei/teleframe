<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for connectedBotStarRef of ConnectedBotStarRef (crc32 19a13f71). */
final class TlConnectedBotStarRefConnectedBotStarRef extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_connected_bot_star_ref_connected_bot_star_ref';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'revoked' => 'bool',
        'url' => 'string',
        'date' => 'int',
        'bot_id' => 'int',
        'commission_permille' => 'int',
        'duration_months' => 'int',
        'participants' => 'int',
        'revenue' => 'int',
    ];
}
