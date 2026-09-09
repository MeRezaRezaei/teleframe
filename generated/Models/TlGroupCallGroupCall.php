<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for groupCall of GroupCall (crc32 efb2b617). */
final class TlGroupCallGroupCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_group_call_group_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'join_muted' => 'bool',
        'can_change_join_muted' => 'bool',
        'join_date_asc' => 'bool',
        'schedule_start_subscribed' => 'bool',
        'can_start_video' => 'bool',
        'record_video_active' => 'bool',
        'rtmp_stream' => 'bool',
        'listeners_hidden' => 'bool',
        'conference' => 'bool',
        'creator' => 'bool',
        'messages_enabled' => 'bool',
        'can_change_messages_enabled' => 'bool',
        'min' => 'bool',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'participants_count' => 'int',
        'title' => 'string',
        'stream_dc_id' => 'int',
        'record_start_date' => 'int',
        'schedule_date' => 'int',
        'unmuted_video_count' => 'int',
        'unmuted_video_limit' => 'int',
        'version' => 'int',
        'invite_link' => 'string',
        'send_paid_messages_stars' => 'int',
    ];
}
