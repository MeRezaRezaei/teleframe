<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessageFwdFrom extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_fwd_from';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'imported' => 'boolean',
        'saved_out' => 'boolean',
        'from_id_type' => 'integer',
        'from_id_id' => 'integer',
        'date' => 'integer',
        'channel_post' => 'integer',
        'saved_from_peer_type' => 'integer',
        'saved_from_peer_id' => 'integer',
        'saved_from_msg_id' => 'integer',
        'saved_from_id_type' => 'integer',
        'saved_from_id_id' => 'integer',
        'saved_date' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TfMessage::class, 'id', 'id');
    }
}
