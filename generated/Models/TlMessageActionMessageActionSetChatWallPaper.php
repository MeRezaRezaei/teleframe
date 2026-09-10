<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaper;

/** Constructor model for messageActionSetChatWallPaper of MessageAction (crc32 5060a3f4). */
final class TlMessageActionMessageActionSetChatWallPaper extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_set_chat_wall_paper';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'same' => 'bool',
        'for_both' => 'bool',
    ];

    public function wallpaper(): BelongsTo
    {
        return $this->belongsTo(TlWallPaper::class, 'wallpaper');
    }
}
