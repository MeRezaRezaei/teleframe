<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlForumTopic;

/** Constructor model for channelAdminLogEventActionDeleteTopic of ChannelAdminLogEventAction (crc32 ae168909). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionDeleteTopic extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_d3cf76f819fa';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(TlForumTopic::class, 'topic');
    }
}
