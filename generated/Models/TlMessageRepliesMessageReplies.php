<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageRepliesMessageRepliesRecent_repliers;

/** Constructor model for messageReplies of MessageReplies (crc32 83d60fc2). */
final class TlMessageRepliesMessageReplies extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_replies_message_replies';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'comments' => 'bool',
        'replies' => 'int',
        'replies_pts' => 'int',
        'channel_id' => 'int',
        'max_id' => 'int',
        'read_max_id' => 'int',
    ];

    public function recentRepliers(): HasMany
    {
        return $this->tlChild(TlMessageRepliesMessageRepliesRecent_repliers::class);
    }
}
