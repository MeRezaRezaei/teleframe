<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShortMessageEntities;

/** Constructor model for updateShortMessage of Updates (crc32 313bc7f8). */
final class TlUpdatesUpdateShortMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_updates_update_short_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'out' => 'bool',
        'mentioned' => 'bool',
        'media_unread' => 'bool',
        'silent' => 'bool',
        'tl_id' => 'int',
        'user_id' => 'int',
        'message' => 'string',
        'pts' => 'int',
        'pts_count' => 'int',
        'date' => 'int',
        'fwd_from' => 'string',
        'via_bot_id' => 'int',
        'reply_to' => 'string',
        'ttl_period' => 'int',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlUpdatesUpdateShortMessageEntities::class);
    }
}
