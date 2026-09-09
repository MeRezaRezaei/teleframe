<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageAction;

/** Constructor model for updateChatUserTyping of Update (crc32 83487af0). */
final class TlUpdateUpdateChatUserTyping extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_chat_user_typing';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'chat_id' => 'int',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(TlSendMessageAction::class, 'action');
    }
}
