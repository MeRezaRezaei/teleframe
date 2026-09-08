<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelForbidden of Chat (crc32 17d493d5). */
final class TlChatChannelForbidden extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chat_channel_forbidden';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'broadcast' => 'bool',
        'megagroup' => 'bool',
        'monoforum' => 'bool',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'title' => 'string',
        'until_date' => 'int',
    ];
}
