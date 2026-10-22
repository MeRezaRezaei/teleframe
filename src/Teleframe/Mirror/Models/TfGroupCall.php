<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the GroupCall union (table tf_group_calls).
 * Constructor discriminates groupCall / groupCallDiscarded; keyed (account_id, id).
 *
 * @property int $account_id
 * @property string $constructor
 * @property int $id
 * @property int $access_hash
 * @property int $duration
 */
final class TfGroupCall extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_group_calls';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'access_hash' => 'int',
        'duration' => 'int',
    ];
}
