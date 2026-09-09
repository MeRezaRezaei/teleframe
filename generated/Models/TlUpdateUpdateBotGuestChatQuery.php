<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotGuestChatQueryReference_messages;

/** Constructor model for updateBotGuestChatQuery of Update (crc32 cdd4093d). */
final class TlUpdateUpdateBotGuestChatQuery extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_bot_guest_chat_query';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'query_id' => 'int',
        'qts' => 'int',
    ];

    public function referenceMessages(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateBotGuestChatQueryReference_messages::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TlMessage::class, 'message');
    }
}
