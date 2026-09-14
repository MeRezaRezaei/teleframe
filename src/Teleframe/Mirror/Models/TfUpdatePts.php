<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_pts. 1:1 pts of a pts-carrying update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $pts
 */
final class TfUpdatePts extends MirrorChildModel
{
    protected $table = 'tf_updates_pts';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'pts'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'pts' => 'int'];
}
