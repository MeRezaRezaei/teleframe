<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputUser;

/** Constructor model for inputBotAppShortName of InputBotApp (crc32 908c0407). */
final class TlInputBotAppInputBotAppShortName extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_bot_app_input_bot_app_short_name';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'short_name' => 'string',
    ];

    public function botId(): BelongsTo
    {
        return $this->belongsTo(TlInputUser::class, 'bot_id');
    }
}
