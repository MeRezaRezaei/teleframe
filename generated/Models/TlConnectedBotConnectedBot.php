<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessBotRecipients;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessBotRights;

/** Constructor model for connectedBot of ConnectedBot (crc32 033ed001). */
final class TlConnectedBotConnectedBot extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_connected_bot_connected_bot';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'bot_id' => 'int',
        'device' => 'string',
        'date' => 'int',
        'location' => 'string',
    ];

    public function recipients(): BelongsTo
    {
        return $this->belongsTo(TlBusinessBotRecipients::class, 'recipients');
    }
    public function rights(): BelongsTo
    {
        return $this->belongsTo(TlBusinessBotRights::class, 'rights');
    }
}
