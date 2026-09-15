<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_update_differences_pts. 1:1 differenceTooLong pts.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $position
 * @property int $pts
 */
final class TfUpdateDifferencePts extends MirrorChildModel
{
    protected $table = 'tf_update_differences_pts';

    /** @var list<string> */
    protected $fillable = ['account_id', 'position', 'pts'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'position' => 'int', 'pts' => 'int'];
}
