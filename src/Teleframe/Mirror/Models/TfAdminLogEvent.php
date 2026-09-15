<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the ChannelAdminLogEvent union
 * (table tf_admin_log_events). Single ctor (channelAdminLogEvent) — no
 * constructor discriminator column; the 52-ctor action union nests under
 * tf_admin_log_events_action.
 *
 * @property int $account_id
 * @property int $id
 * @property int $date
 * @property int $user_id
 */
final class TfAdminLogEvent extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_admin_log_events';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'date' => 'int',
        'user_id' => 'int',
    ];

    public function action(): HasOne
    {
        return $this->hasOne(TfAdminLogEventAction::class, 'id', 'id');
    }
}
