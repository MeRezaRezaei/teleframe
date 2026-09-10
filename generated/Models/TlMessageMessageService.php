<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageAction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReplyHeader;

/** Constructor model for messageService of Message (crc32 7a800e0a). */
final class TlMessageMessageService extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_message_message_service';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'out' => 'bool',
        'mentioned' => 'bool',
        'media_unread' => 'bool',
        'reactions_are_possible' => 'bool',
        'silent' => 'bool',
        'post' => 'bool',
        'legacy' => 'bool',
        'tl_id' => 'int',
        'date' => 'int',
        'ttl_period' => 'int',
    ];

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(TlMessageReplyHeader::class, 'reply_to');
    }
    public function action(): BelongsTo
    {
        return $this->belongsTo(TlMessageAction::class, 'action');
    }
    public function reactions(): BelongsTo
    {
        return $this->belongsTo(TlMessageReactions::class, 'reactions');
    }
}
