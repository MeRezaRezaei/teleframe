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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResult;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPreparedInlineMessagePreparedAbbe0eee55f7Peer_types;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPreparedInlineMessagePreparedAbbe0eee55f7Users;

/** Constructor model for messages.preparedInlineMessage of messages.PreparedInlineMessage (crc32 ff57708d). */
final class TlMessagesPreparedInlineMessagePreparedInlineMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_prepared_inline_message_prepared__abbe0eee55f7';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'query_id' => 'int',
        'cache_time' => 'int',
    ];

    public function peerTypes(): HasMany
    {
        return $this->tlChild(TlMessagesPreparedInlineMessagePreparedAbbe0eee55f7Peer_types::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesPreparedInlineMessagePreparedAbbe0eee55f7Users::class);
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(TlBotInlineResult::class, 'result');
    }
}
