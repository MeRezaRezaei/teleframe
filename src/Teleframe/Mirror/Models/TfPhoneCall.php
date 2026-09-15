<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the PhoneCall union (table tf_phone_calls).
 * Constructor discriminates the six phoneCall* ctors; keyed (account_id, id).
 *
 * @property int $account_id
 * @property string $constructor
 * @property int $id
 * @property int $access_hash
 * @property int $date
 * @property int $admin_id
 * @property int $participant_id
 * @property bool $video
 */
final class TfPhoneCall extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_phone_calls';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'access_hash' => 'int',
        'date' => 'int',
        'admin_id' => 'int',
        'participant_id' => 'int',
        'video' => 'bool',
    ];

    public function protocol(): HasOne
    {
        return $this->hasOne(TfPhoneCallProtocol::class, 'id', 'id');
    }

    public function receiveDate(): HasOne
    {
        return $this->hasOne(TfPhoneCallReceiveDate::class, 'id', 'id');
    }
}
