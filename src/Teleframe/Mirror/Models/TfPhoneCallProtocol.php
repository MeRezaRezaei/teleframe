<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Hand-authored NF5 child of tf_phone_calls — the FK→PhoneCallProtocol
 * protocol fact, flattened to its scalar union payload (library_versions
 * Vector<string> deferred).
 *
 * @property int $account_id
 * @property int $id
 * @property bool $udp_p2p
 * @property bool $udp_reflector
 * @property int $min_layer
 * @property int $max_layer
 */
final class TfPhoneCallProtocol extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_phone_calls_protocol';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'udp_p2p' => 'bool',
        'udp_reflector' => 'bool',
        'min_layer' => 'int',
        'max_layer' => 'int',
    ];

    public function phoneCall(): BelongsTo
    {
        return $this->belongsTo(TfPhoneCall::class, 'id', 'id');
    }
}
