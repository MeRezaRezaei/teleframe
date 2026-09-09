<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPhoto;

/** Constructor model for inputBotInlineResultPhoto of InputBotInlineResult (crc32 a8d864a7). */
final class TlInputBotInlineResultInputBotInlineResultPhoto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_result_input_bot_inline_result_photo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'string',
        'tl_type' => 'string',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlInputPhoto::class, 'photo');
    }
    public function sendMessage(): BelongsTo
    {
        return $this->belongsTo(TlInputBotInlineMessage::class, 'send_message');
    }
}
