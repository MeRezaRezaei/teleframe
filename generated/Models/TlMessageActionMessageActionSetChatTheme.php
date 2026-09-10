<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatTheme;

/** Constructor model for messageActionSetChatTheme of MessageAction (crc32 b91bbd3a). */
final class TlMessageActionMessageActionSetChatTheme extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_set_chat_theme';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(TlChatTheme::class, 'theme');
    }
}
