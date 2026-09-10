<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;

/** Constructor model for updateBotNewBusinessMessage of Update (crc32 9ddb347c). */
final class TlUpdateUpdateBotNewBusinessMessage extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_bot_new_business_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'connection_id' => 'string',
        'qts' => 'int',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TlMessage::class, 'message');
    }
    public function replyToMessage(): BelongsTo
    {
        return $this->belongsTo(TlMessage::class, 'reply_to_message');
    }
}
