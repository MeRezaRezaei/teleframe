<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessageServiceFromId extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_service_from_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'from_id_type' => 'integer',
        'from_id_id' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(TfMessageService::class, 'id', 'id');
    }
}
