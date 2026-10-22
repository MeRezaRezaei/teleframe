<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the SavedDialog union (table tf_saved_dialogs).
 * Peer keyed by (peer_type, peer_id); constructor discriminates
 * savedDialog / monoForumDialog.
 *
 * @property int $account_id
 * @property string $constructor
 * @property int $peer_type
 * @property int $peer_id
 * @property int $top_message
 * @property bool $pinned
 */
final class TfSavedDialog extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_saved_dialogs';

    protected $primaryKey = 'account_id';

    protected $guarded = [];

    protected $casts = [
        'peer_type' => 'int',
        'peer_id' => 'int',
        'top_message' => 'int',
        'pinned' => 'bool',
    ];
}
