<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Hand-authored NF5 child of tf_phone_calls — the optional receive_date fact.
 *
 * @property int $account_id
 * @property int $id
 * @property int $receive_date
 */
final class TfPhoneCallReceiveDate extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_phone_calls_receive_date';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'receive_date' => 'int',
    ];

    public function phoneCall(): BelongsTo
    {
        return $this->belongsTo(TfPhoneCall::class, 'id', 'id');
    }
}
