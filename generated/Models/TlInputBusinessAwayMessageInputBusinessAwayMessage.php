<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessAwayMessageSchedule;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessRecipients;

/** Constructor model for inputBusinessAwayMessage of InputBusinessAwayMessage (crc32 832175e0). */
final class TlInputBusinessAwayMessageInputBusinessAwayMessage extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_business_away_message_input_business_away_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'offline_only' => 'bool',
        'shortcut_id' => 'int',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TlBusinessAwayMessageSchedule::class, 'schedule');
    }
    public function recipients(): BelongsTo
    {
        return $this->belongsTo(TlInputBusinessRecipients::class, 'recipients');
    }
}
