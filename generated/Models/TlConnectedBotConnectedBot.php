<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for connectedBot of ConnectedBot (crc32 033ed001). */
final class TlConnectedBotConnectedBot extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_connected_bot_connected_bot';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'bot_id' => 'int',
        'recipients' => 'string',
        'rights' => 'string',
        'device' => 'string',
        'date' => 'int',
        'location' => 'string',
    ];
}
