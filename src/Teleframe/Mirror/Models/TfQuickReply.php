<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the QuickReply union (table tf_quick_replies).
 * Single ctor (quickReply); keyed by the natural shortcut_id.
 *
 * @property int $account_id
 * @property int $shortcut_id
 * @property string $shortcut
 * @property int $top_message
 * @property int $count
 */
final class TfQuickReply extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_quick_replies';

    protected $primaryKey = 'account_id';

    protected $guarded = [];

    protected $casts = [
        'shortcut_id' => 'int',
        'top_message' => 'int',
        'count' => 'int',
    ];
}
