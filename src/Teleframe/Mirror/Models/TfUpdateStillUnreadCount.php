<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_still_unread_count. 1:1 still_unread_count of a read-inbox update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $still_unread_count
 */
final class TfUpdateStillUnreadCount extends MirrorChildModel
{
    protected $table = 'tf_updates_still_unread_count';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'still_unread_count'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'still_unread_count' => 'int'];
}
