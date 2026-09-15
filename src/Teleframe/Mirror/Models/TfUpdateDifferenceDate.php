<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_update_differences_date. 1:1 differenceEmpty date.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $position
 * @property int $date
 */
final class TfUpdateDifferenceDate extends MirrorChildModel
{
    protected $table = 'tf_update_differences_date';

    /** @var list<string> */
    protected $fillable = ['account_id', 'position', 'date'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'position' => 'int', 'date' => 'int'];
}
