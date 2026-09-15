<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_top_msg_id. 1:1 top_msg_id of a pinned/dialog update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $top_msg_id
 */
final class TfUpdateTopMsgId extends MirrorChildModel
{
    protected $table = 'tf_updates_top_msg_id';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'top_msg_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'top_msg_id' => 'int'];
}
