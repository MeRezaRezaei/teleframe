<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for bots.botInfo of bots.BotInfo (crc32 e8a775b0). */
final class TlBotsBotInfoBotInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bots_bot_info_bot_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'name' => 'string',
        'about' => 'string',
        'description' => 'string',
    ];
}
