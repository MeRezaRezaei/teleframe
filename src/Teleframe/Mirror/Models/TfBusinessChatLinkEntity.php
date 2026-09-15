<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:N entity child — the required Vector<MessageEntity> of the link text, one
 * row per vector slot. Same flat union as tf_messages_entities.
 */
final class TfBusinessChatLinkEntity extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_business_chat_links_entities';

    protected $primaryKey = 'link';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'position' => 'int',
        'offset' => 'int',
        'length' => 'int',
        'user_id' => 'int',
        'document_id' => 'int',
        'date' => 'int',
        'collapsed' => 'bool',
        'relative' => 'bool',
        'short_time' => 'bool',
        'long_time' => 'bool',
        'short_date' => 'bool',
        'long_date' => 'bool',
        'day_of_week' => 'bool',
    ];

    public function chatLink(): BelongsTo
    {
        return $this->belongsTo(TfBusinessChatLink::class, 'link', 'link');
    }
}
