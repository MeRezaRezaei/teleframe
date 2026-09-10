<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlForumTopic;

/** Constructor model for channelAdminLogEventActionEditTopic of ChannelAdminLogEventAction (crc32 f06fe208). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionEditTopic extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_1c072a4a5876';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function prevTopic(): BelongsTo
    {
        return $this->belongsTo(TlForumTopic::class, 'prev_topic');
    }
    public function newTopic(): BelongsTo
    {
        return $this->belongsTo(TlForumTopic::class, 'new_topic');
    }
}
