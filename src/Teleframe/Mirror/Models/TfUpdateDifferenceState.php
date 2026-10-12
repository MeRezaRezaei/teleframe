<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_update_differences_state. 1:1 embedded updates.State snapshot (states/intermediate_state).
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $position
 * @property int $pts
 * @property int $qts
 * @property int $date
 * @property int $seq
 * @property int $unread_count
 */
final class TfUpdateDifferenceState extends MirrorChildModel
{
    protected $table = 'tf_update_differences_state';

    /** @var list<string> */
    protected $fillable = ['account_id', 'position', 'pts', 'qts', 'date', 'seq', 'unread_count'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'position' => 'int', 'pts' => 'int', 'qts' => 'int', 'date' => 'int', 'seq' => 'int', 'unread_count' => 'int'];
}
