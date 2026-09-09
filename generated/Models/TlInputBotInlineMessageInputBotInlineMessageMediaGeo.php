<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGeoPoint;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReplyMarkup;

/** Constructor model for inputBotInlineMessageMediaGeo of InputBotInlineMessage (crc32 96929a85). */
final class TlInputBotInlineMessageInputBotInlineMessageMediaGeo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline__a1361e727854';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'heading' => 'int',
        'period' => 'int',
        'proximity_notification_radius' => 'int',
    ];

    public function geoPoint(): BelongsTo
    {
        return $this->belongsTo(TlInputGeoPoint::class, 'geo_point');
    }
    public function replyMarkup(): BelongsTo
    {
        return $this->belongsTo(TlReplyMarkup::class, 'reply_markup');
    }
}
