<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotCommandsCommands;

/** Constructor model for updateBotCommands of Update (crc32 4d712f2e). */
final class TlUpdateUpdateBotCommands extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_bot_commands';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'bot_id' => 'int',
    ];

    public function commands(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateBotCommandsCommands::class);
    }
}
