<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessageServiceTtlPeriod extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_service_ttl_period';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'ttl_period' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(TfMessageService::class, 'id', 'id');
    }
}
