<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_date. 1:1 date of a date-carrying update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $date
 */
final class TfUpdateDate extends MirrorChildModel
{
    protected $table = 'tf_updates_date';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'date'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'date' => 'int'];
}
