<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Hand-authored NF5 child of tf_admin_log_events — the FK→
 * ChannelAdminLogEventAction action fact. 52-ctor union mirrored flat:
 * a constructor discriminator plus the scalar residue across all ctors
 * (prev_value/new_value as TEXT for string/int/long union, join_muted /
 * via_chatlist flags, approved_by / user_id longs, prev_rank / new_rank
 * strings). Nested object payloads (Message, Photo, ChannelParticipant, ...)
 * are deferred to the shared catalog.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property string $prev_value
 * @property string $new_value
 * @property bool $join_muted
 * @property bool $via_chatlist
 * @property int $approved_by
 * @property int $user_id
 * @property string $prev_rank
 * @property string $new_rank
 */
final class TfAdminLogEventAction extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_admin_log_events_action';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'join_muted' => 'bool',
        'via_chatlist' => 'bool',
        'approved_by' => 'int',
        'user_id' => 'int',
    ];

    public function adminLogEvent(): BelongsTo
    {
        return $this->belongsTo(TfAdminLogEvent::class, 'id', 'id');
    }
}
