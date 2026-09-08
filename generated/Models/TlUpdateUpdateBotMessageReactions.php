<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotMessageReactionsReactions;

/** Constructor model for updateBotMessageReactions of Update (crc32 09cb7759). */
final class TlUpdateUpdateBotMessageReactions extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_bot_message_reactions';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'msg_id' => 'int',
        'date' => 'int',
        'qts' => 'int',
    ];

    public function reactions(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateBotMessageReactionsReactions::class);
    }
}
