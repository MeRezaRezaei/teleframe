<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for chatOnlines of ChatOnlines (crc32 f041e250). */
final class TlChatOnlinesChatOnlines extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chat_onlines_chat_onlines';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'onlines' => 'int',
    ];
}
