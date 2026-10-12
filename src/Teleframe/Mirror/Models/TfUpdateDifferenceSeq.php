<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_update_differences_seq. 1:1 differenceEmpty seq.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $position
 * @property int $seq
 */
final class TfUpdateDifferenceSeq extends MirrorChildModel
{
    protected $table = 'tf_update_differences_seq';

    /** @var list<string> */
    protected $fillable = ['account_id', 'position', 'seq'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'position' => 'int', 'seq' => 'int'];
}
